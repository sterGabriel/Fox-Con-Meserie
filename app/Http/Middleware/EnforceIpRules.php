<?php

namespace App\Http\Middleware;

use App\Models\IpRule;
use App\Support\IpUtils;
use Closure;
use Illuminate\Http\Request;

class EnforceIpRules
{
    public function handle(Request $request, Closure $next)
    {
        $ip = (string) IpUtils::clientIp($request);
        if ($ip === '') {
            return $next($request);
        }

        $rule = IpRule::query()
            ->where('ip', $ip)
            ->where('enabled', true)
            ->first();

        if ($rule) {
            if ($rule->action === 'allow') {
                return $next($request);
            }

            if ($rule->action === 'block') {
                abort(403, 'Your IP address is blocked.');
            }
        }

        return $next($request);
    }
}
