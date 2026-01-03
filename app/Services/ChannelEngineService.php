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
     * Best-effort cleanup of stream outputs (HLS + TS) before starting ffmpeg.
     * This prevents stale/cached segments from being served after restarts.
     */
    protected function cleanupStreamOutputs(): void
    {
        $outputDir = $this->outputDir;
        $hlsDir = $outputDir . '/hls';

        $filesToRemove = [
            $outputDir . '/stream.ts',
            $outputDir . '/stream.m3u8',
            $hlsDir . '/stream.m3u8',
        ];

        foreach ($filesToRemove as $path) {
            try {
                if (is_file($path)) {
                    if (!@unlink($path)) {
                        $this->appendLog("[System] Cleanup: failed to remove {$path}");
                    }
                }
            } catch (\Throwable $e) {
                $this->appendLog('[System] Cleanup error: ' . $e->getMessage());
            }
        }

        if (is_dir($hlsDir)) {
            $patterns = ['*.ts', '*.m3u8', '*.tmp', '*.part'];
            foreach ($patterns as $pattern) {
                $matches = @glob($hlsDir . '/' . $pattern) ?: [];
                foreach ($matches as $path) {
                    try {
                        if (is_file($path) && !@unlink($path)) {
                            $this->appendLog("[System] Cleanup: failed to remove {$path}");
                        }
                    } catch (\Throwable $e) {
                        $this->appendLog('[System] Cleanup error: ' . $e->getMessage());
                    }
                }
            }
        }
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

        $updates = [
            'encoder_pid' => $pid,
            'status' => 'live',
        ];

        if (empty($this->channel->started_at)) {
            $startedAt = $this->getProcessStartedAt($pid);
            if ($startedAt) {
                $updates['started_at'] = $startedAt;
            }
        }

        try {
            $this->channel->update($updates);
        } catch (\Throwable $e) {
            // ignore
        }

        // Keep PID file in sync so normal polling works.
        try {
            $this->savePid($pid);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Best-effort: get process start time as a real wallclock timestamp.
     * Used to show accurate uptime when the channel was started outside this app
     * or when DB started_at is missing.
     */
    protected function getProcessStartedAt(int $pid): ?Carbon
    {
        try {
            if ($pid <= 1) return null;
            $statPath = '/proc/' . $pid . '/stat';
            if (!is_readable($statPath)) return null;

            $stat = trim((string) @file_get_contents($statPath));
            if ($stat === '') return null;

            // /proc/<pid>/stat: field 22 is starttime (clock ticks since boot)
            // comm is in parentheses and may contain spaces.
            $endComm = strrpos($stat, ')');
            if ($endComm === false) return null;
            $after = trim(substr($stat, $endComm + 1));
            $parts = preg_split('/\s+/', $after);
            if (!$parts || count($parts) < 22) return null;

            $starttimeTicks = (int) ($parts[19] ?? 0); // 22nd overall => 20th after ')'
            if ($starttimeTicks <= 0) return null;

            $clkTck = 100;
            if (function_exists('posix_sysconf') && defined('POSIX_SC_CLK_TCK')) {
                $v = @posix_sysconf(POSIX_SC_CLK_TCK);
                if (is_int($v) && $v > 0) $clkTck = $v;
            }

            $btime = null;
            $procStat = @file_get_contents('/proc/stat');
            if (is_string($procStat) && preg_match('/^btime\s+(\d+)$/m', $procStat, $m)) {
                $btime = (int) $m[1];
            }
            if (!$btime) return null;

            $startedEpoch = $btime + ($starttimeTicks / $clkTck);
            return Carbon::createFromTimestamp((int) floor($startedEpoch));
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function ensureChannelMarkedLive(int $pid): void
    {
        if ($pid <= 1) return;

        $updates = [
            'status' => 'live',
            'encoder_pid' => $pid,
        ];

        if (empty($this->channel->started_at)) {
            $startedAt = $this->getProcessStartedAt($pid);
            $updates['started_at'] = $startedAt ?: Carbon::now();
        }

        try {
            $this->channel->update($updates);
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            $this->savePid($pid);
        } catch (\Throwable $e) {
            // ignore
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
            // If a regular file exists here, remove it.
            if ($type !== 'fifo') {
                @unlink($fifo);
            } else {
                // If FIFO exists but is not writable (common when created as root with 0644), recreate it.
                if (!@is_writable($fifo)) {
                    @unlink($fifo);
                }
            }
        }

        $type = file_exists($fifo) ? @filetype($fifo) : null;
        if ($type !== 'fifo') {
            if (function_exists('posix_mkfifo')) {
                @posix_mkfifo($fifo, 0666);
            } else {
                try {
                    $p = new Process(['mkfifo', $fifo]);
                    $p->setTimeout(2);
                    $p->run();
                    @chmod($fifo, 0666);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        // Best-effort: ensure write permission for feeder.
        if (file_exists($fifo) && @filetype($fifo) === 'fifo') {
            @chmod($fifo, 0666);
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
            } else {
                if (!@is_writable($fifo)) {
                    @unlink($fifo);
                }
            }
        }

        $type = file_exists($fifo) ? @filetype($fifo) : null;
        if ($type !== 'fifo') {
            // Create FIFO (named pipe)
            if (function_exists('posix_mkfifo')) {
                @posix_mkfifo($fifo, 0666);
            } else {
                try {
                    $p = new Process(['mkfifo', $fifo]);
                    $p->setTimeout(2);
                    $p->run();
                    @chmod($fifo, 0666);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        if (file_exists($fifo) && @filetype($fifo) === 'fifo') {
            @chmod($fifo, 0666);
        }

        if (!file_exists($fifo) || @filetype($fifo) !== 'fifo') {
            throw new \RuntimeException('Failed to create playlist FIFO: ' . $fifo);
        }

        return $fifo;
    }

    protected function startPlaylistFeeder(string $ffmpegCommand): ?int
    {
        // Only start feeder when FFmpeg is actually consuming the FIFO.
        $fifo = $this->streamFifoPath();
        if ($fifo === '' || strpos($ffmpegCommand, $fifo) === false) {
            return null;
        }

        // Start feeder only if stream FIFO exists.
        if (!file_exists($fifo) || @filetype($fifo) !== 'fifo') {
            return null;
        }

        $cmd = 'php ' . escapeshellarg(base_path('artisan')) . ' channel:feed-stream ' . (int) $this->channel->id;
        $pid = $this->launchDetached($cmd, $this->logPath);

        if ($pid > 1 && $this->isRunning($pid)) {
            @file_put_contents($this->feederPidFile, (string) $pid);
            $this->appendLog("[System] Stream feeder started (PID {$pid})\n");
            return $pid;
        }

        return null;
    }

    /**
     * Launch a command detached and return the spawned PID.
     * If this PHP process runs as root (e.g. cron/scheduler), drop privileges to www-data
     * so web stop/start can manage the process reliably.
     */
    protected function launchDetached(string $cmd, string $logPath): int
    {
        $cmd = trim($cmd);
        if ($cmd === '') return 0;

        @mkdir(dirname($logPath), 0755, true);
        $inner = "nohup {$cmd} >> " . escapeshellarg($logPath) . " 2>&1 < /dev/null & echo $!";

        try {
            $euid = function_exists('posix_geteuid') ? (int) @posix_geteuid() : -1;

            // Prefer runuser when running as root.
            if ($euid === 0) {
                foreach (['/usr/sbin/runuser', '/sbin/runuser', '/bin/runuser', '/usr/bin/runuser'] as $bin) {
                    if (is_file($bin) && is_executable($bin)) {
                        $p = new Process([$bin, '-u', 'www-data', '--', 'sh', '-lc', $inner]);
                        $p->setTimeout(5);
                        $p->mustRun();
                        return (int) trim((string) $p->getOutput());
                    }
                }
            }
        } catch (\Throwable $e) {
            // fall back below
        }

        try {
            $p = Process::fromShellCommandline($inner);
            $p->setTimeout(5);
            $p->mustRun();
            return (int) trim((string) $p->getOutput());
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function getProcessOwnerUid(int $pid): ?int
    {
        try {
            if ($pid <= 1) return null;
            $path = '/proc/' . $pid . '/status';
            if (!is_readable($path)) return null;
            $txt = (string) @file_get_contents($path);
            if ($txt === '') return null;
            if (preg_match('/^Uid:\s+(\d+)\s+/m', $txt, $m)) {
                return (int) $m[1];
            }
        } catch (\Throwable $e) {
            return null;
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
            $pidFromFile = $this->readPid();
            if (($pidFromFile && $this->isRunning($pidFromFile)) || ($storedPid > 1 && $this->isRunning($storedPid))) {
                $pid = ($pidFromFile && $this->isRunning($pidFromFile)) ? $pidFromFile : $storedPid;
                $this->ensureChannelMarkedLive((int) $pid);
                return ['status' => 'success', 'message' => 'Channel already running', 'pid' => (int) $pid];
            }

            $detectedPid = $this->detectRunningFfmpegPid();
            if ($detectedPid) {
                $this->syncDetectedPidToChannel($detectedPid);
                return [
                    'status' => 'success',
                    'message' => 'Channel already running',
                    'pid' => (int) $detectedPid,
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

            // Ensure HLS/TS outputs are fresh for this run.
            // If old segments remain (or are owned by a different user), players can keep showing stale video.
            $this->cleanupStreamOutputs();

            $pidNow = $this->launchDetached($cmd, $this->logPath);
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

            // If this channel is started in FIFO stream mode, start the feeder.
            // This enables appending new TS-ready items without restarting FFmpeg.
            try {
                $this->startPlaylistFeeder($cmd);
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

            // Permission guard: if the ffmpeg process is owned by a different user and we're not root,
            // we cannot stop it from the web UI.
            $euid = function_exists('posix_geteuid') ? (int) @posix_geteuid() : null;
            $ownerUid = $pid ? $this->getProcessOwnerUid((int) $pid) : null;
            if ($pid && $ownerUid !== null && $euid !== null && $euid !== 0 && $ownerUid !== $euid) {
                return [
                    'status' => 'error',
                    'message' => 'Cannot stop channel: ffmpeg PID ' . (int) $pid . ' is owned by UID ' . (int) $ownerUid . ' (current UID ' . (int) $euid . '). Start/stop must run under the same OS user (recommended: www-data).',
                ];
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
        // Note: ffmpeg option scoping is per-output. These codec options apply only
        // to the *next* output file. We must repeat them for each output.
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
            // Ensure HLS is also stream-copied (avoid accidental transcoding).
            '-c:v', 'copy',
            '-c:a', 'copy',
            '-f', 'hls',
            '-hls_time', '10',
            '-hls_list_size', '6',
            '-hls_flags', 'delete_segments+program_date_time',
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

        // Fallback to DB PID when pidfile is missing/stale.
        if (!$isRunning) {
            $dbPid = (int) ($this->channel->encoder_pid ?? 0);
            if ($dbPid > 1 && $this->isRunning($dbPid)) {
                $pid = $dbPid;
                $isRunning = true;
                $this->ensureChannelMarkedLive($pid);
            }
        }

        // Last resort: scan process list to find ffmpeg writing into this channel.
        if (!$isRunning) {
            $detectedPid = $this->detectRunningFfmpegPid();
            if ($detectedPid && $this->isRunning($detectedPid)) {
                $pid = $detectedPid;
                $isRunning = true;
                $this->ensureChannelMarkedLive($pid);
            }
        }

        $startedAt = $this->channel->started_at;
        if ($isRunning && empty($startedAt) && $pid) {
            $pStart = $this->getProcessStartedAt((int) $pid);
            if ($pStart) {
                $startedAt = $pStart;
                try {
                    $this->channel->update(['started_at' => $pStart]);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        return [
            'status' => $isRunning ? 'live' : 'idle',
            'pid' => $isRunning ? $pid : null,
            'is_running' => $isRunning,
            'started_at' => $startedAt,
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
            '-hls_flags', 'delete_segments+program_date_time',
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
            '-hls_flags', 'delete_segments+program_date_time',
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
            '-hls_flags', 'delete_segments+program_date_time',
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
            '-hls_flags', 'delete_segments+program_date_time',
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
            // Use a monotonic clock for elapsed time.
            // `%{pts\:hms}` depends on input PTS and can reset/jump across concatenated sources.
            $mode = strtolower(trim((string) ($this->channel->overlay_timer_mode ?? 'elapsed')));
            $fmt = (string) ($this->channel->overlay_timer_format ?? 'HH:mm');
            $timeExpr = match ($fmt) {
                'HH:mm:ss.mmm' => "%{eif\\:floor(t/3600)\\:d\\:2}\\:%{eif\\:floor(mod(t\\,3600)/60)\\:d\\:2}\\:%{eif\\:floor(mod(t\\,60))\\:d\\:2}.%{eif\\:floor(mod(t*1000\\,1000))\\:d\\:3}",
                'HH:mm:ss' => "%{eif\\:floor(t/3600)\\:d\\:2}\\:%{eif\\:floor(mod(t\\,3600)/60)\\:d\\:2}\\:%{eif\\:floor(mod(t\\,60))\\:d\\:2}",
                default => "%{eif\\:floor(t/3600)\\:d\\:2}\\:%{eif\\:floor(mod(t\\,3600)/60)\\:d\\:2}",
            };
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
