<?php

namespace App\Services;

use App\Models\LiveChannel;
use App\Models\EncodingJob;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

/**
 * EncodingService
 * Handles offline encoding of videos to TS with overlay
 * Creates TS files with overlay baked in (not applied at playback)
 */
class EncodingService
{
    protected EncodingJob $job;
    protected LiveChannel $channel;
    protected string $logPath;

    public function __construct(EncodingJob $job, LiveChannel $channel)
    {
        $this->job = $job;
        $this->channel = $channel;
        $this->logPath = storage_path("logs/encoding_job_{$job->id}.log");
        @mkdir(dirname($this->logPath), 0755, true);
    }

    /**
     * Encode single video to TS with overlay
     * Runs FFmpeg process to:
     * 1. Read input video
     * 2. Apply overlay filters (logo, text, timer)
     * 3. Encode to H.264 + AAC
     * 4. Output as MPEGTS (.ts file)
     */
    public function encode(): array
    {
        try {
            // Build FFmpeg command
            $command = $this->buildEncodeCommand();

            $this->appendLog("Starting encoding...");
            $this->appendLog("Input: {$this->job->input_path}");
            $this->appendLog("Output: {$this->job->output_path}");
            $this->appendLog("Command: $command");

            // Create and start process
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(null); // No timeout
            $process->setIdleTimeout(null);

            // Update job: running
            $this->job->update([
                'status' => 'running',
                'started_at' => Carbon::now(),
                'pid' => null, // Will get from process
                'log_path' => $this->logPath,
            ]);

            // Run synchronously (blocking) - encoding takes time anyway
            $process->run(function($type, $buffer) {
                $this->appendLog($buffer);
                // Could update progress here from ffmpeg stderr
            });

            // Check if successful
            if (!$process->isSuccessful()) {
                $error = $process->getErrorOutput();
                $this->job->update([
                    'status' => 'failed',
                    'error_message' => $error,
                    'finished_at' => Carbon::now(),
                ]);

                $this->appendLog("❌ Encoding failed: $error");
                Log::error("Encoding job {$this->job->id} failed: $error");

                return [
                    'status' => 'failed',
                    'message' => $error,
                ];
            }

            // Verify output file exists
            if (!file_exists($this->job->output_path)) {
                $this->job->update([
                    'status' => 'failed',
                    'error_message' => 'Output file not created',
                    'finished_at' => Carbon::now(),
                ]);

                $this->appendLog("❌ Output file not created");
                return [
                    'status' => 'failed',
                    'message' => 'Output file not created',
                ];
            }

            // Success
            $fileSize = filesize($this->job->output_path);
            
            $this->job->update([
                'status' => 'done',
                'finished_at' => Carbon::now(),
                'completed_at' => Carbon::now(),
                'progress' => 100,
            ]);

            $this->appendLog("✅ Encoding completed successfully");
            $this->appendLog("Output size: " . $this->formatBytes($fileSize));

            Log::info("Encoding job {$this->job->id} completed. File: {$this->job->output_path}");

            return [
                'status' => 'success',
                'output_path' => $this->job->output_path,
                'file_size' => $fileSize,
            ];

        } catch (\Exception $e) {
            $this->job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => Carbon::now(),
            ]);

            $this->appendLog("❌ Exception: {$e->getMessage()}");
            Log::error("Encoding job {$this->job->id} exception: {$e->getMessage()}");

            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build FFmpeg command for encoding with overlay
     */
    protected function buildEncodeCommand(): string
    {
        // Get profile for encoding settings
        $profile = $this->channel->encodeProfile;
        if (!$profile) {
            $profile = \App\Models\EncodeProfile::where('mode', 'LIVE')
                ->where('container', 'mpegts')
                ->first();
        }

        // Build filter complex for overlay
        $filterComplex = $this->buildFilterComplex();

        // Base command
        $cmd = [
            'ffmpeg',
            '-i', escapeshellarg($this->job->input_path),
        ];

        // Add filter if overlay is enabled
        if (!empty($filterComplex)) {
            $cmd = array_merge($cmd, [
                '-filter_complex', escapeshellarg($filterComplex),
                '-map', '[out]',  // Use filter output
            ]);
        } else {
            $cmd = array_merge($cmd, ['-map', '0:v', '-map', '0:a']);
        }

        // Encoding settings
        $cmd = array_merge($cmd, [
            '-c:v', $profile->codec ?? 'libx264',
            '-preset', $profile->preset ?? 'medium',
            '-b:v', ($profile->video_bitrate ?? 1500) . 'k',
            '-maxrate', ($profile->maxrate ?? 1875) . 'k',
            '-bufsize', ($profile->bufsize ?? 3750) . 'k',
            '-c:a', $profile->audio_codec ?? 'aac',
            '-b:a', ($profile->audio_bitrate ?? 128) . 'k',
            '-ar', $profile->audio_rate ?? 48000,
            '-ac', $profile->channels ?? 2,
        ]);

        // Output as MPEGTS
        $cmd = array_merge($cmd, [
            '-f', 'mpegts',
            '-y',  // Overwrite
            escapeshellarg($this->job->output_path),
        ]);

        return implode(' ', $cmd);
    }

    /**
     * Build filter complex for overlay (logo, text, timer)
     */
    protected function buildFilterComplex(): string
    {
        $filters = [];

        // Start with scale/pad for consistent output
        $filters[] = "[0:v]scale=1920:1080:force_original_aspect_ratio=decrease:force_divisible_by=2[scaled]";
        $filters[] = "[scaled]pad=1920:1080:(ow-iw)/2:(oh-ih)/2[padded]";
        $lastLabel = '[padded]';

        // Add text overlay if enabled
        if ($this->channel->overlay_text_enabled) {
            $text = $this->channel->overlay_text_content ?? 'LIVE';
            $x = $this->channel->overlay_text_x ?? 20;
            $y = $this->channel->overlay_text_y ?? 20;
            $fontSize = $this->channel->overlay_text_font_size ?? 24;
            $color = $this->channel->overlay_text_color ?? 'white';

            $filters[] = "{$lastLabel}drawtext=text='{$text}':x={$x}:y={$y}:fontsize={$fontSize}:fontcolor={$color}[txt]";
            $lastLabel = '[txt]';
        }

        // Add timer if enabled
        if ($this->channel->overlay_timer_enabled) {
            $timerX = $this->channel->overlay_timer_x ?? 20;
            $timerY = $this->channel->overlay_timer_y ?? 50;
            $filters[] = "{$lastLabel}drawtext=text='%{pts\\:hms}':x={$timerX}:y={$timerY}:fontsize=20:fontcolor=white[timer]";
            $lastLabel = '[timer]';
        }

        // Final output
        if ($lastLabel !== '[padded]') {
            $filters[] = "{$lastLabel}[out]";
            return implode(';', $filters);
        }

        return '';
    }

    /**
     * Log message to file
     */
    protected function appendLog(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($this->logPath, $logMessage, FILE_APPEND);
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
