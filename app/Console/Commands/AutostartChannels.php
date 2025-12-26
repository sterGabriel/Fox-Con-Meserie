<?php

namespace App\Console\Commands;

use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use App\Services\ChannelEngineService;
use Illuminate\Console\Command;

class AutostartChannels extends Command
{
    protected $signature = 'channels:autostart
        {--all-enabled : Start all enabled channels (dangerous on large installs)}
        {--only-missing : Only start channels that are not currently running (default)}
        {--dry-run : Print what would be started without starting}
        {--limit= : Limit number of channels processed (for safety)}';

    protected $description = 'Auto-start channels after reboot (restart channels that should be live).';

    public function handle(): int
    {
        $onlyMissing = (bool) ($this->option('only-missing') ?? true);
        $dryRun = (bool) $this->option('dry-run');
        $allEnabled = (bool) $this->option('all-enabled');
        $limit = $this->option('limit');

        $query = LiveChannel::query()
            ->where('enabled', true)
            ->when(!$allEnabled, fn ($q) => $q->where('status', 'live'))
            ->orderBy('id');

        if (is_numeric($limit) && (int) $limit > 0) {
            $query->limit((int) $limit);
        }

        $channels = $query->get();

        if ($channels->isEmpty()) {
            $this->line('No channels to autostart.');
            return self::SUCCESS;
        }

        $this->line('Autostart candidates: ' . $channels->count());

        $started = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($channels as $channel) {
            $engine = new ChannelEngineService($channel);

            // Detect running ffmpeg even if encoder_pid is missing/stale (or signal permission is denied).
            $detectedPid = $engine->detectRunningFfmpegPid();
            if ($detectedPid) {
                $channel->update([
                    'encoder_pid' => $detectedPid,
                    'status' => 'live',
                ]);
            }

            $isRunning = $detectedPid ? true : $engine->isRunning($channel->encoder_pid);

            if ($onlyMissing && $isRunning) {
                $this->line("SKIP #{$channel->id} {$channel->name} (already running)");
                $skipped++;
                continue;
            }

            // Clear stale PID so the UI doesn't show a zombie PID.
            if (!$isRunning && $channel->encoder_pid) {
                $channel->update(['encoder_pid' => null]);
            }

            try {
                $ffmpegCommand = $this->buildStartCommand($channel, $engine);
            } catch (\Throwable $e) {
                $this->error("FAIL #{$channel->id} {$channel->name} (build command): {$e->getMessage()}");
                $failed++;
                continue;
            }

            if ($dryRun) {
                $this->line("DRY-RUN #{$channel->id} {$channel->name}: {$ffmpegCommand}");
                $skipped++;
                continue;
            }

            $result = $engine->start($ffmpegCommand);

            if (($result['status'] ?? '') === 'success') {
                $this->info("STARTED #{$channel->id} {$channel->name} (PID " . ($result['pid'] ?? '?') . ")");
                $started++;
            } else {
                $this->error("FAIL #{$channel->id} {$channel->name}: " . ($result['message'] ?? 'Unknown error'));
                $failed++;
            }
        }

        $this->line("Done. started={$started} skipped={$skipped} failed={$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function buildStartCommand(LiveChannel $channel, ChannelEngineService $engine): string
    {
        $encodedPaths = $this->findEncodedTsPathsForPlaylist($channel);

        if (!empty($encodedPaths)) {
            // Preferred: FIFO playlist streaming (no restart needed when new TS arrives).
            return $engine->generatePlayCommandFromFilesFifo();
        }

        // Fallback: start in encode-loop mode if configured as 24/7.
        if ((bool) ($channel->is_24_7_channel ?? false)) {
            return $engine->generateLoopingCommand(includeOverlay: true);
        }

        // Last resort: encode/play (non-looping) from first playlist item.
        return $engine->generateCommand(includeOverlay: true);
    }

    /**
     * @return array<int, string>
     */
    protected function findEncodedTsPathsForPlaylist(LiveChannel $channel): array
    {
        $playlistItems = PlaylistItem::query()
            ->where(function ($q) use ($channel) {
                $q->where('live_channel_id', $channel->id)
                    ->orWhere('vod_channel_id', $channel->id);
            })
            ->orderBy('sort_order')
            ->get(['id', 'video_id']);

        if ($playlistItems->isEmpty()) {
            return [];
        }

        $outputDir = storage_path("app/streams/{$channel->id}");
        $paths = [];

        foreach ($playlistItems as $item) {
            $primary = $outputDir . '/video_' . (int) $item->id . '.ts';
            $fallback = $outputDir . '/video_' . (int) ($item->video_id ?? 0) . '.ts';

            if (is_file($primary)) {
                $paths[] = $primary;
                continue;
            }

            if (($item->video_id ?? 0) && is_file($fallback)) {
                $paths[] = $fallback;
            }
        }

        return $paths;
    }
}
