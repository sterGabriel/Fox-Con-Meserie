<?php

namespace App\Console\Commands;

use App\Models\EncodingJob;
use App\Models\LiveChannel;
use Illuminate\Console\Command;

class PurgeChannelTs extends Command
{
    protected $signature = 'channel:purge-ts {channelId : Live/VOD channel id} {--force : Do not ask for confirmation} {--playlist : Also delete ALL playlist items for this channel}';

    protected $description = 'Deletes ALL encoded TS outputs and related TS encoding artifacts (jobs/progress/logs) for a specific channel. Optionally also deletes all playlist items.';

    public function handle(): int
    {
        $channelId = (int) $this->argument('channelId');
        if ($channelId <= 0) {
            $this->error('Invalid channelId');
            return self::FAILURE;
        }

        $channel = LiveChannel::find($channelId);
        if (!$channel) {
            $this->error('Channel not found');
            return self::FAILURE;
        }

        $streamsDir = storage_path('app/streams/' . $channelId);

        $tsFiles = [];
        if (is_dir($streamsDir)) {
            $files = glob($streamsDir . '/*.ts') ?: [];
            foreach ($files as $f) {
                if (is_string($f) && is_file($f)) {
                    $tsFiles[] = $f;
                }
            }
        }

        $jobs = EncodingJob::query()
            ->where(function ($q) use ($channelId) {
                $q->where('live_channel_id', $channelId)
                  ->orWhere('channel_id', $channelId);
            })
            ->where(function ($q) use ($streamsDir, $channelId) {
                $q->whereNotNull('output_path')
                  ->where(function ($q2) use ($streamsDir, $channelId) {
                      $q2->where('output_path', 'like', $streamsDir . '/%.ts')
                         ->orWhere('output_path', 'like', 'streams/' . $channelId . '/%.ts');
                  });
            })
            ->get(['id', 'status', 'output_path', 'pid']);

        $jobIds = $jobs->pluck('id')->map(fn ($v) => (int) $v)->filter()->values()->all();

        $this->info('Channel: #' . $channelId . ' (' . (string) $channel->name . ')');
        $this->info('TS files to delete: ' . count($tsFiles));
        $this->info('TS encoding jobs to delete: ' . count($jobIds));
        $this->info('Delete playlist items: ' . ($this->option('playlist') ? 'YES' : 'NO'));

        if (!$this->option('force')) {
            if (!$this->confirm('This will permanently delete TS outputs and related job artifacts for this channel. Continue?')) {
                $this->warn('Aborted.');
                return self::SUCCESS;
            }
        }

        $deletedTs = 0;
        foreach ($tsFiles as $f) {
            try {
                if (@unlink($f)) {
                    $deletedTs++;
                }
            } catch (\Throwable $e) {
                // ignore and continue
            }
        }

        $killed = 0;
        foreach ($jobs as $job) {
            $pid = (int) ($job->pid ?? 0);
            if ($pid > 0 && function_exists('posix_kill')) {
                // best-effort stop
                @posix_kill($pid, SIGTERM);
                usleep(250000);
                @posix_kill($pid, SIGKILL);
                $killed++;
            }
        }

        $deletedProgress = 0;
        $deletedLogs = 0;
        foreach ($jobIds as $id) {
            $p = storage_path('app/encoding_progress/job_' . $id . '.txt');
            if (is_file($p)) {
                if (@unlink($p)) $deletedProgress++;
            }
            $l = storage_path('logs/encoding_job_' . $id . '.log');
            if (is_file($l)) {
                if (@unlink($l)) $deletedLogs++;
            }
        }

        $deletedJobs = 0;
        foreach ($jobs as $job) {
            try {
                $job->delete();
                $deletedJobs++;
            } catch (\Throwable $e) {
                // ignore and continue
            }
        }

        $deletedPlaylistItems = 0;
        if ($this->option('playlist')) {
            try {
                $deletedPlaylistItems = \DB::table('playlist_items')
                    ->where('live_channel_id', $channelId)
                    ->orWhere('vod_channel_id', $channelId)
                    ->delete();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $this->info('Deleted TS files: ' . $deletedTs);
        $this->info('Stopped processes (best-effort): ' . $killed);
        $this->info('Deleted progress files: ' . $deletedProgress);
        $this->info('Deleted job logs: ' . $deletedLogs);
        $this->info('Deleted TS encoding jobs: ' . $deletedJobs);
        if ($this->option('playlist')) {
            $this->info('Deleted playlist items: ' . $deletedPlaylistItems);
        }

        return self::SUCCESS;
    }
}
