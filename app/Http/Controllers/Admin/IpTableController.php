<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpAddress;
use App\Models\IpRule;
use App\Models\StreamIpActivity;
use App\Models\LiveChannel;
use App\Support\LogTail;
use Illuminate\Http\Request;

class IpTableController extends Controller
{
    public function index(Request $request)
    {
        $ips = IpAddress::query()
            ->orderByDesc('last_seen_at')
            ->orderByDesc('hit_count')
            ->paginate(50);

        $rulesByIp = IpRule::query()
            ->whereIn('ip', $ips->pluck('ip')->all())
            ->where('enabled', true)
            ->get()
            ->keyBy('ip');

        $allowRules = IpRule::query()
            ->where('enabled', true)
            ->where('action', 'allow')
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get();

        $blockRules = IpRule::query()
            ->where('enabled', true)
            ->where('action', 'block')
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get();

        $labelsByIp = IpAddress::query()
            ->whereIn('ip', $allowRules->pluck('ip')->merge($blockRules->pluck('ip'))->unique()->values()->all())
            ->get(['ip', 'label'])
            ->pluck('label', 'ip');

        $activeWindowSeconds = 300;

        return view('admin.tools.ip-table', [
            'ips' => $ips,
            'rulesByIp' => $rulesByIp,
            'allowRules' => $allowRules,
            'blockRules' => $blockRules,
            'labelsByIp' => $labelsByIp,
            'activeWindowSeconds' => $activeWindowSeconds,
        ]);
    }

    public function live(Request $request)
    {
        $windowSeconds = (int) $request->query('window', 300);
        if ($windowSeconds < 10) {
            $windowSeconds = 10;
        }
        if ($windowSeconds > 86400) {
            $windowSeconds = 86400;
        }

        $requestedChannelId = $request->query('channel_id');
        $channelFilter = null;
        if (is_numeric($requestedChannelId) && (int) $requestedChannelId > 0) {
            $channelFilter = (int) $requestedChannelId;
        }

        // IMPORTANT: Streams are served by Nginx as static files, so Laravel middleware does not see viewer requests.
        // Use Nginx access.log to detect who is requesting each channel in real time.
        $logPath = (string) env('NGINX_ACCESS_LOG', '/var/log/nginx/access.log');
        $lines = LogTail::tailLines($logPath, 1200000);

        $sinceEpoch = now()->subSeconds($windowSeconds)->getTimestamp();
        $channels = [];
        $allIps = [];

        foreach ($lines as $line) {
            // Default Nginx combined-ish format:
            // 37.49.224.38 - - [04/Jan/2026:00:00:09 +0200] "GET /streams/3/hls/stream.m3u8 HTTP/1.1" 206 597 "-" "UA"
            if (!preg_match('/^(\S+)\s+\S+\s+\S+\s+\[([^\]]+)\]\s+"(\S+)\s+([^\s]+)\s+HTTP\/[0-9.]+"\s+(\d{3})\s+([0-9-]+)\s+"([^"]*)"\s+"([^"]*)"/', $line, $m)) {
                continue;
            }

            $ip = (string) $m[1];
            $timeLocal = (string) $m[2];
            $method = (string) $m[3];
            $uri = (string) $m[4];
            $status = (int) $m[5];
            $ua = (string) $m[8];

            if ($status !== 200 && $status !== 206) {
                continue;
            }

            if (!str_starts_with($uri, '/streams/')) {
                continue;
            }
            if ($uri === '/streams/all.m3u8') {
                continue;
            }

            if (!preg_match('#^/streams/(\d+)/(.*)$#', $uri, $mm)) {
                continue;
            }
            $channelId = (int) $mm[1];
            if ($channelId <= 0) {
                continue;
            }
            if ($channelFilter !== null && $channelId !== $channelFilter) {
                continue;
            }

            $dt = \DateTimeImmutable::createFromFormat('d/M/Y:H:i:s O', $timeLocal);
            if (!$dt) {
                continue;
            }
            $ts = $dt->getTimestamp();
            if ($ts < $sinceEpoch) {
                continue;
            }

            $pathRemainder = (string) $mm[2];
            $file = $pathRemainder;
            if (str_contains($file, '/')) {
                $parts = explode('/', $file);
                $file = (string) end($parts);
            }

            $channels[$channelId] ??= [
                'channel_id' => $channelId,
                'max_ts' => 0,
                'ips' => [],
            ];
            $channels[$channelId]['max_ts'] = max($channels[$channelId]['max_ts'], $ts);

            $channels[$channelId]['ips'][$ip] ??= [
                'ip' => $ip,
                'hit_count' => 0,
                'last_seen_ts' => 0,
                'last_file' => '',
                'method' => '',
                'user_agent' => '',
            ];

            $channels[$channelId]['ips'][$ip]['hit_count']++;
            if ($ts >= $channels[$channelId]['ips'][$ip]['last_seen_ts']) {
                $channels[$channelId]['ips'][$ip]['last_seen_ts'] = $ts;
                $channels[$channelId]['ips'][$ip]['last_file'] = $file;
                $channels[$channelId]['ips'][$ip]['method'] = $method;
                $channels[$channelId]['ips'][$ip]['user_agent'] = $ua;
            }

            $allIps[] = $ip;
        }

        // Order channels by most recent activity.
        uasort($channels, fn ($a, $b) => ($b['max_ts'] <=> $a['max_ts']));

        $channelIds = array_keys($channels);
        $channelNames = $channelIds
            ? LiveChannel::query()->whereIn('id', $channelIds)->get(['id', 'name'])->pluck('name', 'id')
            : collect();

        $allIps = array_values(array_unique(array_filter($allIps)));
        $rules = $allIps
            ? IpRule::query()->whereIn('ip', $allIps)->where('enabled', true)->get()->keyBy('ip')
            : collect();
        $labels = $allIps
            ? IpAddress::query()->whereIn('ip', $allIps)->get(['ip', 'label'])->pluck('label', 'ip')
            : collect();

        $data = [];
        foreach ($channels as $channelId => $ch) {
            $ipRows = array_values($ch['ips']);
            usort($ipRows, fn ($a, $b) => ($b['last_seen_ts'] <=> $a['last_seen_ts']));

            $ipsOut = array_map(function (array $row) use ($rules, $labels) {
                $ip = (string) $row['ip'];
                $rule = $rules[$ip] ?? null;

                return [
                    'ip' => $ip,
                    'hit_count' => (int) $row['hit_count'],
                    'last_seen_at' => $row['last_seen_ts'] ? date('Y-m-d H:i:s', (int) $row['last_seen_ts']) : null,
                    'last_file' => (string) ($row['last_file'] ?? ''),
                    'method' => (string) ($row['method'] ?? ''),
                    'user_agent' => (string) ($row['user_agent'] ?? ''),
                    'status' => $rule?->action ?? 'none',
                    'label' => (string) (($labels[$ip] ?? '') ?: ''),
                ];
            }, array_slice($ipRows, 0, 50));

            $uniqueIps = count($ch['ips']);
            $totalRequests = array_reduce($ipRows, fn ($carry, $r) => $carry + (int) ($r['hit_count'] ?? 0), 0);
            $lastSeenTs = (int) ($ch['max_ts'] ?? 0);

            $data[] = [
                'channel_id' => (int) $channelId,
                'channel_name' => (string) ($channelNames[(int) $channelId] ?? ('Channel #' . (int) $channelId)),
                'active_window_seconds' => $windowSeconds,
                'unique_ips' => $uniqueIps,
                'requests' => $totalRequests,
                'last_seen_at' => $lastSeenTs ? date('Y-m-d H:i:s', $lastSeenTs) : null,
                'ips' => $ipsOut,
            ];
        }

        return response()->json([
            'server_time' => now()->format('Y-m-d H:i:s'),
            'channels' => $data,
        ]);
    }

