<?php

namespace App\Console\Commands;

use App\Models\EncodingJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DedupeEncodingJobs extends Command
{
    protected $signature = 'encoding:dedupe-jobs {channelId : LiveChannel id} {--force : Actually delete rows/files}';

    protected $description = 'Deduplicate encoding jobs per playlist item for a channel (keeps the best/latest job and removes duplicates).';

    public function handle(): int
    {
        $channelId = (int) $this->argument('channelId');
        if ($channelId <= 0) {
            $this->error('Invalid channelId');
            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        $jobs = EncodingJob::query()
            ->where(function ($q) use ($channelId) {
                $q->where('live_channel_id', $channelId)
                  ->orWhere('channel_id', $channelId);
            })
            ->where('video_id', '>', 0)
            ->whereNotNull('playlist_item_id')
            ->where('playlist_item_id', '>', 0)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'playlist_item_id', 'status', 'output_path', 'settings', 'created_at']);

        if ($jobs->isEmpty()) {
            $this->info('No playlist-linked encoding jobs found for this channel.');
            return self::SUCCESS;
        }

        $byPlaylistItem = $jobs->groupBy(fn ($j) => (int) $j->playlist_item_id);

        $deleteJobIds = [];
        $keepJobIds = [];

        foreach ($byPlaylistItem as $playlistItemId => $rows) {
            $rows = $rows->values();

            if ($rows->count() <= 1) {
                $keepJobIds[] = (int) $rows[0]->id;
                continue;
            }

            $pick = function () use ($rows): ?EncodingJob {
                // Prefer a DONE job that actually has output on disk.
                foreach ($rows as $r) {
                    $status = strtolower((string) ($r->status ?? ''));
                    $out = (string) ($r->output_path ?? '');
                    if ($status === 'done' && $out !== '' && is_file($out)) {
                        return $r;
                    }
                }

                // Otherwise prefer running/queued (keep newest because $rows are already desc).
                foreach ($rows as $r) {
                    $status = strtolower((string) ($r->status ?? ''));
                    if (in_array($status, ['running', 'queued'], true)) {
                        return $r;
                    }
                }

                // Fallback: keep newest row.
                return $rows[0] ?? null;
            };

            $keep = $pick();
            if (!$keep) {
                continue;
            }

            $keepId = (int) $keep->id;
            $keepJobIds[] = $keepId;

            foreach ($rows as $r) {
                $id = (int) $r->id;
                if ($id === $keepId) continue;

                // Never delete a running job (safety).
                $status = strtolower((string) ($r->status ?? ''));
                if ($status === 'running') {
                    continue;
                }

                $deleteJobIds[] = $id;
            }
        }

        $deleteJobIds = array_values(array_unique(array_filter($deleteJobIds)));
        $keepJobIds = array_values(array_unique(array_filter($keepJobIds)));

        $this->info('Keep jobs: ' . count($keepJobIds));
        $this->info('Delete duplicates: ' . count($deleteJobIds));

        if (!$force) {
            $this->warn('Dry run only. Re-run with --force to delete.');
            return self::SUCCESS;
        }

        $progressDir = storage_path('app/encoding_progress');

        DB::transaction(function () use ($deleteJobIds, $progressDir) {
            foreach ($deleteJobIds as $jobId) {
                $jobId = (int) $jobId;
                if ($jobId <= 0) continue;

                // Best-effort: remove progress + log files.
                $p = $progressDir . '/job_' . $jobId . '.txt';
                if (is_file($p)) {
                    @unlink($p);
                }

                $l = storage_path('logs/encoding_job_' . $jobId . '.log');
                if (is_file($l)) {
                    @unlink($l);
                }

                EncodingJob::query()->whereKey($jobId)->delete();
            }
        });

        $this->info('Done.');
        return self::SUCCESS;
    }
}
