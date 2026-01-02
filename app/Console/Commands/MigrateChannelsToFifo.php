<?php

namespace App\Console\Commands;

use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use App\Services\ChannelEngineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class MigrateChannelsToFifo extends Command
{
    protected $signature = 'channels:migrate-to-fifo
        {--only-live : Only channels currently marked live (default)}
        {--all-enabled : Consider all enabled channels (ignores status)}
        {--dry-run : Print actions without stopping/starting}
        {--limit= : Limit number of channels processed}
        {--channel= : Only process a single channel id}
        {--force : Restart even if not detected as concat mode}
        {--yes : Do not prompt for confirmation}';

    protected $description = 'Restart legacy channels running concat playback into FIFO playback so new encoded TS items append automatically without restart.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyLive = (bool) ($this->option('only-live') ?? true);
        $allEnabled = (bool) $this->option('all-enabled');
        $force = (bool) $this->option('force');
        $yes = (bool) $this->option('yes');
        $limit = $this->option('limit');
        $channelOnly = $this->option('channel');

        $query = LiveChannel::query()
            ->when($allEnabled, fn ($q) => $q->where('enabled', true))
            ->when(!$allEnabled && $onlyLive, fn ($q) => $q->where('status', 'live'))
            ->orderBy('id');

        if ($channelOnly !== null && is_numeric($channelOnly)) {
            $query->where('id', (int) $channelOnly);
        }

        if (is_numeric($limit) && (int) $limit > 0) {
            $query->limit((int) $limit);
        }

        $channels = $query->get();
        if ($channels->isEmpty()) {
            $this->line('No channels to process.');
            return self::SUCCESS;
        }

        $this->line('Scanning channels: ' . $channels->count());

        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        // First pass: decide what would be migrated.
        $actions = [];

        foreach ($channels as $channel) {
            $lock = Cache::lock('channels:migrate-to-fifo:' . (int) $channel->id, 55);
            if (!$lock->get()) {
                $skipped++;
                continue;
            }

            try {
                $engine = new ChannelEngineService($channel);

                // Only meaningful when the channel has TS-ready playlist items.
                $encodedCount = $this->countEncodedTsForPlaylist($channel);
                if ($encodedCount <= 0) {
                    $skipped++;
                    continue;
                }

                $args = $this->detectRunningFfmpegArgs($engine);
                $isFifo = $args !== null && str_contains($args, $this->streamFifoPathFor($channel));
                $looksConcat = $args !== null && (
                    str_contains($args, $this->playPlaylistPathFor($channel))
                    || (str_contains($args, ' -f concat') && str_contains($args, '/streams/' . (int) $channel->id . '/'))
                );

                if ($isFifo) {
                    $skipped++;
                    continue;
                }

                if (!$force && !$looksConcat) {
                    $skipped++;
                    continue;
                }

                $actions[] = [
                    'channel' => $channel,
                    'encoded' => $encodedCount,
                ];

            } catch (\Throwable $e) {
                $this->error("FAIL #{$channel->id} {$channel->name}: {$e->getMessage()}");
                $failed++;
            } finally {
                try { $lock->release(); } catch (\Throwable $e) { /* ignore */ }
            }
        }

        if (empty($actions)) {
            $this->line("Done. migrated=0 skipped={$skipped} failed={$failed}");
            return $failed > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->line('Will migrate to FIFO: ' . count($actions));
        foreach ($actions as $a) {
            /** @var LiveChannel $ch */
            $ch = $a['channel'];
            $this->line("- #{$ch->id} {$ch->name} (encoded={$a['encoded']})");
        }

        if ($dryRun) {
            $this->info('Dry-run complete. No changes were made.');
            $this->line("Summary. migrated=0 skipped={$skipped} failed={$failed}");
            return $failed > 0 ? self::FAILURE : self::SUCCESS;
        }

        if (!$yes) {
            if (!$this->confirm('This will STOP and START the channels listed above. Continue?', true)) {
                $this->warn('Aborted.');
                return self::FAILURE;
            }
        }

        // Second pass: perform migrations with progress bar.
        $this->newLine();
        $this->line('Migrating...');

        $this->withProgressBar($actions, function (array $a) use (&$migrated, &$failed) {
            /** @var LiveChannel $channel */
            $channel = $a['channel'];
            try {
                $engine = new ChannelEngineService($channel);
                $cmd = $engine->generatePlayCommandFromFilesFifo();

                $stop = $engine->stop();
                if (($stop['status'] ?? '') !== 'success') {
                    $failed++;
                    return;
                }

                $start = $engine->start($cmd);
                if (($start['status'] ?? '') === 'success') {
                    $migrated++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
            }
        });

        $this->newLine(2);

        $this->line("Done. migrated={$migrated} skipped={$skipped} failed={$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function streamFifoPathFor(LiveChannel $channel): string
    {
        return storage_path('app/streams/' . (int) $channel->id . '/play_stream.fifo');
    }

    protected function playPlaylistPathFor(LiveChannel $channel): string
    {
        return storage_path('app/streams/' . (int) $channel->id . '/play_playlist.txt');
    }

    protected function countEncodedTsForPlaylist(LiveChannel $channel): int
    {
        $items = PlaylistItem::query()
            ->where(function ($q) use ($channel) {
                $q->where('live_channel_id', $channel->id)
                    ->orWhere('vod_channel_id', $channel->id);
            })
            ->orderBy('sort_order')
            ->get(['id', 'video_id']);

        if ($items->isEmpty()) {
            return 0;
        }

        $outputDir = storage_path('app/streams/' . (int) $channel->id);
        $count = 0;

        foreach ($items as $item) {
            $primary = $outputDir . '/video_' . (int) $item->id . '.ts';
            $fallback = $outputDir . '/video_' . (int) ($item->video_id ?? 0) . '.ts';
            if (is_file($primary) || ((int) ($item->video_id ?? 0) > 0 && is_file($fallback))) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Best-effort detection of the running ffmpeg args for a channel.
     * Returns full args string or null.
     */
    protected function detectRunningFfmpegArgs(ChannelEngineService $engine): ?string
    {
        try {
            $p = new Process(['ps', '-eo', 'pid=,args=']);
            $p->setTimeout(2);
            $p->run();
            if (!$p->isSuccessful()) {
                return null;
            }

            $needle = $this->outputDirForEngine($engine);
            $lines = preg_split('/\r?\n/', (string) $p->getOutput()) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                if (stripos($line, 'ffmpeg') === false) continue;
                if (strpos($line, $needle) === false) continue;

                if (
                    strpos($line, $needle . '/stream.ts') === false
                    && strpos($line, $needle . '/hls/stream.m3u8') === false
                    && strpos($line, $needle . '/play_stream.fifo') === false
                ) {
                    continue;
                }

                return $line;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    protected function outputDirForEngine(ChannelEngineService $engine): string
    {
        // Mirror ChannelEngineService outputDir without accessing protected props.
        // engine is channel-scoped, so infer from channel id in its log path.
        // Safer: use reflection as a last resort.
        try {
            $r = new \ReflectionClass($engine);
            if ($r->hasProperty('outputDir')) {
                $p = $r->getProperty('outputDir');
                $p->setAccessible(true);
                $v = (string) $p->getValue($engine);
                if ($v !== '') {
                    return $v;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return storage_path('app/streams');
    }
}
