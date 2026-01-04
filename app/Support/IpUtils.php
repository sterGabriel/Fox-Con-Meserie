<?php

namespace App\Support;

use Illuminate\Http\Request;

class IpUtils
{
    /**
     * Resolve the real client IP when the request comes from a trusted proxy.
     *
     * Set TRUSTED_PROXIES env var (comma-separated IPs/CIDRs), default: 127.0.0.1,::1
     */
    public static function clientIp(Request $request): string
    {
        $remoteAddr = (string) ($request->server('REMOTE_ADDR') ?? '');

        if ($remoteAddr !== '' && self::isTrustedProxy($remoteAddr)) {
            $xff = (string) $request->headers->get('x-forwarded-for', '');
            if ($xff !== '') {
                // XFF may contain a chain: client, proxy1, proxy2
                $parts = array_map('trim', explode(',', $xff));
                foreach ($parts as $candidate) {
                    if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
                        return $candidate;
                    }
                }
            }

            $xRealIp = (string) $request->headers->get('x-real-ip', '');
            if ($xRealIp !== '' && filter_var($xRealIp, FILTER_VALIDATE_IP)) {
                return $xRealIp;
            }
        }

        $fallback = (string) $request->ip();
        return $fallback !== '' ? $fallback : $remoteAddr;
    }

    private static function isTrustedProxy(string $ip): bool
    {
        $configured = config('app.trusted_proxies');
        if (!is_string($configured) || trim($configured) === '') {
            $configured = (string) env('TRUSTED_PROXIES', '127.0.0.1,::1');
        }

        $entries = array_filter(array_map('trim', explode(',', $configured)));
        if (empty($entries)) {
            $entries = ['127.0.0.1', '::1'];
        }

        foreach ($entries as $entry) {
            if (self::ipInCidr($ip, $entry) || $ip === $entry) {
                return true;
            }
        }

        return false;
    }

    private static function ipInCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return false;
        }

        [$subnet, $maskBits] = array_map('trim', explode('/', $cidr, 2));
        if (!filter_var($subnet, FILTER_VALIDATE_IP)) {
            return false;
        }

        $maskBits = (int) $maskBits;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        $len = strlen($ipBin);
        if ($len !== strlen($subnetBin)) {
            return false;
        }

        $maxBits = $len * 8;
        if ($maskBits < 0 || $maskBits > $maxBits) {
            return false;
        }

        $bytes = intdiv($maskBits, 8);
        $bits = $maskBits % 8;

        if ($bytes > 0) {
            if (substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
                return false;
            }
        }

        if ($bits === 0) {
            return true;
        }

        $mask = chr((0xFF << (8 - $bits)) & 0xFF);
        return (ord($ipBin[$bytes]) & ord($mask)) === (ord($subnetBin[$bytes]) & ord($mask));
    }
}
