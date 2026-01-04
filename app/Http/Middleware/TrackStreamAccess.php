<?php

namespace App\Http\Middleware;

use App\Models\StreamIpActivity;
use App\Support\IpUtils;
use Closure;
use Illuminate\Http\Request;

class TrackStreamAccess
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $channel = $request->route('channel');
            if ($channel === null || !is_numeric($channel)) {
                return $response;
            }

            $ip = (string) IpUtils::clientIp($request);
            if ($ip === '') {
                return $response;
            }

            $now = now();
            $channelId = (int) $channel;

            $file = (string) ($request->route('file') ?? '');
            if ($file === '') {
                $file = (string) ($request->route('subdir') ?? '');
            }

            $record = StreamIpActivity::query()
                ->where('channel_id', $channelId)
                ->where('ip', $ip)
                ->first();

            if (!$record) {
                StreamIpActivity::query()->create([
                    'channel_id' => $channelId,
                    'ip' => $ip,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                    'hit_count' => 1,
                    'last_file' => $file !== '' ? substr($file, 0, 255) : null,
                    'last_method' => $request->method(),
                    'last_user_agent' => substr((string) $request->userAgent(), 0, 255),
                ]);

                return $response;
            }

            $record->forceFill([
                'last_seen_at' => $now,
                'last_file' => $file !== '' ? substr($file, 0, 255) : null,
                'last_method' => $request->method(),
                'last_user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
            $record->hit_count = (int) $record->hit_count + 1;
            $record->save();
        } catch (\Throwable $e) {
            // Never break streaming if tracking fails.
        }

        return $response;
    }
}
