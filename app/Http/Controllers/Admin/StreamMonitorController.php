<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveChannel;
use App\Support\LogTail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StreamMonitorController extends Controller
{
    private function toCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    private function fmtDate(mixed $value): ?string
    {
        $dt = $this->toCarbon($value);
        return $dt ? $dt->format('Y-m-d H:i:s') : (is_string($value) && trim($value) !== '' ? $value : null);
    }

    public function index()
    {
        return view('admin.tools.stream-monitor', [
            'activeWindowSeconds' => 300,
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

        $logPath = (string) env('NGINX_ACCESS_LOG', '/var/log/nginx/access.log');
        $lines = LogTail::tailLines($logPath, 1400000);

        $sinceEpoch = now()->subSeconds($windowSeconds)->getTimestamp();

        $channels = [];
        foreach ($lines as $line) {
            // 37.49.224.38 - - [04/Jan/2026:00:00:09 +0200] "GET /streams/3/hls/stream.m3u8 HTTP/1.1" 206 597 "-" "UA"
            if (!preg_match('/^(\S+)\s+\S+\s+\S+\s+\[([^\]]+)\]\s+"(\S+)\s+([^\s]+)\s+HTTP\/[0-9.]+"\s+(\d{3})\s+([0-9-]+)\s+"([^"]*)"\s+"([^"]*)"/', $line, $m)) {
                continue;
            }

            $ip = (string) $m[1];
            $timeLocal = (string) $m[2];
            $method = (string) $m[3];
            $uri = (string) $m[4];
            $status = (int) $m[5];
            $bytesRaw = (string) $m[6];
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

            $bytes = ctype_digit($bytesRaw) ? (int) $bytesRaw : 0;

            $pathRemainder = (string) $mm[2];
            $file = $pathRemainder;
            if (str_contains($file, '/')) {
                $parts = explode('/', $file);
                $file = (string) end($parts);
            }

            $channels[$channelId] ??= [
                'channel_id' => $channelId,
                'min_ts' => 0,
                'max_ts' => 0,
                'bytes_total' => 0,
                'requests_total' => 0,
                'ips' => [],
            ];

            $channels[$channelId]['requests_total']++;
            $channels[$channelId]['bytes_total'] += $bytes;
            $channels[$channelId]['min_ts'] = $channels[$channelId]['min_ts'] ? min($channels[$channelId]['min_ts'], $ts) : $ts;
            $channels[$channelId]['max_ts'] = max($channels[$channelId]['max_ts'], $ts);

            $channels[$channelId]['ips'][$ip] ??= [
                'ip' => $ip,
                'hit_count' => 0,
                'bytes_total' => 0,
                'last_seen_ts' => 0,
                'last_file' => '',
                'method' => '',
                'user_agent' => '',
            ];

            $channels[$channelId]['ips'][$ip]['hit_count']++;
            $channels[$channelId]['ips'][$ip]['bytes_total'] += $bytes;
            if ($ts >= $channels[$channelId]['ips'][$ip]['last_seen_ts']) {
                $channels[$channelId]['ips'][$ip]['last_seen_ts'] = $ts;
                $channels[$channelId]['ips'][$ip]['last_file'] = $file;
                $channels[$channelId]['ips'][$ip]['method'] = $method;
                $channels[$channelId]['ips'][$ip]['user_agent'] = $ua;
            }
        }

        uasort($channels, fn ($a, $b) => ($b['max_ts'] <=> $a['max_ts']));

        $channelIds = array_keys($channels);
        $channelRows = $channelIds
            ? LiveChannel::query()
                ->whereIn('id', $channelIds)
                ->get(['id', 'name', 'status', 'encoder_pid', 'started_at', 'last_started_at', 'last_stopped_at'])
                ->keyBy('id')
            : collect();

        $out = [];
        foreach ($channels as $channelId => $agg) {
            $ch = $channelRows->get((int) $channelId);

            $ipRows = array_values($agg['ips']);
            usort($ipRows, fn ($a, $b) => ($b['bytes_total'] <=> $a['bytes_total']));

            $bytesTotal = (int) ($agg['bytes_total'] ?? 0);
            $reqTotal = (int) ($agg['requests_total'] ?? 0);
            $bpsWindow = $windowSeconds > 0 ? (int) floor(($bytesTotal * 8) / $windowSeconds) : 0;

            $minTs = (int) ($agg['min_ts'] ?? 0);
            $maxTs = (int) ($agg['max_ts'] ?? 0);
            $observedSeconds = ($minTs > 0 && $maxTs >= $minTs) ? max(1, $maxTs - $minTs) : $windowSeconds;
            $bpsObserved = $observedSeconds > 0 ? (int) floor(($bytesTotal * 8) / $observedSeconds) : 0;

            $startedAt = $ch?->started_at;
            $uptimeSeconds = null;
            $startedAtC = $this->toCarbon($startedAt);
            if ($startedAtC) {
                try {
                    $uptimeSeconds = (int) floor($startedAtC->diffInSeconds(now(), true));
                } catch (\Throwable $e) {
                    $uptimeSeconds = null;
                }
            }

            $ffSpeed = null;
            $isRunning = null;
            $pid = $ch ? (int) ($ch->encoder_pid ?? 0) : 0;
            if ($ch) {
                try {
                    $engine = new \App\Services\ChannelEngineService($ch);
                    $isRunning = $pid > 1 ? $engine->isRunning($pid) : false;
                    $ffSpeed = $engine->getLastFfmpegSpeed();
                } catch (\Throwable $e) {
                    $isRunning = null;
                    $ffSpeed = null;
                }
            }

            $lastDowntimeSeconds = null;
            $stoppedSinceSeconds = null;
            $lastStartedAtC = $this->toCarbon($ch?->last_started_at);
            $lastStoppedAtC = $this->toCarbon($ch?->last_stopped_at);
            if ($lastStartedAtC && $lastStoppedAtC) {
                try {
                    if ($lastStoppedAtC->lt($lastStartedAtC)) {
                        $lastDowntimeSeconds = (int) floor($lastStartedAtC->diffInSeconds($lastStoppedAtC, true));
                    }
                } catch (\Throwable $e) {
                    $lastDowntimeSeconds = null;
                }
            }
            if ($isRunning === false && $lastStoppedAtC) {
                try {
                    $stoppedSinceSeconds = (int) floor($lastStoppedAtC->diffInSeconds(now(), true));
                } catch (\Throwable $e) {
                    $stoppedSinceSeconds = null;
                }
            }

            $ipsOut = array_map(function (array $row) {
                return [
                    'ip' => (string) $row['ip'],
                    'hit_count' => (int) ($row['hit_count'] ?? 0),
                    'bytes_total' => (int) ($row['bytes_total'] ?? 0),
                    'last_seen_at' => $row['last_seen_ts'] ? date('Y-m-d H:i:s', (int) $row['last_seen_ts']) : null,
                    'last_file' => (string) ($row['last_file'] ?? ''),
                    'method' => (string) ($row['method'] ?? ''),
                    'user_agent' => (string) ($row['user_agent'] ?? ''),
                ];
            }, array_slice($ipRows, 0, 80));

            $out[] = [
                'channel_id' => (int) $channelId,
                'channel_name' => (string) ($ch?->name ?? ('Channel #' . (int) $channelId)),
                'channel_status' => (string) ($ch?->status ?? ''),
                'encoder_pid' => $pid ?: null,
                'is_running' => $isRunning,
                'started_at' => $this->fmtDate($ch?->started_at),
                'uptime_seconds' => $uptimeSeconds,
                'last_started_at' => $this->fmtDate($ch?->last_started_at),
                'last_stopped_at' => $this->fmtDate($ch?->last_stopped_at),
                'last_downtime_seconds' => $lastDowntimeSeconds,
                'stopped_since_seconds' => $stoppedSinceSeconds,
                'ffmpeg_speed' => $ffSpeed,
                'active_window_seconds' => $windowSeconds,
                'observed_seconds' => (int) $observedSeconds,
                'unique_ips' => count($agg['ips'] ?? []),
                'requests' => $reqTotal,
                'bytes_total' => $bytesTotal,
                'bitrate_bps' => $bpsWindow,
                'bitrate_observed_bps' => $bpsObserved,
                'last_seen_at' => $agg['max_ts'] ? date('Y-m-d H:i:s', (int) $agg['max_ts']) : null,
                'ips' => $ipsOut,
            ];
        }

        return response()->json([
            'server_time' => now()->format('Y-m-d H:i:s'),
            'channels' => $out,
        ]);
    }
}
