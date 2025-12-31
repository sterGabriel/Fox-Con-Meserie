<?php

namespace App\Observers;

use App\Models\Video;

class VideoObserver
{
    /**
     * Handle the Video "created" event.
     */
    public function created(Video $video): void
    {
        if (!$video->resolution || !$video->duration_seconds || !$video->size_bytes || !$video->bitrate_kbps || !$video->format) {
            $this->syncMetadata($video);
        }
    }

    /**
     * Handle the Video "updated" event.
     */
    public function updated(Video $video): void
    {
        if (!$video->resolution || !$video->duration_seconds || !$video->size_bytes || !$video->bitrate_kbps || !$video->format) {
            $this->syncMetadata($video);
        }
    }

    /**
     * Extract metadata from video file
     */
    private function syncMetadata(Video $video): void
    {
        $filePath = $video->file_path;

        if (!file_exists($filePath)) {
            return;
        }

        // Get file size
        $sizeBytes = filesize($filePath);

        // Get resolution
        $resolutionCmd = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 2>/dev/null " . escapeshellarg($filePath);
        $resolution = trim((string) shell_exec($resolutionCmd)) ?: '1280x720';

        // Get duration
        $durationCmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 2>/dev/null " . escapeshellarg($filePath);
        $durationOutput = trim((string) shell_exec($durationCmd));
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

        // Update without triggering observer again
        Video::withoutEvents(function () use ($video, $sizeBytes, $resolution, $duration, $bitrateKbps, $formatName) {
            $video->update([
                'size_bytes' => $sizeBytes,
                'resolution' => $resolution,
                'duration_seconds' => $duration,
                'bitrate_kbps' => $bitrateKbps,
                'format' => $formatName,
            ]);
        });
    }
}
