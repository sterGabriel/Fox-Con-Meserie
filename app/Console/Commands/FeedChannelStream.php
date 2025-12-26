<?php

namespace App\Console\Commands;

use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use Illuminate\Console\Command;

class FeedChannelStream extends Command
{
    protected $signature = 'channel:feed-stream {channel : LiveChannel id} {--sleep=3 : Sleep seconds when no items are TS-ready}';

    protected $description = 'Feeds a channel TS stream FIFO by concatenating TS files in playlist order, looping forever and picking up new TS-ready items at the end of each cycle.';

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

        $fifoPath = $outputDir . '/play_stream.fifo';
        if (!file_exists($fifoPath)) {
            $this->error('FIFO not found: ' . $fifoPath);
            $this->error('Start the channel in FIFO stream mode first.');
            return self::FAILURE;
        }

        $this->info('Feeding stream FIFO: ' . $fifoPath);

        // Open FIFO for writing. This will block until FFmpeg opens it for reading.
        $out = @fopen($fifoPath, 'wb');
        if (!is_resource($out)) {
            $this->error('Failed to open FIFO for writing.');
            return self::FAILURE;
        }
        stream_set_write_buffer($out, 0);

        $lastPathCount = null;

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

            if (empty($paths)) {
                $this->line('No TS-ready items yet; waiting...');
                sleep($sleep);
                continue;
            }

            if ($lastPathCount !== count($paths)) {
                $this->line('Stream cycle (files: ' . count($paths) . ')');
                $lastPathCount = count($paths);
            }

            foreach ($paths as $path) {
                $in = @fopen($path, 'rb');
                if (!is_resource($in)) {
                    continue;
                }

                while (!feof($in)) {
                    $chunk = @fread($in, 1024 * 1024);
                    if ($chunk === false || $chunk === '') {
                        break;
                    }

                    $written = @fwrite($out, $chunk);
                    @fflush($out);

                    if ($written === false) {
                        $this->error('Write failed. FFmpeg may have stopped reading the FIFO.');
                        @fclose($in);
                        @fclose($out);
                        return self::FAILURE;
                    }
                }

                @fclose($in);
            }
        }
    }
}
