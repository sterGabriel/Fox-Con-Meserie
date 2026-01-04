<?php

namespace App\Listeners;

use App\Models\UserLoginEvent;
use App\Support\IpUtils;
use Illuminate\Auth\Events\Login;

class RecordUserLogin
{
    public function handle(Login $event): void
    {
        $request = request();

        $ip = null;
        $ua = null;
        if ($request) {
            $ip = IpUtils::clientIp($request);
            $ua = $request->userAgent();
        }

        UserLoginEvent::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'ip_address' => $ip,
            'user_agent' => $ua,
            'guard' => (string) ($event->guard ?? ''),
            'remember' => (bool) ($event->remember ?? false),
            'logged_in_at' => now(),
        ]);

        try {
            $event->user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $ip,
                'last_login_user_agent' => $ua,
            ])->save();
        } catch (\Throwable $e) {
            // Never break login flow if tracking fails.
        }
    }
}
