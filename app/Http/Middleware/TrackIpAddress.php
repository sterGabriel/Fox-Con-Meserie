<?php

namespace App\Http\Middleware;

use App\Models\IpAddress;
use App\Support\IpUtils;
use Closure;
use Illuminate\Http\Request;

class TrackIpAddress
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $ip = (string) IpUtils::clientIp($request);
            if ($ip === '') {
                return $response;
            }

            $path = (string) $request->path();
            if ($this->shouldIgnorePath($path)) {
                return $response;
            }

            $now = now();
            $user = $request->user();

            $record = IpAddress::query()->where('ip', $ip)->first();
            if (!$record) {
                IpAddress::query()->create([
                    'ip' => $ip,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                    'hit_count' => 1,
                    'last_path' => $path,
                    'last_method' => $request->method(),
                    'last_user_id' => $user?->id,
                    'last_user_agent' => substr((string) $request->userAgent(), 0, 255),
                ]);

                return $response;
            }

            $record->forceFill([
                'last_seen_at' => $now,
                'last_path' => $path,
                'last_method' => $request->method(),
                'last_user_id' => $user?->id,
                'last_user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
            $record->hit_count = (int) $record->hit_count + 1;
            $record->save();
        } catch (\Throwable $e) {
            // Never break the request if tracking fails.
        }

        return $response;
    }

    private function shouldIgnorePath(string $path): bool
    {
        $path = ltrim($path, '/');

        return $path === ''
            || str_starts_with($path, 'assets/')
            || str_starts_with($path, 'build/')
            || str_starts_with($path, 'storage/')
            || str_starts_with($path, 'vendor/')
            || str_starts_with($path, 'tools/ip-table')
            || $path === 'favicon.ico';
    }
}
