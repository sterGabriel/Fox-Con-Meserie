<?php

namespace App\Console\Commands;

use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use Illuminate\Console\Command;

class FeedChannelPlaylist extends Command
{
    protected $signature = 'channel:feed-playlist {channel : LiveChannel id} {--sleep=3 : Sleep seconds when no new items}';

    protected $description = 'Feeds a channel playlist FIFO with TS-ready files, looping forever and picking up new encoded items at the end of each cycle.';

    public function handle(): int
    {
        $channelId = (int) $this->argument('channel');
        $sleep = (int) $this->option('sleep');
        if ($sleep < 1) {
            $sleep = 1;
        }

        $channel = LiveChannel::query()->whereKey($channelId)->first();
        if (!$channel) {
            $this->error('Channel not found.');
            return self::FAILURE;
        }

        $outputDir = storage_path('app/streams/' . $channel->id);
        @mkdir($outputDir, 0755, true);

        $fifoPath = $outputDir . '/play_playlist.fifo';
        if (!file_exists($fifoPath)) {
            $this->error('FIFO not found: ' . $fifoPath);
            $this->error('Start the channel in FIFO mode first.');
            return self::FAILURE;
        }

        $this->info('Feeding FIFO: ' . $fifoPath);

        // Open FIFO for writing. This will block until FFmpeg opens it for reading.
        $fh = @fopen($fifoPath, 'w');
        if (!is_resource($fh)) {
            $this->error('Failed to open FIFO for writing.');
            return self::FAILURE;
        }

        stream_set_write_buffer($fh, 0);

        // Provide a concat demuxer header once (more compatible than relying on implicit parsing).
        @fwrite($fh, "ffconcat version 1.0\n");
        @fflush($fh);

        // Keep a simple in-process cache to reduce DB hits.
        $lastItemIds = [];

        while (true) {
            $items = PlaylistItem::query()
                ->where(function ($q) use ($channel) {
                    $q->where('live_channel_id', $channel->id)
                      ->orWhere('vod_channel_id', $channel->id);
                })
                ->orderBy('sort_order')
                ->get(['id', 'video_id']);

            $paths = [];
            foreach ($items as $item) {
                $primary = $outputDir . '/video_' . (int) $item->id . '.ts';
                $fallback = $outputDir . '/video_' . (int) ($item->video_id ?? 0) . '.ts';

                if (is_file($primary)) {
                    $paths[] = $primary;
                } elseif (($item->video_id ?? 0) && is_file($fallback)) {
                    $paths[] = $fallback;
                }
            }

            // If nothing encoded yet, wait and retry (keeps channel alive, but output will have no new input).
            if (empty($paths)) {
                $this->line('No TS-ready items yet; waiting...');
                sleep($sleep);
                continue;
            }

            $itemIdsNow = $items->pluck('id')->map(fn ($v) => (int) $v)->all();
            $changed = $itemIdsNow !== $lastItemIds;
            if ($changed) {
                $this->line('Playlist cycle (items: ' . count($paths) . ')');
                $lastItemIds = $itemIdsNow;
            }

            // Write one full cycle. New files will be picked up at the next cycle.
            foreach ($paths as $path) {
                $escaped = str_replace("'", "'\\''", $path);
                $line = "file '{$escaped}'\n";
                $ok = @fwrite($fh, $line);
                @fflush($fh);

                if ($ok === false) {
                    $this->error('Write failed. FFmpeg may have stopped reading the FIFO.');
                    @fclose($fh);
                    return self::FAILURE;
                }
            }
        }
    }
}
