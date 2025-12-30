<?php

namespace App\Http\Controllers;

use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EpgController extends Controller
{
    public function all(Request $request)
    {
        $domain = rtrim((string) config('app.streaming_domain', ''), '/');
        if ($domain === '' || str_contains($domain, 'localhost')) {
            $domain = rtrim((string) $request->getSchemeAndHttpHost(), '/');
        }

        $rangeStart = Carbon::now('UTC');
        $rangeEnd = $rangeStart->copy()->addDays(7);

        $channels = LiveChannel::query()
            ->where('enabled', true)
            ->orderBy('id')
            ->get(['id', 'name', 'status', 'started_at']);

        $channelIds = $channels->pluck('id')->all();

        $items = PlaylistItem::query()
            ->whereIn('live_channel_id', $channelIds)
            ->orWhereIn('vod_channel_id', $channelIds)
            ->with(['video'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $itemsByChannel = [];
        foreach ($items as $item) {
            $cid = (int) ($item->live_channel_id ?? 0);
            if ($cid <= 0) {
                $cid = (int) ($item->vod_channel_id ?? 0);
            }
            if ($cid <= 0) {
                continue;
            }
            $itemsByChannel[$cid] ??= [];
            $itemsByChannel[$cid][] = $item;
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $tv = $doc->createElement('tv');
        $tv->setAttribute('generator-info-name', 'IPTV Panel');
        $tv->setAttribute('generator-info-url', $domain);
        $doc->appendChild($tv);

        foreach ($channels as $channel) {
            $xmlChannelId = 'vod-channel-' . (int) $channel->id;

            $chEl = $doc->createElement('channel');
            $chEl->setAttribute('id', $xmlChannelId);

            $name = trim((string) ($channel->name ?? 'Channel ' . (int) $channel->id));
            $displayName = $doc->createElement('display-name');
            $displayName->appendChild($doc->createTextNode($name));
            $chEl->appendChild($displayName);

            $icon = $doc->createElement('icon');
            $icon->setAttribute('src', $domain . '/vod-channels/' . (int) $channel->id . '/logo-preview');
            $chEl->appendChild($icon);

            $tv->appendChild($chEl);
        }

        foreach ($channels as $channel) {
            $xmlChannelId = 'vod-channel-' . (int) $channel->id;
            $playlist = $itemsByChannel[(int) $channel->id] ?? [];

            $segments = [];
            foreach ($playlist as $item) {
                $video = $item->video;
                if (!$video) continue;

                $duration = (int) ($video->duration_seconds ?? 0);
                if ($duration <= 0) continue;

                $title = $this->getVideoDisplayTitle($video);

                $segments[] = [
                    'title' => $title,
                    'duration' => $duration,
                ];
            }

            if (empty($segments)) {
                continue;
            }

            $anchor = null;
            if ((string) ($channel->status ?? '') === 'live' && !empty($channel->started_at)) {
                try {
                    $anchor = Carbon::parse($channel->started_at)->utc();
                } catch (\Throwable $e) {
                    $anchor = null;
                }
            }

            foreach ($this->buildProgrammeBlocks($segments, $rangeStart, $rangeEnd, $anchor) as $p) {
                $pr = $doc->createElement('programme');
                $pr->setAttribute('channel', $xmlChannelId);
                $pr->setAttribute('start', $this->formatXmltvTime($p['start']));
                $pr->setAttribute('stop', $this->formatXmltvTime($p['stop']));

                $t = $doc->createElement('title');
                $t->setAttribute('lang', 'en');
                $t->appendChild($doc->createTextNode($p['title']));
                $pr->appendChild($t);

                $tv->appendChild($pr);
            }
        }

        return response($doc->saveXML(), 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * @param array<int, array{title:string, duration:int}> $segments
     * @return array<int, array{start:Carbon, stop:Carbon, title:string}>
     */
    private function buildProgrammeBlocks(array $segments, Carbon $rangeStart, Carbon $rangeEnd, ?Carbon $anchor): array
    {
        $total = 0;
        foreach ($segments as $s) {
            $total += (int) $s['duration'];
        }
        if ($total <= 0) {
            return [];
        }

        $cursor = $rangeStart->copy();

        $idx = 0;
        $offsetInCurrent = 0;

        if ($anchor) {
            $anchorUtc = $anchor->copy()->utc();

            if ($anchorUtc->lessThan($rangeStart)) {
                $diff = $anchorUtc->diffInSeconds($rangeStart);
                $mod = $diff % $total;

                $remaining = $mod;
                foreach ($segments as $i => $s) {
                    $dur = (int) $s['duration'];
                    if ($remaining < $dur) {
                        $idx = $i;
                        $offsetInCurrent = (int) $remaining;
                        break;
                    }
                    $remaining -= $dur;
                }
            }
        }

        $out = [];
        $guard = 0;

        while ($cursor->lessThan($rangeEnd) && $guard < 25000) {
            $guard++;

            $seg = $segments[$idx] ?? null;
            if (!$seg) break;

            $dur = (int) $seg['duration'];
            if ($dur <= 0) {
                $idx = ($idx + 1) % count($segments);
                $offsetInCurrent = 0;
                continue;
            }

            $remaining = max(1, $dur - $offsetInCurrent);
            $stop = $cursor->copy()->addSeconds($remaining);
            if ($stop->greaterThan($rangeEnd)) {
                $stop = $rangeEnd->copy();
            }

            $out[] = [
                'start' => $cursor->copy(),
                'stop' => $stop,
                'title' => (string) $seg['title'],
            ];

            $cursor = $stop;
            $idx = ($idx + 1) % count($segments);
            $offsetInCurrent = 0;
        }

        return $out;
    }

    private function formatXmltvTime(Carbon $dt): string
    {
        $u = $dt->copy()->utc();
        return $u->format('YmdHis') . ' +0000';
    }

    private function getVideoDisplayTitle($video): string
    {
        if (!$video) {
            return 'Unknown';
        }

        $title = trim((string) ($video->title ?? ''));
        $filePath = trim((string) ($video->file_path ?? ''));
        $isNumericTitle = ($title !== '' && preg_match('/^\d+$/', $title) === 1);

        if ($title !== '' && !$isNumericTitle) {
            return $title;
        }

        if ($filePath !== '') {
            $base = trim((string) pathinfo($filePath, PATHINFO_FILENAME));
            $parent = trim((string) basename((string) dirname($filePath)));

            if ($base !== '') {
                if ($parent !== '' && $parent !== '.' && $parent !== '/' && $parent !== $base) {
                    if (preg_match('/^\d+$/', $base) === 1 || $title === '' || $isNumericTitle) {
                        return $parent . ' / ' . $base;
                    }
                }
                return $base;
            }
        }

        if ($title !== '') {
            return $title;
        }

        return 'Video #' . (int) ($video->id ?? 0);
    }
}
