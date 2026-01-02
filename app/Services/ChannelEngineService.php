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
    protected string $feederPidFile;

    public function __construct(LiveChannel $channel)
    {
        $this->channel = $channel;
        $this->outputDir = storage_path("app/streams/{$channel->id}");
        $this->logPath = storage_path("logs/channel_{$channel->id}.log");
        $this->pidFile = storage_path("app/pids/{$channel->id}.pid");
        $this->feederPidFile = storage_path("app/pids/{$channel->id}.feeder.pid");
        
        // Ensure directories exist
        @mkdir($this->outputDir, 0755, true);
        @mkdir(dirname($this->pidFile), 0755, true);
    }

    /**
     * Detect a running ffmpeg process for this channel by scanning the process list.
     * This is used to prevent duplicate ffmpeg instances when PID tracking is stale
     * (e.g. after reboot or when the process is owned by a different user).
     */
    public function detectRunningFfmpegPid(): ?int
    {
        try {
            $p = new Process(['ps', '-eo', 'pid=,args=']);
            $p->setTimeout(2);
            $p->run();

            if (!$p->isSuccessful()) {
                return null;
            }

            $needle = $this->outputDir;
            $lines = preg_split('/\r?\n/', (string) $p->getOutput()) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                if (stripos($line, 'ffmpeg') === false) continue;
                if (strpos($line, $needle) === false) continue;

                // Expected forms include output to stream.ts or hls/stream.m3u8, or input FIFO.
                if (
                    strpos($line, $needle . '/stream.ts') === false
                    && strpos($line, $needle . '/hls/stream.m3u8') === false
                    && strpos($line, $needle . '/play_stream.fifo') === false
                ) {
                    continue;
                }

                if (preg_match('/^(\d+)\s+/', $line, $m)) {
                    $pid = (int) $m[1];
                    if ($pid > 1) {
                        return $pid;
                    }
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    protected function syncDetectedPidToChannel(?int $pid): void
    {
        if (!$pid || $pid <= 1) {
            return;
        }

        if ((int) ($this->channel->encoder_pid ?? 0) !== $pid) {
            try {
                $this->channel->update([
                    'encoder_pid' => $pid,
                    'status' => 'live',
                ]);
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    protected function playlistFifoPath(): string
    {
        return $this->outputDir . '/play_playlist.fifo';
    }

    protected function streamFifoPath(): string
    {
        return $this->outputDir . '/play_stream.fifo';
    }

    protected function ensureStreamFifo(): string
    {
        $fifo = $this->streamFifoPath();

        if (file_exists($fifo) && !@is_link($fifo) && !@is_dir($fifo)) {
            $type = @filetype($fifo);
            if ($type !== 'fifo') {
                @unlink($fifo);
            }
        }

        $type = file_exists($fifo) ? @filetype($fifo) : null;
        if ($type !== 'fifo') {
            if (function_exists('posix_mkfifo')) {
                @posix_mkfifo($fifo, 0644);
            } else {
                try {
                    $p = new Process(['mkfifo', $fifo]);
                    $p->setTimeout(2);
                    $p->run();
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        if (!file_exists($fifo) || @filetype($fifo) !== 'fifo') {
            throw new \RuntimeException('Failed to create stream FIFO: ' . $fifo);
        }

        return $fifo;
    }

    protected function ensurePlaylistFifo(): string
    {
        $fifo = $this->playlistFifoPath();

        // If a regular file exists at this path, remove it.
        if (file_exists($fifo) && !@is_link($fifo) && !@is_dir($fifo)) {
            $type = @filetype($fifo);
            if ($type !== 'fifo') {
                @unlink($fifo);
            }
        }

        $type = file_exists($fifo) ? @filetype($fifo) : null;
        if ($type !== 'fifo') {
            // Create FIFO (named pipe)
            if (function_exists('posix_mkfifo')) {
                @posix_mkfifo($fifo, 0644);
            } else {
                try {
                    $p = new Process(['mkfifo', $fifo]);
                    $p->setTimeout(2);
                    $p->run();
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        if (!file_exists($fifo) || @filetype($fifo) !== 'fifo') {
            throw new \RuntimeException('Failed to create playlist FIFO: ' . $fifo);
        }

        return $fifo;
    }

    protected function startPlaylistFeeder(): ?int
    {
        // Start feeder only if stream FIFO exists.
        $fifo = $this->streamFifoPath();
        if (!file_exists($fifo) || @filetype($fifo) !== 'fifo') {
            return null;
        }

        $cmd = 'php ' . escapeshellarg(base_path('artisan')) . ' channel:feed-stream ' . (int) $this->channel->id;
        $launch = "nohup {$cmd} >> " . escapeshellarg($this->logPath) . " 2>&1 < /dev/null & echo $!";
        $p = Process::fromShellCommandline($launch);
        $p->setTimeout(5);
        $p->run();
        $pid = (int) trim((string) $p->getOutput());

        if ($pid > 1 && $this->isRunning($pid)) {
            @file_put_contents($this->feederPidFile, (string) $pid);
            $this->appendLog("[System] Stream feeder started (PID {$pid})\n");
            return $pid;
        }

        return null;
    }

    /**
     * Start channel encoding
     * Builds and executes ffmpeg command with proper output handling
     */
    public function start(string $ffmpegCommand): array
    {
        try {
            // Check if already running (PID file, stored PID, or detected ffmpeg writing into this channel output dir)
            $storedPid = (int) ($this->channel->encoder_pid ?? 0);
            if ($this->isRunning() || ($storedPid > 1 && $this->isRunning($storedPid))) {
                return ['status' => 'error', 'message' => 'Channel already running'];
            }

            $detectedPid = $this->detectRunningFfmpegPid();
            if ($detectedPid) {
                $this->syncDetectedPidToChannel($detectedPid);
                return [
                    'status' => 'error',
                    'message' => 'Channel already running (detected ffmpeg PID ' . $detectedPid . ')',
                ];
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

            // Start ffmpeg detached so it keeps running after the web request ends.
            // Using nohup + background + echo $! gives us the real ffmpeg PID.
            $cmd = trim($ffmpegCommand);
            if ($cmd === '') {
                return ['status' => 'error', 'message' => 'Empty ffmpeg command'];
            }

            @mkdir(dirname($this->logPath), 0755, true);
            $launch = "nohup {$cmd} >> " . escapeshellarg($this->logPath) . " 2>&1 < /dev/null & echo $!";
            $launcher = Process::fromShellCommandline($launch);
            $launcher->setTimeout(5);
            $launcher->mustRun();

            $pidNow = (int) trim((string) $launcher->getOutput());
            usleep(300000); // let ffmpeg initialize

            if ($pidNow <= 1 || !$this->isRunning($pidNow)) {
                $this->appendLog("[System] FFmpeg failed to start (no running PID)\n");

                $job->update([
                    'status' => 'error',
                    'ended_at' => Carbon::now(),
                    'exit_code' => 1,
                ]);
                $this->channel->update([
                    'status' => 'error',
                    'encoder_pid' => null,
                ]);

                return [
                    'status' => 'error',
                    'message' => 'FFmpeg failed to start. Check channel log.',
                ];
            }

            // Save PID
            $this->savePid($pidNow);

            // If this channel is started in FIFO playlist mode, start the feeder.
            // This enables appending new TS-ready items without restarting FFmpeg.
            try {
                $this->startPlaylistFeeder();
            } catch (\Throwable $e) {
                $this->appendLog('[System] Playlist feeder failed: ' . $e->getMessage() . "\n");
            }

            // Update job
            $job->update([
                'status' => 'running',
                'pid' => $pidNow,
            ]);

            // Update channel
            $this->channel->update([
                'status' => 'live',
                'encoder_pid' => $pidNow,
                'started_at' => Carbon::now(),
            ]);

            Log::info("Channel {$this->channel->id} started with PID {$pidNow}");

            return [
                'status' => 'success',
                'message' => 'Channel started',
                'pid' => $pidNow,
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
            // Stop feeder first (so it doesn't keep the FIFO open).
            $feederPid = null;
            if (is_file($this->feederPidFile)) {
                $feederPid = (int) trim((string) @file_get_contents($this->feederPidFile));
            }
            if ($feederPid && $this->isRunning($feederPid)) {
                @posix_kill($feederPid, SIGTERM);
                usleep(200000);
                if ($this->isRunning($feederPid)) {
                    @posix_kill($feederPid, SIGKILL);
                }
            }
            @unlink($this->feederPidFile);

            $pid = $this->readPid();

            // Fallback: if pidfile is stale (shell wrapper exited), try the PID stored on the channel.
            if ((!$pid || !$this->isRunning($pid)) && !empty($this->channel->encoder_pid)) {
                $fallbackPid = (int) $this->channel->encoder_pid;
                if ($fallbackPid > 0 && $this->isRunning($fallbackPid)) {
                    $pid = $fallbackPid;
                }
            }

            // Last-resort fallback: sometimes a shell wrapper PID gets stored while the real ffmpeg PID differs.
            // If we don't have a valid PID, locate ffmpeg by matching the output path in its command line.
            $extraPids = [];
            if (!$pid || !$this->isRunning($pid)) {
                try {
                    $needle = preg_quote($this->outputDir . '/stream.ts', '/');
                    $pgrep = new Process(['pgrep', '-f', $needle]);
                    $pgrep->setTimeout(2);
                    $pgrep->run();
                    if ($pgrep->isSuccessful()) {
                        $lines = preg_split('/\R+/', trim($pgrep->getOutput()));
                        foreach ($lines as $line) {
                            $cand = (int) trim((string) $line);
                            if ($cand > 1 && $this->isRunning($cand)) {
                                $extraPids[] = $cand;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore; we'll handle as already stopped below
                }
            }

            if ((!$pid || !$this->isRunning($pid)) && empty($extraPids)) {
                $this->channel->update(['status' => 'idle']);
                return ['status' => 'success', 'message' => 'Channel already stopped'];
            }

            // If pid is invalid but we found ffmpeg PIDs, stop them.
            if ((!$pid || !$this->isRunning($pid)) && !empty($extraPids)) {
                $pid = $extraPids[0];
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

            // Also terminate any additional matched ffmpeg processes (if any)
            foreach ($extraPids as $p) {
                if ($p === $pid) continue;
                if ($this->isRunning($p)) {
                    @posix_kill($p, SIGTERM);
                    usleep(200000);
                    if ($this->isRunning($p)) {
                        @posix_kill($p, SIGKILL);
                    }
                }
            }

            // Clean up
            $this->deletePid();
            
            // Update channel
            $this->channel->update([
                'status' => 'idle',
                'encoder_pid' => null,
                'started_at' => null,
            ]);

            // Update job(s)
            // Prefer updating by live_channel_id because PID may not match when a wrapper PID was stored.
            EncodingJob::where('live_channel_id', $this->channel->id)
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
     * Generate a FIFO-based play command.
     * The FIFO is fed by `channel:feed-playlist` in an infinite loop.
     * This allows new TS-ready items to be appended without stopping the channel.
     */
    public function generatePlayCommandFromFilesFifo(bool $includeOverlay = false): string
    {
        $outputDir = $this->outputDir;

        // Ensure stream FIFO exists.
        $fifoPath = $this->ensureStreamFifo();

        $tsOutput = "{$outputDir}/stream.ts";

        $cmd = [
            'ffmpeg',
            '-nostdin',
            '-y',
            '-re',
            '-i', escapeshellarg($fifoPath),
        ];

        // No re-encoding - just copy streams
        $cmd = array_merge($cmd, ['-c:v', 'copy', '-c:a', 'copy']);

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
     * Check if channel is currently running
     */
    public function isRunning(?int $pid = null): bool
    {
        $pid = $pid ?? $this->readPid();

        if (!$pid) {
            return false;
        }

        // Check if process exists
        if (posix_getpid() === $pid) {
            return true;
        }

        $ok = @posix_kill($pid, 0);
        if ($ok) {
            return true;
        }

        // If we don't have permission to signal the process, it still exists.
        // This prevents false negatives that lead to duplicate ffmpeg instances.
        $err = function_exists('posix_get_last_error') ? (int) @posix_get_last_error() : 0;
        if ($err === 1 /* EPERM */) {
            return true;
        }

        return false;
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
     * Extract the most recent FFmpeg realtime speed factor from the channel log.
     * Example values: "0.88x", "1x", "1.10x".
     */
    public function getLastFfmpegSpeed(): ?string
    {
        try {
            if (!is_file($this->logPath)) {
                return null;
            }

            $size = (int) @filesize($this->logPath);
            if ($size <= 0) {
                return null;
            }

            // Read only the tail of the log to keep it fast even for huge logs.
            $maxBytes = 128 * 1024;
            $readBytes = min($maxBytes, $size);
            $fh = @fopen($this->logPath, 'rb');
            if (!$fh) {
                return null;
            }

            if ($readBytes < $size) {
                @fseek($fh, -$readBytes, SEEK_END);
            }

            $chunk = @stream_get_contents($fh);
            @fclose($fh);

            if (!is_string($chunk) || $chunk === '') {
                return null;
            }

            // Typical ffmpeg output: "speed=   1x" or "speed=1.10x".
            if (!preg_match_all('/\bspeed=\s*([0-9]+(?:\.[0-9]+)?)x\b/i', $chunk, $m) || empty($m[1])) {
                return null;
            }

            $raw = (string) end($m[1]);
            $raw = trim($raw);
            if ($raw === '') {
                return null;
            }

            // Preserve as-is from FFmpeg. Normalize leading/trailing spaces.
            return $raw . 'x';
        } catch (\Throwable $e) {
            return null;
        }
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

                // Duration is optional for concat demuxer; write it only when numeric.
                $dur = null;
                if (isset($video->duration_seconds)) {
                    $dur = $video->duration_seconds;
                } elseif (isset($video->duration)) {
                    $dur = $video->duration;
                }
                if (is_numeric($dur) && (float) $dur > 0) {
                    $playlistContent .= 'duration ' . (float) $dur . "\n";
                }

                $playlistContent .= "\n";
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

        $videoBitrateK = (int) (
            $profile?->video_bitrate_k
            ?? ($this->channel->video_bitrate ?? 1500)
        );
        $maxrateK = (int) ($profile?->maxrate_k ?? (int) ceil($videoBitrateK * 1.25));
        $bufsizeK = (int) ($profile?->bufsize_k ?? (int) max(1, $maxrateK * 2));
        $audioBitrateK = (int) (
            $profile?->audio_bitrate_k
            ?? ($this->channel->audio_bitrate ?? 128)
        );
        $audioChannels = (int) ($profile?->audio_channels ?? 2);

        // Base input and output paths
        $tsOutput = "{$this->outputDir}/stream.ts";

        // Create HLS directory
        @mkdir("{$this->outputDir}/hls", 0755, true);

        // Build FFmpeg command with concat demuxer
        // The concat demuxer automatically loops through the playlist
        $cmd = [
            'ffmpeg',
            '-nostdin',
            '-y',
            '-f', 'concat',        // Use concat demuxer
            '-safe', '0',          // Allow absolute paths
            '-protocol_whitelist', 'file,http,https,tcp,tls,crypto', // Required for concat
            '-i', escapeshellarg($playlistPath), // Input playlist
        ];

        if (!empty($filterComplex)) {
            $cmd = array_merge($cmd, [
                '-filter_complex', escapeshellarg($filterComplex),
                '-map', escapeshellarg('[out]'),
                '-map', '0:a?',
            ]);
        }

        // Codec and encoding settings
        $cmd = array_merge($cmd, [
            '-c:v', 'libx264',
            '-preset', $profile?->preset ?? 'medium',
            '-b:v', $videoBitrateK . 'k',
            '-maxrate', $maxrateK . 'k',
            '-bufsize', $bufsizeK . 'k',
            '-c:a', $profile?->audio_codec ?? 'aac',
            '-b:a', $audioBitrateK . 'k',
            '-ar', 48000,
            '-ac', $audioChannels,
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

        $videoBitrateK = (int) (
            $profile?->video_bitrate_k
            ?? ($this->channel->video_bitrate ?? 1500)
        );
        $maxrateK = (int) ($profile?->maxrate_k ?? (int) ceil($videoBitrateK * 1.25));
        $bufsizeK = (int) ($profile?->bufsize_k ?? (int) max(1, $maxrateK * 2));
        $audioBitrateK = (int) (
            $profile?->audio_bitrate_k
            ?? ($this->channel->audio_bitrate ?? 128)
        );
        $audioChannels = (int) ($profile?->audio_channels ?? 2);

        // Base input and output paths
        $tsOutput = "{$this->outputDir}/stream.ts";
        $hlsOutput = "{$this->outputDir}/stream.m3u8";

        // Build filter complex for overlay
        $filterComplex = $this->buildFilterComplex($includeOverlay);

        // Build FFmpeg command
        $cmd = [
            'ffmpeg',
            '-nostdin',
            '-y',
            '-re', // Read at input rate (for streaming)
            '-i', escapeshellarg($inputFile),
        ];

        if (!empty($filterComplex)) {
            $cmd = array_merge($cmd, [
                '-filter_complex', escapeshellarg($filterComplex),
                '-map', escapeshellarg('[out]'),
                '-map', '0:a?',
            ]);
        }

        // Codec and encoding settings
        $cmd = array_merge($cmd, [
            '-c:v', 'libx264',
            '-preset', $profile?->preset ?? 'medium',
            '-b:v', $videoBitrateK . 'k',
            '-maxrate', $maxrateK . 'k',
            '-bufsize', $bufsizeK . 'k',
            '-c:a', $profile?->audio_codec ?? 'aac',
            '-b:a', $audioBitrateK . 'k',
            '-ar', 48000,
            '-ac', $audioChannels,
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
            // Prefer relative paths to avoid hardcoding absolute project paths.
            // Concat demuxer resolves relative paths relative to the playlist file.
            $relative = basename($file);
            $escapedPath = str_replace("'", "'\\''", $relative);
            $playlistContent .= "file '{$escapedPath}'\n";
        }

        file_put_contents($playlistPath, $playlistContent);
        
        $this->appendLog("Generated play playlist: $playlistPath");
        $this->appendLog("Encoded files: " . count($encodedFiles));

        // Build command: play from concat playlist (no re-encoding, just muxing)
        $tsOutput = "{$outputDir}/stream.ts";

        $cmd = [
            'ffmpeg',
            '-nostdin',
            '-y',
            '-re',
        ];

        if ($loop) {
            // Input-level loop for 24/7 channel playback
            $cmd = array_merge($cmd, ['-stream_loop', '-1']);
        }

        $cmd = array_merge($cmd, [
            '-f', 'concat',
            '-safe', '0',
            '-protocol_whitelist', 'file,http,https,tcp,tls,crypto',
            '-i', escapeshellarg($playlistPath),
            // NO re-encoding - just copy streams
            '-c:v', 'copy',
            '-c:a', 'copy',
        ]);

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
     * Generate command to PLAY from a provided ordered list of already-encoded TS files.
     * This ensures looping uses only the files the user encoded (e.g., playlist TS-ready items).
     */
    public function generatePlayCommandFromFiles(array $encodedFiles, bool $loop = true): string
    {
        $outputDir = $this->outputDir;

        $files = [];
        foreach ($encodedFiles as $path) {
            $path = (string) $path;
            if ($path === '') continue;
            if (!is_file($path)) continue;
            $files[] = $path;
        }

        if (empty($files)) {
            throw new \Exception('No encoded TS files found for this channel playlist.');
        }

        // Create concat playlist for the encoded files (keep given order)
        $playlistPath = "{$outputDir}/play_playlist.txt";
        $playlistContent = "# FFmpeg Concat Demuxer Playlist (Pre-Encoded TS Files)\n";

        foreach ($files as $file) {
            $path = (string) $file;
            $outputDirNorm = rtrim(str_replace('\\', '/', $outputDir), '/') . '/';
            $pathNorm = str_replace('\\', '/', $path);

            // If the file lives inside the output directory, write a relative path.
            // This keeps the playlist portable across deployments (/var/www vs /home, etc.).
            if (str_starts_with($pathNorm, $outputDirNorm)) {
                $pathNorm = substr($pathNorm, strlen($outputDirNorm));
            }

            $escapedPath = str_replace("'", "'\\''", $pathNorm);
            $playlistContent .= "file '{$escapedPath}'\n";
        }

        file_put_contents($playlistPath, $playlistContent);

        $this->appendLog("Generated play playlist: $playlistPath");
        $this->appendLog("Encoded files (playlist): " . count($files));

        $tsOutput = "{$outputDir}/stream.ts";

        $cmd = [
            'ffmpeg',
            '-nostdin',
            '-y',
            '-re',
        ];

        if ($loop) {
            $cmd = array_merge($cmd, ['-stream_loop', '-1']);
        }

        $cmd = array_merge($cmd, [
            '-f', 'concat',
            '-safe', '0',
            '-protocol_whitelist', 'file,http,https,tcp,tls,crypto',
            '-i', escapeshellarg($playlistPath),
            '-c:v', 'copy',
            '-c:a', 'copy',
        ]);

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
        $logoPath = (string) ($this->channel->overlay_logo_path ?? $this->channel->logo_path ?? '');
        $logoEnabled = (bool) ($this->channel->overlay_logo_enabled ?? false);
        if (!$logoEnabled && trim($logoPath) !== '') {
            // Legacy channels may not use overlay_logo_enabled; enable when a logo path exists.
            $logoEnabled = true;
        }

        $textEnabled = (bool) ($this->channel->overlay_text_enabled ?? false);
        if (!$textEnabled && (bool) ($this->channel->overlay_title ?? false)) {
            $textEnabled = true;
        }

        $timerEnabled = (bool) ($this->channel->overlay_timer_enabled ?? false);
        if (!$timerEnabled && (bool) ($this->channel->overlay_timer ?? false)) {
            $timerEnabled = true;
        }

        if (!$includeOverlay || (!$logoEnabled && !$textEnabled && !$timerEnabled)) {
            return '';
        }

        $filters = [];

        // Rezoluție target din setările canalului
        [$outW, $outH] = $this->parseResolution((string) ($this->channel->resolution ?? ''));
        if (!$outW || !$outH) {
            $outW = 1920;
            $outH = 1080;
        }

        if ($this->channel->manual_override_encoding || $this->channel->manual_encode_enabled) {
            $mw = (int) ($this->channel->manual_width ?? 0);
            $mh = (int) ($this->channel->manual_height ?? 0);
            if ($mw > 0 && $mh > 0) {
                $outW = $mw;
                $outH = $mh;
            }
        }

        // Start with scale/pad to ensure consistent output
        $filters[] = "[0:v]scale={$outW}:{$outH}:force_original_aspect_ratio=decrease:force_divisible_by=2[scaled]";
        $filters[] = "[scaled]pad={$outW}:{$outH}:(ow-iw)/2:(oh-ih)/2[padded]";

        $lastLabel = '[padded]';
        $filterNum = 0;

        // Add logo (support legacy logo_path)
        if ($logoEnabled && trim($logoPath) !== '') {
            $logoRel = $logoPath;
            $logoAbs = null;

            if (str_starts_with($logoRel, '/')) {
                $logoAbs = $logoRel;
            } else {
                try {
                    $logoAbs = Storage::disk('local')->path($logoRel);
                } catch (\Throwable $e) {
                    $logoAbs = null;
                }

                if (!$logoAbs || !file_exists($logoAbs)) {
                    $try1 = storage_path('app/' . ltrim($logoRel, '/'));
                    $try2 = storage_path('app/private/' . ltrim($logoRel, '/'));
                    if (file_exists($try1)) $logoAbs = $try1;
                    elseif (file_exists($try2)) $logoAbs = $try2;
                }
            }

            if ($logoAbs && file_exists($logoAbs)) {
                $logoW = (int) ($this->channel->overlay_logo_width ?? $this->channel->logo_width ?? 150);
                $logoH = (int) ($this->channel->overlay_logo_height ?? $this->channel->logo_height ?? 100);
                $logoX = (int) ($this->channel->overlay_logo_x ?? $this->channel->logo_position_x ?? 20);
                $logoY = (int) ($this->channel->overlay_logo_y ?? $this->channel->logo_position_y ?? 20);
                $safeLogo = str_replace("'", "\\'", $logoAbs);

                $filterNum++;
                $newLabel = "[logo{$filterNum}]";
                $filters[] = "movie='{$safeLogo}',scale={$logoW}:{$logoH}[lg]";
                $filters[] = "{$lastLabel}[lg]overlay={$logoX}:{$logoY}{$newLabel}";
                $lastLabel = $newLabel;
            }
        }

        // Add text
        if ($textEnabled) {
            // În engine nu avem titlul VOD pe fiecare clip fără re-encodare.
            // Pentru 'title' folosim fallback la numele canalului.
            $mode = (string) ($this->channel->overlay_text_content ?? 'channel_name');
            $text = match ($mode) {
                'custom' => (string) ($this->channel->overlay_text_custom ?? $this->channel->name ?? 'LIVE'),
                default => (string) ($this->channel->name ?? 'LIVE'),
            };

            $x = $this->channel->overlay_text_x ?? 20;
            $y = $this->channel->overlay_text_y ?? 20;
            $fontSize = $this->channel->overlay_text_font_size ?? 24;
            $color = $this->channel->overlay_text_color ?? 'white';

            $fontFamily = (string) ($this->channel->overlay_text_font_family ?? 'Arial');
            $fontFile = $this->resolveFontFileForFamily($fontFamily);
            $fontOpt = '';
            if ($fontFile) {
                $safeFontFile = $this->escapeForDrawtextValue($fontFile);
                $fontOpt = ":fontfile='{$safeFontFile}'";
            }

            $filterNum++;
            $newLabel = "[txt{$filterNum}]";
            $safeText = $this->escapeForDrawtext((string) $text);
            $filters[] = "{$lastLabel}drawtext=text='{$safeText}'{$fontOpt}:fontsize={$fontSize}:fontcolor={$color}:x={$x}:y={$y}{$newLabel}";
            $lastLabel = $newLabel;
        }

        // Add timer
        if ($timerEnabled) {
            $x = $this->channel->overlay_timer_x ?? 1920 - 100;
            $y = $this->channel->overlay_timer_y ?? 20;
            $fontSize = $this->channel->overlay_timer_font_size ?? 24;
            $color = $this->channel->overlay_timer_color ?? 'white';

            $fontFamily = (string) ($this->channel->overlay_text_font_family ?? 'Arial');
            $fontFile = $this->resolveFontFileForFamily($fontFamily);
            $fontOpt = '';
            if ($fontFile) {
                $safeFontFile = $this->escapeForDrawtextValue($fontFile);
                $fontOpt = ":fontfile='{$safeFontFile}'";
            }

            $filterNum++;
            $newLabel = "[timer{$filterNum}]";
            // Use timecode for elapsed time
            $mode = strtolower(trim((string) ($this->channel->overlay_timer_mode ?? 'elapsed')));
            $fmt = (string) ($this->channel->overlay_timer_format ?? 'HH:mm');
            $timeExpr = "%{pts\\:hms}";
            if ($mode === 'realtime') {
                $ff = match ($fmt) {
                    'HH:mm:ss' => '%H\\:%M\\:%S',
                    'HH:mm:ss.mmm' => '%H\\:%M\\:%S',
                    default => '%H\\:%M',
                };
                $timeExpr = "%{localtime:{$ff}}";
            }
            $filters[] = "{$lastLabel}drawtext=text='{$timeExpr}'{$fontOpt}:fontsize={$fontSize}:fontcolor={$color}:x={$x}:y={$y}{$newLabel}";
            $lastLabel = $newLabel;
        }

        // Format output
        $filters[] = "{$lastLabel}format=yuv420p[out]";

        return implode(';', $filters);
    }

    protected function parseResolution(string $res): array
    {
        $res = trim(strtolower($res));
        if ($res === '') return [0, 0];
        if (!preg_match('/^(\d{2,5})\s*x\s*(\d{2,5})$/', $res, $m)) return [0, 0];
        return [(int) $m[1], (int) $m[2]];
    }

    protected function escapeForDrawtext(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace("'", "\\'", $text);
        $text = str_replace(':', '\\:', $text);
        $text = str_replace(["\n", "\r"], ' ', $text);
        return $text;
    }

    protected function resolveFontFileForFamily(?string $family): ?string
    {
        $family = trim((string) $family);
        if ($family === '') return null;

        static $cache = [];
        if (array_key_exists($family, $cache)) {
            return $cache[$family];
        }

        try {
            $process = new \Symfony\Component\Process\Process(['fc-match', '-f', '%{file}\n', $family]);
            $process->setTimeout(2);
            $process->run();
            if (!$process->isSuccessful()) {
                return $cache[$family] = null;
            }
            $file = trim((string) $process->getOutput());
            if ($file === '' || !is_file($file)) {
                return $cache[$family] = null;
            }
            return $cache[$family] = $file;
        } catch (\Throwable $e) {
            return $cache[$family] = null;
        }
    }

    protected function escapeForDrawtextValue(string $value): string
    {
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace("'", "\\'", $value);
        $value = str_replace(':', '\\:', $value);
        $value = str_replace(["\n", "\r"], ' ', $value);
        return $value;
    }
}
