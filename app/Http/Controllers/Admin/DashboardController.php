<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EncodingJob;
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
        $runningChannels = LiveChannel::whereIn('status', ['running', 'live'])->count();
        $errorChannels   = LiveChannel::where('status', 'error')->count();
        $idleChannels    = LiveChannel::whereIn('status', ['idle', 'stopped'])->count();

        // playlist_items schema has evolved; count items via either FK to avoid showing 0 for legacy rows.
        $playlistHasVodChannelId = false;
        try {
            $playlistHasVodChannelId = DB::getSchemaBuilder()->hasTable('playlist_items')
                && DB::getSchemaBuilder()->hasColumn('playlist_items', 'vod_channel_id');
        } catch (\Throwable $e) {
            $playlistHasVodChannelId = false;
        }

        // Recent channels for quick status view
        $recentChannels = LiveChannel::query()
            ->select([
                'id',
                'name',
                'status',
                'enabled',
                'resolution',
                'video_bitrate',
                'fps',
                'updated_at',
                'logo_path',
                'started_at',
                'encoded_output_path',
                'hls_output_path',
            ])
            ->selectSub(function ($q) use ($playlistHasVodChannelId) {
                $q->from('playlist_items')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('playlist_items.live_channel_id', 'live_channels.id');
                if ($playlistHasVodChannelId) {
                    $q->orWhereColumn('playlist_items.vod_channel_id', 'live_channels.id');
                }
            }, 'playlist_items_count')
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
        $nowEncodingJobs = collect();
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
                // Be tolerant to multiple status vocabularies used across the app.
                $jobsStats['queued']  = DB::table('encoding_jobs')->whereIn('status', ['queued', 'pending'])->count();
                $jobsStats['running'] = DB::table('encoding_jobs')->whereIn('status', ['running', 'processing'])->count();
                $jobsStats['failed']  = DB::table('encoding_jobs')->where('status', 'failed')->count();

                $jobs = DB::table('encoding_jobs')
                    ->orderByDesc('id')
                    ->limit(8)
                    ->get(['id', 'vod_channel_id', 'status', 'progress', 'created_at', 'updated_at']);

                $nowEncodingJobs = EncodingJob::with([
                        'channel:id,name',
                        'video:id,title',
                    ])
                    ->whereIn('status', ['running', 'processing'])
                    ->orderByDesc('updated_at')
                    ->limit(8)
                    ->get();
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

        $systemState = 'ok';
        if (count($alertSummary['critical']) > 0) {
            $systemState = 'critical';
        } elseif (count($alertSummary['warning']) > 0) {
            $systemState = 'warning';
        }

        $systemSummaryText = count($alertSummary['critical']) . ' critical, ' . count($alertSummary['warning']) . ' warning';

        // ===== SYSTEM METRICS (REAL DATA) =====
        $cpuUsage = SystemMonitorService::getCpuUsage();
        $ramUsage = SystemMonitorService::getSystemMemoryUsage();
        $networkStats = SystemMonitorService::getNetworkStats();
        $uptime = SystemMonitorService::getUptime();
        $diskStats = SystemMonitorService::getDiskSpace();

        $loadAvg = @sys_getloadavg();
        $load1 = is_array($loadAvg) && isset($loadAvg[0]) ? (float) $loadAvg[0] : null;
        $cores = 1;
        try {
            $cores = (int) trim((string) @shell_exec('nproc 2>/dev/null')) ?: 1;
        } catch (\Throwable $e) {
            $cores = 1;
        }

        // Channels needing attention (dashboard table)
        $channelsNeedingAttention = LiveChannel::query()
            ->select(['id', 'name', 'status', 'enabled', 'logo_path', 'encoded_output_path', 'hls_output_path', 'updated_at'])
            ->where(function ($q) {
                $q->where('enabled', 0)
                    ->orWhere('status', 'error')
                    ->orWhereNull('encoded_output_path')
                    ->orWhere('encoded_output_path', '')
                    ->orWhereNull('hls_output_path')
                    ->orWhere('hls_output_path', '');
            })
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $quickLinks = [
            ['label' => 'VOD Channels', 'url' => route('vod-channels.index')],
            ['label' => 'Encoding Jobs', 'url' => route('encoding-jobs.index')],
            ['label' => 'Import Media', 'url' => route('media.import')],
            ['label' => 'Video Categories', 'url' => route('video-categories.index')],
            ['label' => 'TMDb Settings', 'url' => route('settings.tmdb')],
        ];

        // Health summary rows with drill-down "Fix" links
        $healthRows = [];

        if (is_numeric($diskUsedPct) && $diskUsedPct >= 90) {
            $healthRows[] = [
                'severity' => 'critical',
                'issue' => "Disk usage at {$diskUsedPct}%",
                'impact' => 'Encoding/streaming may become unstable due to low free space.',
                'action_label' => 'Open File Browser',
                'action_url' => route('file-browser.index'),
            ];
        } elseif (is_numeric($diskUsedPct) && $diskUsedPct >= 75) {
            $healthRows[] = [
                'severity' => 'warning',
                'issue' => "Disk usage at {$diskUsedPct}%",
                'impact' => 'Consider freeing space to avoid future failures.',
                'action_label' => 'Open File Browser',
                'action_url' => route('file-browser.index'),
            ];
        }

        if ($channelsMissingOutput > 0) {
            $healthRows[] = [
                'severity' => 'critical',
                'issue' => "{$channelsMissingOutput} channels missing outputs",
                'impact' => 'Streams may not start (missing HLS/TS output paths).',
                'action_label' => 'View affected channels',
                'action_url' => route('vod-channels.index', ['filter' => 'missing-outputs']),
            ];
        }

        if ($failedJobs > 0) {
            $healthRows[] = [
                'severity' => 'critical',
                'issue' => "{$failedJobs} failed encoding jobs",
                'impact' => 'Content remains unencoded until jobs are retried/fixed.',
                'action_label' => 'Open failed jobs',
                'action_url' => route('encoding-jobs.index', ['status' => 'failed']),
            ];
        }

        if ($channelsMissingLogo > 0) {
            $healthRows[] = [
                'severity' => 'warning',
                'issue' => "{$channelsMissingLogo} channels missing logo",
                'impact' => 'Branding is incomplete for those channels.',
                'action_label' => 'View affected channels',
                'action_url' => route('vod-channels.index', ['filter' => 'missing-logo']),
            ];
        }

        if ($runningChannels === 0 && $totalChannels > 0) {
            $healthRows[] = [
                'severity' => 'warning',
                'issue' => 'No channels running',
                'impact' => 'Users will not have any live streams available.',
                'action_label' => 'View channels',
                'action_url' => route('vod-channels.index'),
            ];
        }

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
            'systemState'          => $systemState,
            'systemSummaryText'    => $systemSummaryText,
            'quickLinks'           => $quickLinks,
            'healthRows'           => $healthRows,
            'channelsNeedingAttention' => $channelsNeedingAttention,
            'nowEncodingJobs'      => $nowEncodingJobs,
            // System metrics
            'cpuUsage'             => $cpuUsage,
            'ramUsage'             => $ramUsage,
            'networkStats'         => $networkStats,
            'uptime'               => $uptime,
            'diskStats'            => $diskStats,
            'load1'                => $load1,
            'cores'                => $cores,
        ]);
    }
}
