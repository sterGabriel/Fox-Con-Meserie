<?php

namespace App\Services;

use App\Models\LiveChannel;
use App\Models\EncodingJob;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * ChannelEngineService
 * 
 * Manages ffmpeg process lifecycle for live streaming:
 * - Start/Stop channel encoding
 * - Monitor process status and health
 * - Write logs and manage output files
 * - Handle errors and cleanup
 */
class ChannelEngineService
{
    protected LiveChannel $channel;
    protected ?Process $process = null;
    protected string $logPath;
    protected string $pidFile;
    protected string $outputDir;

    public function __construct(LiveChannel $channel)
    {
        $this->channel = $channel;
        $this->outputDir = storage_path("app/streams/{$channel->id}");
        $this->logPath = storage_path("logs/channel_{$channel->id}.log");
        $this->pidFile = storage_path("app/pids/{$channel->id}.pid");
        
        // Ensure directories exist
        @mkdir($this->outputDir, 0755, true);
        @mkdir(dirname($this->pidFile), 0755, true);
    }

    /**
     * Start channel encoding
     * Builds and executes ffmpeg command with proper output handling
     */
    public function start(string $ffmpegCommand): array
    {
        try {
            // Check if already running
            if ($this->isRunning()) {
                return ['status' => 'error', 'message' => 'Channel already running'];
            }

            // Create encoding job record
            $job = EncodingJob::create([
                'live_channel_id' => $this->channel->id,
                'channel_id' => $this->channel->id,
                'video_id' => 0,  // No specific video for live stream
                'playlist_item_id' => 0,  // No specific playlist item
                'ffmpeg_command' => $ffmpegCommand,
                'status' => 'queued',
                'log_path' => $this->logPath,
                'started_at' => Carbon::now(),
            ]);

            // Prepare process
            $process = Process::fromShellCommandline($ffmpegCommand);
            $process->setTimeout(null); // No timeout for streaming
            $process->setIdleTimeout(null);

            // Start process in background
            $process->start(function($type, $buffer) {
                $this->appendLog($buffer);
            });

            // Save PID
            $this->savePid($process->getPid());

            // Update job
            $job->update([
                'status' => 'running',
                'pid' => $process->getPid(),
            ]);

            // Update channel
            $this->channel->update([
                'status' => 'live',
                'encoder_pid' => $process->getPid(),
                'started_at' => Carbon::now(),
            ]);

            Log::info("Channel {$this->channel->id} started with PID {$process->getPid()}");

            return [
                'status' => 'success',
                'message' => 'Channel started',
                'pid' => $process->getPid(),
                'job_id' => $job->id,
            ];

        } catch (\Exception $e) {
            Log::error("Failed to start channel {$this->channel->id}: {$e->getMessage()}");
            
            $this->channel->update(['status' => 'error']);
            
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Stop channel encoding
     * Graceful shutdown with SIGTERM, then SIGKILL if needed
     */
    public function stop(): array
    {
        try {
            $pid = $this->readPid();

            if (!$pid || !$this->isRunning($pid)) {
                $this->channel->update(['status' => 'idle']);
                return ['status' => 'success', 'message' => 'Channel already stopped'];
            }

            // Send SIGTERM (graceful shutdown)
            posix_kill($pid, SIGTERM);
            
            // Wait up to 5 seconds for graceful shutdown
            $waited = 0;
            while ($this->isRunning($pid) && $waited < 5) {
                usleep(500000); // 0.5 seconds
                $waited++;
            }

            // Force kill if still running
            if ($this->isRunning($pid)) {
                posix_kill($pid, SIGKILL);
                usleep(500000);
                Log::warning("Force killed channel {$this->channel->id} (PID $pid)");
            }

            // Clean up
            $this->deletePid();
            
            // Update channel
            $this->channel->update([
                'status' => 'idle',
                'encoder_pid' => null,
                'started_at' => null,
            ]);

            // Update job
            EncodingJob::where('pid', $pid)
                ->where('status', 'running')
                ->update([
                    'status' => 'done',
                    'ended_at' => Carbon::now(),
                    'exit_code' => 0,
                ]);

            Log::info("Channel {$this->channel->id} stopped");

            return [
                'status' => 'success',
                'message' => 'Channel stopped',
            ];

        } catch (\Exception $e) {
            Log::error("Failed to stop channel {$this->channel->id}: {$e->getMessage()}");
            
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if channel is currently running
     */
    public function isRunning(?int $pid = null): bool
    {
        $pid = $pid ?? $this->readPid();

        if (!$pid) {
            return false;
        }

        // Check if process exists
        return posix_getpid() === $pid || posix_kill($pid, 0);
    }

    /**
     * Get current status
     */
    public function getStatus(): array
    {
        $pid = $this->readPid();
        $isRunning = $this->isRunning($pid);

        return [
            'status' => $isRunning ? 'live' : 'idle',
            'pid' => $isRunning ? $pid : null,
            'is_running' => $isRunning,
            'started_at' => $this->channel->started_at,
            'log_path' => $this->logPath,
        ];
    }

    /**
     * Get recent log lines
     */
    public function getLogTail(int $lines = 100): string
    {
        if (!file_exists($this->logPath)) {
            return "[System] Log file not created yet";
        }

        $file = file_get_contents($this->logPath);
        $logLines = array_filter(explode("\n", $file));
        $tail = array_slice($logLines, -$lines);

        return implode("\n", $tail);
    }

    /**
     * Clear log file
     */
    public function clearLog(): bool
    {
        return file_put_contents($this->logPath, '') !== false;
    }

    /**
     * Download log file
     */
    public function downloadLog(): string
    {
        return $this->logPath;
    }

    /**
     * Append to log file
     */
    protected function appendLog(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($this->logPath, $logMessage, FILE_APPEND);
    }

    /**
     * Save process PID to file
     */
    protected function savePid(int $pid): void
    {
        file_put_contents($this->pidFile, $pid);
    }

    /**
     * Read process PID from file
     */
    protected function readPid(): ?int
    {
        if (!file_exists($this->pidFile)) {
            return null;
        }

        $pid = (int) file_get_contents($this->pidFile);
        return $pid > 0 ? $pid : null;
    }

    /**
     * Delete PID file
     */
    protected function deletePid(): void
    {
        @unlink($this->pidFile);
    }

    /**
     * Generate concat demuxer playlist for 24/7 looping
     * Creates a playlist.txt file that references all channel videos
     * Format: ffmpeg concat demuxer compatible
     */
    public function generateConcatPlaylist(bool $infiniteLoop = true): string
    {
        $playlistItems = $this->channel->playlistItems()
            ->orderBy('sort_order')
            ->get();

        if ($playlistItems->isEmpty()) {
            throw new \Exception('Channel has no videos for concat playlist');
        }

        $playlistPath = "{$this->outputDir}/playlist.txt";
        $playlistContent = "# FFmpeg Concat Demuxer Playlist\n";
        $playlistContent .= "# Generated for channel: {$this->channel->name}\n\n";

        // Add each video to the playlist
        foreach ($playlistItems as $item) {
            $video = $item->video;
            if (file_exists($video->file_path)) {
                // Escape path for concat demuxer
                $escapedPath = str_replace("'", "'\\''", $video->file_path);
                $playlistContent .= "file '{$escapedPath}'\n";
                $playlistContent .= "duration {$video->duration}\n\n";
            }
        }

        // Write playlist file
        file_put_contents($playlistPath, $playlistContent);
        
        $this->appendLog("Generated concat playlist: {$playlistPath}");
        
        return $playlistPath;
    }

    /**
     * Generate FFmpeg command with looping support
     * Uses concat demuxer for seamless 24/7 playback
     */
    public function generateLoopingCommand(bool $includeOverlay = true): string
    {
        // Generate concat playlist
        $playlistPath = $this->generateConcatPlaylist();

        // Build filter complex for overlay
        $filterComplex = $this->buildFilterComplex($includeOverlay);

        // Get encoding profile
        $profile = $this->channel->encodeProfile;
        if (!$profile) {
            // Use default LIVE profile
            $profile = \App\Models\EncodeProfile::where('mode', 'LIVE')
                ->where('container', 'mpegts')
                ->first();
        }

        // Base input and output paths
        $tsOutput = "{$this->outputDir}/stream.ts";

        // Create HLS directory
        @mkdir("{$this->outputDir}/hls", 0755, true);

        // Build FFmpeg command with concat demuxer
        // The concat demuxer automatically loops through the playlist
        $cmd = [
            'ffmpeg',
            '-f', 'concat',        // Use concat demuxer
            '-safe', '0',          // Allow absolute paths
            '-protocol_whitelist', 'file,http,https,tcp,tls,crypto', // Required for concat
            '-i', escapeshellarg($playlistPath), // Input playlist
        ];

        if (!empty($filterComplex)) {
            $cmd = array_merge($cmd, ['-filter_complex', escapeshellarg($filterComplex)]);
        }

        // Codec and encoding settings
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

        // Add outputs: both TS and HLS from single encode
        // First output: MPEGTS stream (for Xtream Codes/streaming)
        $cmd = array_merge($cmd, [
            '-f', 'mpegts',
            '-muxdelay', '0.1',
            '-muxpreload', '0.1',
            escapeshellarg($tsOutput),
        ]);
        
        // Second output: HLS (for browser playback)
        // Need to specify the same input streams again
        $cmd = array_merge($cmd, [
            '-f', 'hls',
            '-hls_time', '10',
            '-hls_list_size', '6',
            '-hls_flags', 'delete_segments',
            '-start_number', '0',
            escapeshellarg("{$this->outputDir}/hls/stream.m3u8"),
        ]);

        return implode(' ', $cmd);
    }

    /**
     * Generate FFmpeg command for channel
     */
    public function generateCommand(bool $includeOverlay = true): string
    {
        $playlist = $this->channel->playlistItems()
            ->orderBy('sort_order')
            ->get();

        if ($playlist->isEmpty()) {
            throw new \Exception('Channel has no videos');
        }

        // Get first video for input
        $firstVideo = $playlist->first()->video;
        $inputFile = $firstVideo->file_path;

        if (!file_exists($inputFile)) {
            throw new \Exception("Video file not found: $inputFile");
        }

        // Get encoding profile
        $profile = $this->channel->encodeProfile;
        if (!$profile) {
            // Use default LIVE profile
            $profile = \App\Models\EncodeProfile::where('mode', 'LIVE')
                ->where('container', 'mpegts')
                ->first();
        }

        // Base input and output paths
        $tsOutput = "{$this->outputDir}/stream.ts";
        $hlsOutput = "{$this->outputDir}/stream.m3u8";

        // Build filter complex for overlay
        $filterComplex = $this->buildFilterComplex($includeOverlay);

        // Build FFmpeg command
        $cmd = [
            'ffmpeg',
            '-re', // Read at input rate (for streaming)
            '-i', escapeshellarg($inputFile),
        ];

        if (!empty($filterComplex)) {
            $cmd = array_merge($cmd, ['-filter_complex', escapeshellarg($filterComplex)]);
        }

        // Codec and encoding settings
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

        // Dual output: MPEGTS + HLS simultaneously
        // Create HLS directory
        @mkdir("{$this->outputDir}/hls", 0755, true);

        // Add outputs: both TS and HLS from single encode
        // First output: MPEGTS stream (for Xtream Codes/streaming)
        $cmd = array_merge($cmd, [
            '-f', 'mpegts',
            '-muxdelay', '0.1',
            '-muxpreload', '0.1',
            escapeshellarg($tsOutput),
        ]);
        
        // Second output: HLS (for browser playback)
        // Need to specify the same input streams again
        $cmd = array_merge($cmd, [
            '-f', 'hls',
            '-hls_time', '10',
            '-hls_list_size', '6',
            '-hls_flags', 'delete_segments',
            '-start_number', '0',
            escapeshellarg("{$this->outputDir}/hls/stream.m3u8"),
        ]);

        return implode(' ', $cmd);
    }

    /**
     * Generate command to PLAY from already-encoded TS files (not re-encode)
     * Uses concat demuxer to play pre-encoded TS files directly
     * This is the "PLAY" phase after "ENCODE" phase
     */
    public function generatePlayCommand(bool $loop = true): string
    {
        // Find all encoded TS files in output directory
        $outputDir = $this->outputDir;
        $encodedFiles = glob("{$outputDir}/video_*.ts");

        if (empty($encodedFiles)) {
            throw new \Exception('No encoded TS files found. Please encode videos first.');
        }

        // Sort by filename to play in order
        sort($encodedFiles);

        // Create concat playlist for the encoded files
        $playlistPath = "{$outputDir}/play_playlist.txt";
        $playlistContent = "# FFmpeg Concat Demuxer Playlist (Pre-Encoded TS Files)\n";

        foreach ($encodedFiles as $file) {
            // Escape path for concat demuxer
            $escapedPath = str_replace("'", "'\\''", $file);
            $playlistContent .= "file '{$escapedPath}'\n";
        }

        file_put_contents($playlistPath, $playlistContent);
        
        $this->appendLog("Generated play playlist: $playlistPath");
        $this->appendLog("Encoded files: " . count($encodedFiles));

        // Build command: play from concat playlist (no re-encoding, just muxing)
        $tsOutput = "{$outputDir}/stream.ts";

        $cmd = [
            'ffmpeg',
            '-f', 'concat',
            '-safe', '0',
            '-protocol_whitelist', 'file,http,https,tcp,tls,crypto',
            '-i', escapeshellarg($playlistPath),
            // NO re-encoding - just copy streams
            '-c:v', 'copy',
            '-c:a', 'copy',
        ];

        // Output 1: MPEGTS stream
        $cmd = array_merge($cmd, [
            '-f', 'mpegts',
            '-muxdelay', '0.1',
            '-muxpreload', '0.1',
            escapeshellarg($tsOutput),
        ]);

        // Output 2: HLS
        @mkdir("{$outputDir}/hls", 0755, true);
        $cmd = array_merge($cmd, [
            '-f', 'hls',
            '-hls_time', '10',
            '-hls_list_size', '6',
            '-hls_flags', 'delete_segments',
            '-start_number', '0',
            escapeshellarg("{$outputDir}/hls/stream.m3u8"),
        ]);

        return implode(' ', $cmd);
    }

    /**
     * Build FFmpeg filter complex string for overlays
     */
    public function buildFilterComplex(bool $includeOverlay): string
    {
        if (!$includeOverlay || (!$this->channel->overlay_logo_enabled && !$this->channel->overlay_text_enabled && !$this->channel->overlay_timer_enabled)) {
            return '';
        }

        $filters = [];

        // Start with scale/pad to ensure consistent output
        $filters[] = "[0:v]scale=1920:1080:force_original_aspect_ratio=decrease:force_divisible_by=2[scaled]";
        $filters[] = "[scaled]pad=1920:1080:(ow-iw)/2:(oh-ih)/2[padded]";

        $lastLabel = '[padded]';
        $filterNum = 0;

        // Add logo
        if ($this->channel->overlay_logo_enabled && $this->channel->overlay_logo_path) {
            // Logo overlay (complex, requires image overlay)
            // For now, simplified version
            $filterNum++;
            $lastLabel = "[padded]"; // Skip for now, too complex
        }

        // Add text
        if ($this->channel->overlay_text_enabled) {
            $text = match ($this->channel->overlay_text_content) {
                'channel_name' => $this->channel->name,
                'title' => '{metadata\\:comment}', // Placeholder
                default => $this->channel->overlay_text_custom ?? 'LIVE',
            };

            $x = $this->channel->overlay_text_x ?? 20;
            $y = $this->channel->overlay_text_y ?? 20;
            $fontSize = $this->channel->overlay_text_font_size ?? 24;
            $color = $this->channel->overlay_text_color ?? 'white';

            $filterNum++;
            $newLabel = "[txt{$filterNum}]";
            $filters[] = "{$lastLabel}drawtext=text='{$text}':fontsize={$fontSize}:fontcolor={$color}:x={$x}:y={$y}{$newLabel}";
            $lastLabel = $newLabel;
        }

        // Add timer
        if ($this->channel->overlay_timer_enabled) {
            $x = $this->channel->overlay_timer_x ?? 1920 - 100;
            $y = $this->channel->overlay_timer_y ?? 20;
            $fontSize = $this->channel->overlay_timer_font_size ?? 24;
            $color = $this->channel->overlay_timer_color ?? 'white';

            $filterNum++;
            $newLabel = "[timer{$filterNum}]";
            // Use timecode for elapsed time
            $filters[] = "{$lastLabel}drawtext=text='%{pts\\:hms}':fontsize={$fontSize}:fontcolor={$color}:x={$x}:y={$y}{$newLabel}";
            $lastLabel = $newLabel;
        }

        // Format output
        $filters[] = "{$lastLabel}format=yuv420p[out]";

        return implode(';', $filters);
    }
}
