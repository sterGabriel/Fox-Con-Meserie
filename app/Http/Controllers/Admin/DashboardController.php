<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveChannel;
use App\Services\SystemMonitorService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Channels
        $totalChannels   = LiveChannel::count();
        $enabledChannels = LiveChannel::where('enabled', 1)->count();
        $runningChannels = LiveChannel::where('status', 'running')->count();
        $errorChannels   = LiveChannel::where('status', 'error')->count();
        $idleChannels    = LiveChannel::where('status', 'idle')->count();

        // Recent channels for quick status view
        $recentChannels = LiveChannel::query()
            ->select(['id', 'name', 'status', 'enabled', 'resolution', 'video_bitrate', 'fps', 'updated_at', 'logo_path'])
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        // Alerts (basic)
        $channelsMissingLogo = LiveChannel::whereNull('logo_path')->orWhere('logo_path', '')->count();
        $channelsMissingOutput = LiveChannel::where(function ($q) {
            $q->whereNull('encoded_output_path')->orWhereNull('hls_output_path');
        })->count();

        // Storage (MVP)
        $storagePath = storage_path();
        $diskTotal = @disk_total_space($storagePath) ?: 0;
        $diskFree  = @disk_free_space($storagePath) ?: 0;
        $diskUsed  = max($diskTotal - $diskFree, 0);

        $diskUsedPct = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : null;

        // Encoding jobs (optional: only if table exists)
        $jobs = collect();
        $jobsStats = [
            'queued'  => null,
            'running' => null,
            'failed'  => null,
        ];

        // If you have an encoding_jobs table, enable this block by creating a model later.
        // For now, we use DB::table with a safe existence check.
        try {
            $hasJobsTable = DB::getSchemaBuilder()->hasTable('encoding_jobs');

            if ($hasJobsTable) {
                $jobsStats['queued']  = DB::table('encoding_jobs')->where('status', 'queued')->count();
                $jobsStats['running'] = DB::table('encoding_jobs')->where('status', 'running')->count();
                $jobsStats['failed']  = DB::table('encoding_jobs')->where('status', 'failed')->count();

                $jobs = DB::table('encoding_jobs')
                    ->orderByDesc('id')
                    ->limit(8)
                    ->get(['id', 'vod_channel_id', 'status', 'progress', 'created_at', 'updated_at']);
            }
        } catch (\Throwable $e) {
            // Keep dashboard resilient; do not fail if schema/table is missing
        }

        // ===== ALERT SUMMARY (Severity Calculation) =====
        $critical = [];
        $warning = [];
        $okCount = 0;

        // Rule 1: Disk >= 90% → CRITICAL
        if (is_numeric($diskUsedPct) && $diskUsedPct >= 90) {
            $critical[] = "Disk usage at {$diskUsedPct}%";
        } elseif (is_numeric($diskUsedPct) && $diskUsedPct >= 75) {
            // Rule 2: Disk 75–89% → WARNING
            $warning[] = "Disk usage at {$diskUsedPct}%";
        }

        // Rule 3: Missing outputs > 0 → CRITICAL
        if ($channelsMissingOutput > 0) {
            $critical[] = "{$channelsMissingOutput} channels missing outputs";
        }

        // Rule 4: Missing logo > 0 → WARNING
        if ($channelsMissingLogo > 0) {
            $warning[] = "{$channelsMissingLogo} channels missing logo";
        }

        // Rule 5: Running = 0 AND channels > 0 → WARNING
        if ($runningChannels === 0 && $totalChannels > 0) {
            $warning[] = "No channels running";
        }

        // Rule 6: Jobs failed > 0 → CRITICAL
        $failedJobs = $jobsStats['failed'] ?? 0;
        if ($failedJobs > 0) {
            $critical[] = "{$failedJobs} failed encoding jobs";
        }

        // OK count: total alerts resolved
        $okCount = max(0, 6 - count($critical) - count($warning));

        $alertSummary = [
            'critical' => $critical,
            'warning'  => $warning,
            'ok'       => $okCount,
        ];

        // ===== SYSTEM METRICS (REAL DATA) =====
        $cpuUsage = SystemMonitorService::getCpuUsage();
        $ramUsage = SystemMonitorService::getSystemMemoryUsage();
        $networkStats = SystemMonitorService::getNetworkStats();
        $uptime = SystemMonitorService::getUptime();
        $diskStats = SystemMonitorService::getDiskSpace();

        return view('admin.dashboard', [
            'totalChannels'        => $totalChannels,
            'enabledChannels'      => $enabledChannels,
            'runningChannels'      => $runningChannels,
            'errorChannels'        => $errorChannels,
            'idleChannels'         => $idleChannels,
            'recentChannels'       => $recentChannels,
            'channelsMissingLogo'  => $channelsMissingLogo,
            'channelsMissingOutput'=> $channelsMissingOutput,
            'diskTotal'            => $diskTotal,
            'diskFree'             => $diskFree,
            'diskUsedPct'          => $diskUsedPct,
            'jobsStats'            => $jobsStats,
            'jobs'                 => $jobs,
            'alertSummary'         => $alertSummary,
            // System metrics
            'cpuUsage'             => $cpuUsage,
            'ramUsage'             => $ramUsage,
            'networkStats'         => $networkStats,
            'uptime'               => $uptime,
            'diskStats'            => $diskStats,
        ]);
    }
}
