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
        if (!$video->resolution || !$video->duration_seconds || !$video->size_bytes) {
            $this->syncMetadata($video);
        }
    }

    /**
     * Handle the Video "updated" event.
     */
    public function updated(Video $video): void
    {
        if (!$video->resolution || !$video->duration_seconds || !$video->size_bytes) {
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
        $resolution = trim(shell_exec($resolutionCmd)) ?: '1280x720';

        // Get duration
        $durationCmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 2>/dev/null " . escapeshellarg($filePath);
        $durationOutput = trim(shell_exec($durationCmd));
        $duration = $durationOutput ? (int)$durationOutput : 0;

        // Update without triggering observer again
        Video::withoutEvents(function () use ($video, $sizeBytes, $resolution, $duration) {
            $video->update([
                'size_bytes' => $sizeBytes,
                'resolution' => $resolution,
                'duration_seconds' => $duration,
            ]);
        });
    }
}
