<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveChannel;
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
        ]);
    }
}
