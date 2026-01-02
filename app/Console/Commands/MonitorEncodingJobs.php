<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EncodingJob;
use App\Models\LiveChannel;
use App\Services\EncodingService;
use Carbon\Carbon;

class MonitorEncodingJobs extends Command
{
    protected $signature = 'encoding:monitor';
    protected $description = 'Monitor running encoding jobs and update status when complete';

    public function handle()
    {
        $now = Carbon::now();

        $isPidAlive = function ($pid): bool {
            $p = (int) ($pid ?? 0);
            if ($p <= 0) return false;
            if (function_exists('posix_kill')) {
                return @posix_kill($p, 0);
            }
            return is_dir('/proc/' . $p);
        };

        $parseProgressFile = function (?string $path): array {
            $out = ['progress' => null];
            $p = trim((string) ($path ?? ''));
            if ($p === '' || !is_file($p)) return $out;
            $data = @file_get_contents($p);
            if (!is_string($data) || $data === '') return $out;
            if (preg_match_all('/^progress=([^\r\n]+)$/m', $data, $m) && !empty($m[1])) {
                $out['progress'] = (string) end($m[1]);
            }
            return $out;
        };

        // 1) Mark running jobs done/failed when appropriate.
        $jobs = EncodingJob::query()->where('status', 'running')->get();
        $touchedChannelIds = [];

        foreach ($jobs as $job) {
            $channelId = (int) ($job->live_channel_id ?? $job->channel_id ?? 0);
            if ($channelId > 0) {
                $touchedChannelIds[$channelId] = true;
            }

            $outputPath = (string) ($job->output_path ?? '');
            $hasOutput = $outputPath !== '' && is_file($outputPath);
            $fileSize = $hasOutput ? ((int) @filesize($outputPath)) : 0;

            $isHls = $hasOutput && str_ends_with(strtolower($outputPath), '.m3u8');
            $hlsEnded = false;
            if ($isHls) {
                $c = @file_get_contents($outputPath);
                $hlsEnded = is_string($c) && str_contains($c, '#EXT-X-ENDLIST');
            }

            $pid = (int) ($job->pid ?? 0);
            $alive = $isPidAlive($pid);

            $settings = $job->settings;
            if (!is_array($settings)) $settings = [];
            $progressFile = $settings['_progress_file'] ?? null;
            $progressMeta = $parseProgressFile(is_string($progressFile) ? $progressFile : null);
            $ffmpegEnded = ($progressMeta['progress'] === 'end');

            // Completed:
            // - ffmpeg progress indicates end and output exists, OR
            // - process is dead and output exists (and is non-trivial)
            if (($ffmpegEnded && $hasOutput) || (!$alive && $hasOutput && $fileSize > 1048576)) {
                $job->update([
                    'status' => 'done',
                    'progress' => 100,
                    'finished_at' => $now,
                    'completed_at' => $now,
                    'error_message' => null,
                ]);

                $this->info("✅ Job {$job->id} completed" . ($hasOutput ? " - {$fileSize} bytes" : ''));
                continue;
            }

            // HLS test outputs can be small (m3u8), so also accept a finalized playlist.
            if (($hlsEnded && $hasOutput) || (!$alive && $isHls && $hasOutput)) {
                $job->update([
                    'status' => 'done',
                    'progress' => 100,
                    'finished_at' => $now,
                    'completed_at' => $now,
                    'error_message' => null,
                ]);

                $this->info("✅ Job {$job->id} completed (HLS)");
                continue;
            }

            // Failed:
            // - process is dead and output missing, or
            // - output missing for too long after start
            if (!$alive && !$hasOutput && $job->started_at) {
                $job->update([
                    'status' => 'failed',
                    'error_message' => 'FFmpeg exited without producing output',
                    'finished_at' => $now,
                    'completed_at' => $now,
                ]);
                $this->error("❌ Job {$job->id} failed (no output) ");
                continue;
            }

            if (!$hasOutput && $job->started_at && $job->started_at->diffInMinutes($now) > 10) {
                $job->update([
                    'status' => 'failed',
                    'error_message' => 'Output file not created within 10 minutes',
                    'finished_at' => $now,
                    'completed_at' => $now,
                ]);
                $this->error("❌ Job {$job->id} timed out");
                continue;
            }
        }

        // 2) Start the next queued job for channels that are idle.
        $channelIdsWithQueued = EncodingJob::query()
            ->where('status', 'queued')
            ->where('video_id', '>', 0)
            ->get(['live_channel_id', 'channel_id'])
            ->flatMap(function ($row) {
                $out = [];
                $a = (int) ($row->live_channel_id ?? 0);
                $b = (int) ($row->channel_id ?? 0);
                if ($a > 0) $out[] = $a;
                if ($b > 0) $out[] = $b;
                return $out;
            })
            ->unique()
            ->values()
            ->all();

        foreach ($channelIdsWithQueued as $channelId) {
            $touchedChannelIds[(int) $channelId] = true;
        }

        foreach (array_keys($touchedChannelIds) as $channelId) {
            $channelId = (int) $channelId;
            if ($channelId <= 0) continue;

            $claimed = null;

            \DB::transaction(function () use ($channelId, $now, &$claimed) {
                $scope = function ($q) use ($channelId) {
                    return $q->where(function ($qq) use ($channelId) {
                        $qq->where('encoding_jobs.live_channel_id', $channelId)
                           ->orWhere('encoding_jobs.channel_id', $channelId);
                    })
                    ->where('encoding_jobs.video_id', '>', 0);
                };

                $hasRunning = $scope(EncodingJob::query())
                    ->where('encoding_jobs.status', 'running')
                    ->lockForUpdate()
                    ->exists();

                if ($hasRunning) {
                    $claimed = null;
                    return;
                }

                // Priority: start short TEST jobs first so the user gets immediate feedback.
                // Otherwise, tests (playlist_item_id=null) can get stuck behind long playlist encodes.
                $next = $scope(EncodingJob::query())
                    ->where('encoding_jobs.status', 'queued')
                    ->whereNull('encoding_jobs.playlist_item_id')
                    ->where('encoding_jobs.settings->job_type', 'test')
                    ->orderBy('encoding_jobs.created_at')
                    ->select('encoding_jobs.*')
                    ->lockForUpdate()
                    ->first();

                if (!$next) {
                    $next = $scope(EncodingJob::query())
                        ->where('encoding_jobs.status', 'queued')
                        ->leftJoin('playlist_items', 'encoding_jobs.playlist_item_id', '=', 'playlist_items.id')
                        ->orderByRaw('CASE WHEN encoding_jobs.playlist_item_id IS NULL THEN 1 ELSE 0 END asc')
                        ->orderByRaw('COALESCE(playlist_items.sort_order, 2147483647) asc')
                        ->orderBy('encoding_jobs.created_at')
                        ->select('encoding_jobs.*')
                        ->lockForUpdate()
                        ->first();
                }

                if (!$next) {
                    $claimed = null;
                    return;
                }

                $next->update([
                    'status' => 'running',
                    'started_at' => $now,
                    'pid' => null,
                    'error_message' => null,
                ]);

                $claimed = $next;
            });

            if ($claimed) {
                $channel = LiveChannel::query()->whereKey($channelId)->first();
                if (!$channel) {
                    $claimed->update([
                        'status' => 'failed',
                        'error_message' => 'Missing channel',
                        'finished_at' => $now,
                        'completed_at' => $now,
                    ]);
                    continue;
                }

                try {
                    (new EncodingService($claimed, $channel))->startAsync();
                    $this->info("▶️ Started next queued job #{$claimed->id} for channel {$channelId}");
                } catch (\Throwable $e) {
                    $claimed->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'finished_at' => $now,
                        'completed_at' => $now,
                    ]);
                    $this->error("❌ Failed starting job #{$claimed->id}: {$e->getMessage()}");
                }
            }
        }
    }
}
