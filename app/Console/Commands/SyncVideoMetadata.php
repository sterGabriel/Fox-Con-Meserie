<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;

class SyncVideoMetadata extends Command
{
    protected $signature = 'videos:sync-metadata';
    protected $description = 'Extract and sync video metadata (resolution, duration, size, bitrate, format) from actual files';

    public function handle()
    {
        $videos = Video::whereNull('resolution')
            ->orWhere('duration_seconds', null)
            ->orWhere('size_bytes', null)
            ->orWhere('bitrate_kbps', null)
            ->orWhere('format', null)
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

            // Get container format
            $formatCmd = "ffprobe -v error -show_entries format=format_name -of default=noprint_wrappers=1:nokey=1 2>/dev/null " . escapeshellarg($filePath);
            $formatName = trim((string) shell_exec($formatCmd));
            $formatName = $formatName !== '' ? $formatName : null;

            // Get bitrate (kbps)
            $bitrateCmd = "ffprobe -v error -show_entries format=bit_rate -of default=noprint_wrappers=1:nokey=1 2>/dev/null " . escapeshellarg($filePath);
            $bitrateOutput = trim((string) shell_exec($bitrateCmd));
            $bitrateBps = $bitrateOutput !== '' ? (int) $bitrateOutput : 0;
            $bitrateKbps = $bitrateBps > 0 ? (int) round($bitrateBps / 1000) : null;

            // Update database
            $video->update([
                'size_bytes' => $sizeBytes,
                'resolution' => $resolution,
                'duration_seconds' => $duration,
                'bitrate_kbps' => $bitrateKbps,
                'format' => $formatName,
            ]);

            $this->line("✅ {$video->title}");
            $this->line("   📊 Size: " . round($sizeBytes / (1024*1024)) . " MB | Resolution: $resolution | Duration: {$duration}s | Bitrate: " . ($bitrateKbps ? ($bitrateKbps . " kbps") : "N/A") . " | Format: " . ($formatName ?? 'N/A') . "\n");
        }

        $this->info("\n✅ All videos metadata synced!");
        return 0;
    }
}
