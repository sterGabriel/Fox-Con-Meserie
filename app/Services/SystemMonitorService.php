<?php

namespace App\Services;

class SystemMonitorService
{
    /**
     * Get CPU usage percentage
     */
    public static function getCpuUsage(): float
    {
        $cpu = 0;
        
        if (PHP_OS_FAMILY === 'Linux') {
            $cpu = self::getLinuxCpuUsage();
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $cpu = self::getWindowsCpuUsage();
        }

        return round(min($cpu, 100), 2);
    }

    /**
     * Get memory usage percentage
     */
    public static function getMemoryUsage(): float
    {
        $used = memory_get_usage(true);
        $total = ini_get('memory_limit');
        
        if ($total === false || $total === '-1') {
            $total = 128 * 1024 * 1024; // Default 128MB if unlimited
        } else {
            $total = self::convertToBytes($total);
        }

        return round(($used / $total) * 100, 2);
    }

    /**
     * Get system memory usage
     */
    public static function getSystemMemoryUsage(): float
    {
        if (PHP_OS_FAMILY === 'Linux') {
            return self::getLinuxMemoryUsage();
        }

        return 0;
    }

    /**
     * Get uptime in days
     */
    public static function getUptime(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = trim(@shell_exec('uptime -p 2>/dev/null') ?: @shell_exec('cat /proc/uptime 2>/dev/null'));
            
            if ($uptime) {
                return $uptime;
            }
        }

        return 'N/A';
    }

    /**
     * Get disk space info
     */
    public static function getDiskSpace(): array
    {
        $path = storage_path();
        $total = @disk_total_space($path) ?: 0;
        $free = @disk_free_space($path) ?: 0;
        $used = max($total - $free, 0);

        $usedPct = $total > 0 ? round(($used / $total) * 100, 1) : 0;

        return [
            'total'     => $total,
            'used'      => $used,
            'free'      => $free,
            'used_pct'  => $usedPct,
            'free_pct'  => round(100 - $usedPct, 1),
            'total_gb'  => round($total / 1024 / 1024 / 1024, 2),
            'used_gb'   => round($used / 1024 / 1024 / 1024, 2),
            'free_gb'   => round($free / 1024 / 1024 / 1024, 2),
        ];
    }

    /**
     * Get network info (from /proc/net/dev on Linux)
     */
    public static function getNetworkStats(): array
    {
        $input = 0;
        $output = 0;

        if (PHP_OS_FAMILY === 'Linux') {
            $stats = self::getLinuxNetworkStats();
            $input = $stats['input'] ?? 0;
            $output = $stats['output'] ?? 0;
        }

        return [
            'input_mbps'   => round($input / 1024 / 1024, 0),
            'output_mbps'  => round($output / 1024 / 1024, 0),
            'total_mbps'   => round(($input + $output) / 1024 / 1024, 0),
        ];
    }

    /**
     * Get Linux CPU usage
     */
    private static function getLinuxCpuUsage(): float
    {
        $load = @sys_getloadavg();
        if ($load === false) {
            return 0;
        }

        $cores = @shell_exec('nproc 2>/dev/null') ?: 1;
        $cores = intval(trim($cores)) ?: 1;

        // Load average / number of cores * 100
        return min(($load[0] / $cores) * 100, 100);
    }

    /**
     * Get Linux memory usage
     */
    private static function getLinuxMemoryUsage(): float
    {
        $meminfo = @file_get_contents('/proc/meminfo');
        if (!$meminfo) {
            return 0;
        }

        $lines = explode("\n", $meminfo);
        $memTotal = 0;
        $memAvailable = 0;

        foreach ($lines as $line) {
            if (strpos($line, 'MemTotal:') === 0) {
                $memTotal = intval(explode(':', $line)[1]);
            }
            if (strpos($line, 'MemAvailable:') === 0) {
                $memAvailable = intval(explode(':', $line)[1]);
            }
        }

        if ($memTotal === 0) {
            return 0;
        }

        $used = $memTotal - $memAvailable;
        return round(($used / $memTotal) * 100, 2);
    }

    /**
     * Get Linux network stats
     */
    private static function getLinuxNetworkStats(): array
    {
        $net_dev = @file_get_contents('/proc/net/dev');
        if (!$net_dev) {
            return ['input' => 0, 'output' => 0];
        }

        $input = 0;
        $output = 0;
        $lines = explode("\n", $net_dev);

        foreach ($lines as $line) {
            // Skip headers and loopback
            if (strpos($line, 'Inter-') !== false || strpos($line, 'face') !== false) {
                continue;
            }

            if (strpos($line, 'lo:') !== false) {
                continue;
            }

            // Parse interface stats: name | bytes_in packets_in ... | bytes_out packets_out ...
            $parts = preg_split('/[\s:]+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);

            if (count($parts) >= 10) {
                // Get only active interfaces (not docker/veth)
                $name = $parts[0];
                if (preg_match('/^(eth|en|wlan|ppp|tun)/', $name)) {
                    $input += (int)$parts[1];  // bytes received
                    $output += (int)$parts[9]; // bytes transmitted
                }
            }
        }

        // Convert cumulative bytes to average Mbps (rough estimate)
        // Assuming average packet rate - this is total data since boot
        // For realistic bandwidth, we show the actual throughput rate
        $uptime = @file_get_contents('/proc/uptime');
        if ($uptime) {
            $uptime_seconds = intval(explode(' ', $uptime)[0]);
            if ($uptime_seconds > 0) {
                $input = $input / $uptime_seconds; // bytes per second
                $output = $output / $uptime_seconds;
            }
        }

        return ['input' => $input, 'output' => $output];
    }

    /**
     * Get Windows CPU usage
     */
    private static function getWindowsCpuUsage(): float
    {
        $output = @shell_exec('wmic cpu get loadpercentage 2>nul') ?: '';
        preg_match('/(\d+)/', $output, $matches);
        return isset($matches[1]) ? (float)$matches[1] : 0;
    }

    /**
     * Convert various memory formats to bytes
     */
    private static function convertToBytes($value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);

        $val = (int)$value;

        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
                break;
        }

        return $val;
    }
}