    public function updateLabel(Request $request, IpAddress $ipAddress)
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $ipAddress->fill($validated);
        $ipAddress->save();

        return back()->with('success', 'IP updated.');
    }

    public function block(Request $request, IpAddress $ipAddress)
    {
        if ($ipAddress->ip === (string) $request->ip()) {
            return back()->with('error', 'You cannot block your current IP.');
        }

        IpRule::query()->updateOrCreate(
            ['ip' => $ipAddress->ip],
            [
                'action' => 'block',
                'enabled' => true,
                'updated_by_user_id' => $request->user()?->id,
            ]
        );

        return back()->with('success', 'IP blocked.');
    }

    public function allow(Request $request, IpAddress $ipAddress)
    {
        IpRule::query()->updateOrCreate(
            ['ip' => $ipAddress->ip],
            [
                'action' => 'allow',
                'enabled' => true,
                'updated_by_user_id' => $request->user()?->id,
            ]
        );

        return back()->with('success', 'IP allowed.');
    }

    public function clearRule(IpAddress $ipAddress)
    {
        IpRule::query()->where('ip', $ipAddress->ip)->delete();

        return back()->with('success', 'IP rule cleared.');
    }

    public function allowIp(Request $request)
    {
        $validated = $request->validate([
            'ip' => ['required', 'ip'],
        ]);

        IpRule::query()->updateOrCreate(
            ['ip' => $validated['ip']],
            [
                'action' => 'allow',
                'enabled' => true,
                'updated_by_user_id' => $request->user()?->id,
            ]
        );

        return back()->with('success', 'IP allowed.');
    }

    public function blockIp(Request $request)
    {
        $validated = $request->validate([
            'ip' => ['required', 'ip'],
        ]);

        if ($validated['ip'] === (string) $request->ip()) {
            return back()->with('error', 'You cannot block your current IP.');
        }

        IpRule::query()->updateOrCreate(
            ['ip' => $validated['ip']],
            [
                'action' => 'block',
                'enabled' => true,
                'updated_by_user_id' => $request->user()?->id,
            ]
        );

        return back()->with('success', 'IP blocked.');
    }

    public function clearIp(Request $request)
    {
        $validated = $request->validate([
            'ip' => ['required', 'ip'],
        ]);

        IpRule::query()->where('ip', $validated['ip'])->delete();

        return back()->with('success', 'IP rule cleared.');
    }
}
