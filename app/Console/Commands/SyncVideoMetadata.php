<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;

class SyncVideoMetadata extends Command
{
    protected $signature = 'videos:sync-metadata';
    protected $description = 'Extract and sync video metadata (resolution, duration, size) from actual files';

    public function handle()
    {
        $videos = Video::whereNull('resolution')
            ->orWhere('duration_seconds', null)
            ->orWhere('size_bytes', null)
            ->get();

        if ($videos->isEmpty()) {
            $this->info('✅ All videos already have metadata!');
            return 0;
        }

        $this->info("🔄 Processing " . $videos->count() . " videos...\n");

        foreach ($videos as $video) {
            $filePath = $video->file_path;

            if (!file_exists($filePath)) {
                $this->warn("❌ File not found: $filePath");
                continue;
            }

            // Get file size
            $sizeBytes = filesize($filePath);

            // Get resolution
            $resolutionCmd = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 2>/dev/null " . escapeshellarg($filePath);
            $resolution = trim(shell_exec($resolutionCmd)) ?: '1280x720';

            // Get duration
            $durationCmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 2>/dev/null " . escapeshellarg($filePath);
            $durationOutput = trim(shell_exec($durationCmd));
            $duration = $durationOutput ? (int)$durationOutput : 0;

            // Update database
            $video->update([
                'size_bytes' => $sizeBytes,
                'resolution' => $resolution,
                'duration_seconds' => $duration,
            ]);

            $this->line("✅ {$video->title}");
            $this->line("   📊 Size: " . round($sizeBytes / (1024*1024)) . " MB | Resolution: $resolution | Duration: {$duration}s\n");
        }

        $this->info("\n✅ All videos metadata synced!");
        return 0;
    }
}
