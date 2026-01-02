<?php

namespace App\Services;

use App\Models\LiveChannel;
use App\Models\EncodingJob;
use App\Models\PlaylistItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    protected function setting(string $key, $default = null)
    {
        $settings = $this->job->settings ?? [];
        if (is_array($settings) && array_key_exists($key, $settings)) {
            $v = $settings[$key];
            // Allow explicit falsey values (0/false), but ignore empty strings
            if ($v === 0 || $v === '0' || $v === false) return $v;
            if ($v === null) return $default;
            if (is_string($v) && trim($v) === '') return $default;
            return $v;
        }
        return $default;
    }

    protected function settingBool(string $key, bool $default = false): bool
    {
        $v = $this->setting($key, $default);
        if (is_bool($v)) return $v;
        if (is_numeric($v)) return ((int) $v) === 1;
        $parsed = filter_var($v, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        return $parsed === null ? $default : (bool) $parsed;
    }

    protected function resolveFontFileForFamily(?string $family): ?string
    {
        $family = trim((string) $family);
        if ($family === '') return null;

        static $cache = [];
        if (array_key_exists($family, $cache)) {
            return $cache[$family];
        }

        // Use fontconfig to resolve the actual font file. This works even if "Arial"
        // isn't installed (it will fallback to the closest available font).
        try {
            $process = new Process(['fc-match', '-f', '%{file}\n', $family]);
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

            // If this was a production encode and the video isn't in the playlist yet,
            // append it automatically to the end so it will be picked up by FIFO playback.
            $this->maybeAppendToPlaylistOnSuccess();

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

        // Manual override (din UI / per-job settings) - dacă e activ, suprascrie profile-ul
        $manual = $this->settingBool('manual_override_encoding', (bool) ($this->channel->manual_override_encoding ?? false))
            || $this->settingBool('manual_encode_enabled', (bool) ($this->channel->manual_encode_enabled ?? false));

        $videoCodec = (string) ($profile->codec ?? 'libx264');
        $preset = (string) ($profile->preset ?? 'veryfast');
        $videoBitrateK = (int) ($profile->video_bitrate_k ?? 1500);
        $maxrateK = (int) ($profile->maxrate_k ?? max(1875, (int) round($videoBitrateK * 1.25)));
        $bufsizeK = (int) ($profile->bufsize_k ?? max(3750, (int) round($videoBitrateK * 2.5)));
        $audioCodec = (string) ($profile->audio_codec ?? 'aac');
        $audioBitrateK = (int) ($profile->audio_bitrate_k ?? 128);
        $audioChannels = (int) ($profile->audio_channels ?? 2);
        $audioRate = (int) ($profile->audio_rate ?? 48000);

        if ($manual) {
            $mCodec = (string) $this->setting('manual_codec', (string) ($this->channel->manual_codec ?? ''));
            $mPreset = (string) $this->setting('manual_preset', (string) ($this->channel->manual_preset ?? ''));
            $mBitrate = (int) $this->setting('manual_bitrate', (int) ($this->channel->manual_bitrate ?? 0));
            $mAudioCodec = (string) $this->setting('manual_audio_codec', (string) ($this->channel->manual_audio_codec ?? ''));
            $mAudioBitrate = (int) $this->setting('manual_audio_bitrate', (int) ($this->channel->manual_audio_bitrate ?? 0));

            if ($mCodec !== '') $videoCodec = $mCodec;
            if ($mPreset !== '') $preset = $mPreset;
            if ($mBitrate > 0) {
                $videoBitrateK = $mBitrate;
                $maxrateK = max($maxrateK, (int) round($videoBitrateK * 1.25));
                $bufsizeK = max($bufsizeK, (int) round($videoBitrateK * 2.5));
            }

            if ($mAudioCodec !== '') $audioCodec = $mAudioCodec;
            if ($mAudioBitrate > 0) $audioBitrateK = $mAudioBitrate;
        }

        // Per-job UI keys (Create Video page) compatibility
        // If user provided these keys, treat them as manual overrides even if flags are missing.
        $uiCodec = (string) $this->setting('encoder', '');
        if ($uiCodec !== '') {
            $videoCodec = $uiCodec;
        }
        $uiPreset = (string) $this->setting('preset', '');
        if ($uiPreset !== '') {
            $preset = $uiPreset;
        }
        $uiBitrate = (int) $this->setting('video_bitrate', 0);
        if ($uiBitrate > 0) {
            $videoBitrateK = $uiBitrate;
            $maxrateK = max($maxrateK, (int) round($videoBitrateK * 1.25));
            $bufsizeK = max($bufsizeK, (int) round($videoBitrateK * 2.5));
        }
        $uiAudioBitrate = (int) $this->setting('audio_bitrate', 0);
        if ($uiAudioBitrate > 0) {
            $audioBitrateK = $uiAudioBitrate;
        }

        $tune = trim((string) $this->setting('manual_tune', (string) $this->setting('tune', '')));
        $fpsRaw = trim((string) $this->setting('manual_fps', (string) $this->setting('frame_rate', '')));
        $fps = (int) $fpsRaw;
        if ($fps < 0) $fps = 0;

        $crfMode = strtolower(trim((string) $this->setting('crf_mode', '')));
        $crfEnabled = $this->settingBool('manual_crf_enabled', false) || in_array($crfMode, ['manual', 'enabled', 'crf'], true);
        $crf = (int) $this->setting('manual_crf', (int) $this->setting('crf_value', 23));
        if ($crf < 0) $crf = 0;
        if ($crf > 51) $crf = 51;

        // Build filter complex for overlay
        $filterComplex = $this->buildFilterComplex();

        $progressDir = storage_path('app/encoding_progress');
        @mkdir($progressDir, 0755, true);
        $progressFile = $progressDir . '/job_' . $this->job->id . '.txt';

        $outputContainer = strtolower(trim((string) $this->setting('output_container', 'ts')));
        if (!in_array($outputContainer, ['ts', 'mpegts', 'mp4', 'hls'], true)) {
            $outputContainer = 'ts';
        }

        // Ensure output directory exists
        try {
            $outPath = (string) ($this->job->output_path ?? '');
            if ($outPath !== '') {
                @mkdir(dirname($outPath), 0755, true);
            }
        } catch (\Throwable $e) {
            // best-effort
        }

        $testDuration = (int) $this->setting('test_duration_seconds', 0);
        if ($testDuration < 0) $testDuration = 0;

        // Base command
        $cmd = [
            'ffmpeg',
            '-hide_banner',
            '-y',
        ];

        $testStart = (int) $this->setting('test_start_seconds', (int) $this->setting('test_start_time', 0));
        if ($testStart < 0) $testStart = 0;
        if ($testStart > 0) {
            $cmd = array_merge($cmd, ['-ss', (string) $testStart]);
        }

        $cmd = array_merge($cmd, [
            '-i', escapeshellarg($this->job->input_path),
            '-progress', escapeshellarg($progressFile),
            '-nostats',
        ]);

        // Add filter if overlay is enabled
        if (!empty($filterComplex)) {
            $cmd = array_merge($cmd, [
                '-filter_complex', escapeshellarg($filterComplex),
                '-map', '[out]',  // Use filter output (video)
                '-map', '0:a?',   // Keep audio when present
            ]);
        } else {
            $cmd = array_merge($cmd, ['-map', '0:v', '-map', '0:a?']);
        }

        // Optional: limit duration for test encodes
        if ($testDuration > 0) {
            $cmd = array_merge($cmd, ['-t', (string) $testDuration]);
        }

        // Encoding settings
        $videoArgs = [
            '-c:v', $videoCodec,
            '-preset', $preset,
        ];

        // Tune (use only for libx264 to avoid codec-specific incompatibilities)
        if ($tune !== '' && $videoCodec === 'libx264') {
            $videoArgs = array_merge($videoArgs, ['-tune', $tune]);
        }

        // FPS override
        if ($fps > 0) {
            $videoArgs = array_merge($videoArgs, ['-r', (string) $fps]);
        }

        // CRF or bitrate
        if ($crfEnabled) {
            $videoArgs = array_merge($videoArgs, [
                '-crf', (string) $crf,
                '-maxrate', $maxrateK . 'k',
                '-bufsize', $bufsizeK . 'k',
            ]);
        } else {
            $videoArgs = array_merge($videoArgs, [
                '-b:v', $videoBitrateK . 'k',
                '-maxrate', $maxrateK . 'k',
                '-bufsize', $bufsizeK . 'k',
            ]);
        }

        $audioArgs = [
            '-c:a', $audioCodec,
            '-b:a', $audioBitrateK . 'k',
            '-ar', $audioRate,
            '-ac', $audioChannels,
        ];

        $cmd = array_merge($cmd, $videoArgs, $audioArgs);

        // Output container
        if ($outputContainer === 'hls') {
            $outPath = (string) ($this->job->output_path ?? '');
            $outDir = $outPath !== '' ? dirname($outPath) : storage_path('app/public/previews');
            @mkdir($outDir, 0755, true);
            $segmentPattern = rtrim($outDir, '/') . '/seg_%03d.ts';

            $cmd = array_merge($cmd, [
                '-f', 'hls',
                '-hls_time', (string) ((int) $this->setting('hls_time', 2)),
                '-hls_list_size', '0',
                '-hls_flags', 'independent_segments',
                '-hls_segment_filename', escapeshellarg($segmentPattern),
                escapeshellarg($outPath),
            ]);
        } elseif ($outputContainer === 'mp4') {
            $cmd = array_merge($cmd, [
                '-movflags', '+faststart',
                '-pix_fmt', 'yuv420p',
                '-f', 'mp4',
                escapeshellarg($this->job->output_path),
            ]);
        } else {
            $cmd = array_merge($cmd, [
                '-f', 'mpegts',
                escapeshellarg($this->job->output_path),
            ]);
        }

        // Persist progress file path in settings (for UI polling / monitoring)
        try {
            $s = $this->job->settings ?? [];
            if (!is_array($s)) $s = [];
            $s['_progress_file'] = $progressFile;
            $this->job->settings = $s;
            $this->job->save();
        } catch (\Throwable $e) {
            // Best-effort only
        }

        return implode(' ', $cmd);
    }

    protected function maybeAppendToPlaylistOnSuccess(): void
    {
        try {
            $settings = is_array($this->job->settings) ? $this->job->settings : [];
            if ((string) ($settings['job_type'] ?? '') === 'test') {
                return;
            }

            $channelId = (int) ($this->channel->id ?? 0);
            $videoId = (int) ($this->job->video_id ?? 0);
            if ($channelId <= 0 || $videoId <= 0) {
                return;
            }

            $alreadyInPlaylist = PlaylistItem::query()
                ->where(function ($q) use ($channelId) {
                    $q->where('live_channel_id', $channelId)
                        ->orWhere('vod_channel_id', $channelId);
                })
                ->where('video_id', $videoId)
                ->exists();

            if ($alreadyInPlaylist) {
                return;
            }

            $maxOrder = (int) (PlaylistItem::query()
                ->where(function ($q) use ($channelId) {
                    $q->where('live_channel_id', $channelId)
                        ->orWhere('vod_channel_id', $channelId);
                })
                ->max('sort_order') ?? 0);

            $newItem = PlaylistItem::create([
                'live_channel_id' => $channelId,
                'vod_channel_id' => $channelId,
                'video_id' => $videoId,
                'sort_order' => $maxOrder > 0 ? $maxOrder + 1 : 1,
            ]);

            $this->appendLog("[System] Auto-added to playlist (item #{$newItem->id}, video #{$videoId})");

            // Best-effort: ensure the playback naming convention video_{playlist_item_id}.ts exists.
            // If we already encoded to a different filename (e.g., video_{video_id}.ts), link it.
            $outputPath = (string) ($this->job->output_path ?? '');
            if ($outputPath !== '' && is_file($outputPath)) {
                $outputDir = storage_path('app/streams/' . $channelId);
                $expected = $outputDir . '/video_' . (int) $newItem->id . '.ts';
                if (!is_file($expected)) {
                    // Prefer symlink; if it fails, fallback resolution via video_id still works.
                    @symlink($outputPath, $expected);
                }
            }
        } catch (\Throwable $e) {
            // Never fail the job for this convenience feature.
            try {
                $this->appendLog('[System] Auto-append failed: ' . $e->getMessage());
            } catch (\Throwable $e2) {
                // ignore
            }
        }
    }

    /**
     * Build filter complex for overlay (logo, text, timer)
     */
    protected function buildFilterComplex(): string
    {
        $filters = [];

        $jobSettings = $this->job->settings;
        if (!is_array($jobSettings)) {
            $jobSettings = [];
        }

        // Keep some state so timer can stack relative to title.
        $titleEnabled = false;
        $titlePos = 'BL';
        $titleX = 0;
        $titleY = 0;
        $titleFontSize = 0;

        // Rezoluție target din setările canalului (ex: 1280x720)
        [$outW, $outH] = $this->parseResolution((string) $this->setting('resolution', (string) ($this->channel->resolution ?? '')));
        if (!$outW || !$outH) {
            $outW = 1920;
            $outH = 1080;
        }

        // Dacă manual override e activ și are width/height, folosește-le
        $manual = $this->settingBool('manual_override_encoding', (bool) ($this->channel->manual_override_encoding ?? false))
            || $this->settingBool('manual_encode_enabled', (bool) ($this->channel->manual_encode_enabled ?? false));
        if ($manual) {
            $mw = (int) $this->setting('manual_width', (int) ($this->channel->manual_width ?? 0));
            $mh = (int) $this->setting('manual_height', (int) ($this->channel->manual_height ?? 0));
            if ($mw > 0 && $mh > 0) {
                $outW = $mw;
                $outH = $mh;
            }
        }

        // Start with scale/pad for consistent output
        $filters[] = "[0:v]scale={$outW}:{$outH}:force_original_aspect_ratio=decrease:force_divisible_by=2[scaled]";
        $filters[] = "[scaled]pad={$outW}:{$outH}:(ow-iw)/2:(oh-ih)/2[padded]";
        $lastLabel = '[padded]';

        // Add logo overlay if enabled
        // Support both new (overlay_logo_*) and legacy (logo_*) fields.
        $logoPath = (string) $this->setting('overlay_logo_path', (string) ($this->channel->overlay_logo_path ?? ''));
        if (trim($logoPath) === '') {
            $logoPath = (string) $this->setting('logo_path', (string) ($this->channel->logo_path ?? ''));
        }

        // Default behavior: if a logo path exists, show it (unless explicitly disabled per-job).
        // Do NOT rely on live_channels.overlay_logo_enabled because it defaults to 0 for legacy channels.
        $logoEnabledDefault = trim($logoPath) !== '';
        $logoEnabled = $this->settingBool('overlay_logo_enabled', $logoEnabledDefault);

        if ($logoEnabled && $logoPath) {
            $logoW = (int) $this->setting('overlay_logo_width', (int) ($this->channel->overlay_logo_width ?? ($this->channel->logo_width ?? 100)));
            $logoH = (int) $this->setting('overlay_logo_height', (int) ($this->channel->overlay_logo_height ?? ($this->channel->logo_height ?? 100)));
            $logoX = (int) $this->setting('overlay_logo_x', (int) ($this->channel->overlay_logo_x ?? ($this->channel->logo_position_x ?? 20)));
            $logoY = (int) $this->setting('overlay_logo_y', (int) ($this->channel->overlay_logo_y ?? ($this->channel->logo_position_y ?? 20)));
            $logoPos = strtoupper(trim((string) $this->setting('overlay_logo_position', (string) ($this->channel->overlay_logo_position ?? ($this->channel->logo_position ?? 'TL')))));
            if ($logoPos === '') $logoPos = 'TL';
            if (strlen($logoPos) === 2 && ctype_alpha($logoPos)) {
                $logoPos = strtoupper($logoPos);
            }
            if (!in_array($logoPos, ['TL','TR','BL','BR','CUSTOM'], true)) {
                // Accept legacy lowercase tl/tr/bl/br
                $logoPos = strtoupper($logoPos);
                if (in_array($logoPos, ['TL','TR','BL','BR'], true) === false) {
                    $logoPos = 'TL';
                }
            }
            if ($logoX < 0) $logoX = 0;
            if ($logoY < 0) $logoY = 0;

            // overlay_logo_path este de obicei o cale relativă pe disk-ul local.
            // FFmpeg (movie=) are nevoie de o cale reală pe filesystem.
            $logoAbs = null;
            if (str_starts_with($logoPath, '/')) {
                $logoAbs = $logoPath;
            } else {
                try {
                    $logoAbs = Storage::disk('local')->path($logoPath);
                } catch (\Throwable $e) {
                    $logoAbs = null;
                }

                if (!$logoAbs || !file_exists($logoAbs)) {
                    $try1 = storage_path('app/' . ltrim($logoPath, '/'));
                    $try2 = storage_path('app/private/' . ltrim($logoPath, '/'));
                    if (file_exists($try1)) $logoAbs = $try1;
                    elseif (file_exists($try2)) $logoAbs = $try2;
                }
            }

            if (!$logoAbs || !file_exists($logoAbs)) {
                // Dacă logo-ul nu există, nu blocăm encodarea.
                $logoAbs = null;
            }
            
            // Add logo as overlay
            if ($logoAbs) {
                $safeLogo = str_replace("'", "\\'", $logoAbs);
                // Preserve logo aspect ratio (avoid stretching); fit inside the requested WxH box.
                $filters[] = "movie='{$safeLogo}',scale={$logoW}:{$logoH}:force_original_aspect_ratio=decrease[logo]";

                $safeMargin = (int) $this->setting('overlay_safe_margin', (int) ($this->channel->overlay_safe_margin ?? 30));
                if ($safeMargin < 0) $safeMargin = 0;

                $logoXExpr = (string) $logoX;
                $logoYExpr = (string) $logoY;
                if ($logoPos !== 'CUSTOM') {
                    // For TL/TR/BL/BR, treat overlay_logo_x/y as margins from that corner.
                    // If the per-job settings explicitly provided 0, respect it.
                    $hasLogoX = array_key_exists('overlay_logo_x', $jobSettings);
                    $hasLogoY = array_key_exists('overlay_logo_y', $jobSettings);
                    $mX = $hasLogoX ? $logoX : ($logoX > 0 ? $logoX : $safeMargin);
                    $mY = $hasLogoY ? $logoY : ($logoY > 0 ? $logoY : $safeMargin);
                    $logoXExpr = match ($logoPos) {
                        'TR', 'BR' => "W-w-{$mX}",
                        default => (string) $mX,
                    };
                    $logoYExpr = match ($logoPos) {
                        'BL', 'BR' => "H-h-{$mY}",
                        default => (string) $mY,
                    };
                }

                $this->appendLog("Overlay logo: enabled=1 pos={$logoPos} x={$logoX} y={$logoY} expr={$logoXExpr}:{$logoYExpr} size={$logoW}x{$logoH}");

                $filters[] = "{$lastLabel}[logo]overlay={$logoXExpr}:{$logoYExpr}[withlogo]";
                $lastLabel = '[withlogo]';
            }
        }

        // Add text overlay if enabled (support legacy overlay_title)
        // Do NOT rely on overlay_text_enabled default 0 for legacy channels.
        $textEnabled = $this->settingBool('overlay_text_enabled', (bool) ($this->channel->overlay_title ?? false));
        if ($textEnabled) {
            $titleEnabled = true;
            $textMode = (string) $this->setting('overlay_text_content', (string) ($this->channel->overlay_text_content ?? 'channel_name'));
            $text = $this->resolveOverlayText($textMode);
            $fontFamily = (string) $this->setting('overlay_text_font_family', (string) ($this->channel->overlay_text_font_family ?? 'Arial'));
            $fontFile = $this->resolveFontFileForFamily($fontFamily);
            $fontSize = (int) $this->setting('overlay_text_font_size', (int) ($this->channel->overlay_text_font_size ?? 24));
            $titleFontSize = $fontSize;
            $color = (string) $this->setting('overlay_text_color', (string) ($this->channel->overlay_text_color ?? 'white'));

            $safeMargin = (int) $this->setting('overlay_safe_margin', (int) ($this->channel->overlay_safe_margin ?? 30));
            if ($safeMargin < 0) $safeMargin = 0;
            // Default for VOD is bottom-left (BL)
            $textPos = strtoupper(trim((string) $this->setting('overlay_text_position', (string) ($this->channel->overlay_text_position ?? 'BL'))));
            $textX = (int) $this->setting('overlay_text_x', (int) ($this->channel->overlay_text_x ?? $safeMargin));
            $textY = (int) $this->setting('overlay_text_y', (int) ($this->channel->overlay_text_y ?? $safeMargin));
            if ($textX < 0) $textX = 0;
            if ($textY < 0) $textY = 0;

            $titlePos = $textPos;
            $titleX = $textX;
            $titleY = $textY;

            $xExpr = (string) $textX;
            $yExpr = (string) $textY;
            if ($textPos !== '' && $textPos !== 'CUSTOM') {
                // For corner anchors (TL/TR/BL/BR), treat overlay_text_x/y as margins from that corner.
                // If the per-job settings explicitly provided 0, respect it.
                $hasTextX = array_key_exists('overlay_text_x', $jobSettings);
                $hasTextY = array_key_exists('overlay_text_y', $jobSettings);
                $mX = $hasTextX ? $textX : ($textX > 0 ? $textX : $safeMargin);
                $mY = $hasTextY ? $textY : ($textY > 0 ? $textY : $safeMargin);

                $xExpr = match ($textPos) {
                    'TR', 'BR' => "w-tw-{$mX}",
                    default => (string) $mX,
                };

                $yExpr = match ($textPos) {
                    'BL', 'BR' => "h-th-{$mY}",
                    default => (string) $mY,
                };
            }

            $safeText = $this->escapeForDrawtext($text);

            $fontOpt = '';
            if ($fontFile) {
                $safeFontFile = $this->escapeForDrawtextValue($fontFile);
                $fontOpt = ":fontfile='{$safeFontFile}'";
            }

            $this->appendLog("Overlay title: enabled=1 pos={$textPos} x={$textX} y={$textY} expr={$xExpr}:{$yExpr} fontsize={$fontSize}");
            $filters[] = "{$lastLabel}drawtext=text='{$safeText}'{$fontOpt}:x={$xExpr}:y={$yExpr}:fontsize={$fontSize}:fontcolor={$color}[txt]";
            $lastLabel = '[txt]';
        }

        // Add timer if enabled (support legacy overlay_timer)
        // Do NOT rely on overlay_timer_enabled default 0 for legacy channels.
        $timerEnabled = $this->settingBool('overlay_timer_enabled', (bool) ($this->channel->overlay_timer ?? false));
        if ($timerEnabled) {
            $timerFont = (int) $this->setting('overlay_timer_font_size', (int) ($this->channel->overlay_timer_font_size ?? 24));
            $timerColor = (string) $this->setting('overlay_timer_color', (string) ($this->channel->overlay_timer_color ?? '#FFFFFF'));

            // Use the same font family as the title for a consistent look.
            $fontFamily = (string) $this->setting('overlay_text_font_family', (string) ($this->channel->overlay_text_font_family ?? 'Arial'));
            $fontFile = $this->resolveFontFileForFamily($fontFamily);
            $fontOpt = '';
            if ($fontFile) {
                $safeFontFile = $this->escapeForDrawtextValue($fontFile);
                $fontOpt = ":fontfile='{$safeFontFile}'";
            }

            $safeMargin = (int) $this->setting('overlay_safe_margin', (int) ($this->channel->overlay_safe_margin ?? 30));
            if ($safeMargin < 0) $safeMargin = 0;
            // Default for VOD is bottom-left (BL)
            $timerPos = strtoupper(trim((string) $this->setting('overlay_timer_position', (string) ($this->channel->overlay_timer_position ?? 'BL'))));
            $timerX = (int) $this->setting('overlay_timer_x', (int) ($this->channel->overlay_timer_x ?? $safeMargin));
            $timerY = (int) $this->setting('overlay_timer_y', (int) ($this->channel->overlay_timer_y ?? $safeMargin));
            if ($timerX < 0) $timerX = 0;
            if ($timerY < 0) $timerY = 0;

            $timerXExpr = (string) $timerX;
            $timerYExpr = (string) $timerY;
            if ($timerPos !== '' && $timerPos !== 'CUSTOM') {
                // For corner anchors (TL/TR/BL/BR), treat overlay_timer_x/y as margins from that corner.
                // If the per-job settings explicitly provided 0, respect it.
                $hasTimerX = array_key_exists('overlay_timer_x', $jobSettings);
                $hasTimerY = array_key_exists('overlay_timer_y', $jobSettings);
                $mX = $hasTimerX ? $timerX : ($timerX > 0 ? $timerX : $safeMargin);
                $mY = $hasTimerY ? $timerY : ($timerY > 0 ? $timerY : $safeMargin);

                $timerXExpr = match ($timerPos) {
                    'TR', 'BR' => "w-tw-{$mX}",
                    default => (string) $mX,
                };
                $timerYExpr = match ($timerPos) {
                    'BL', 'BR' => "h-th-{$mY}",
                    default => (string) $mY,
                };
            }

            // Prevent overlap: if timer shares the same corner or same custom XY as the title, move timer higher.
            if ($titleEnabled) {
                $offset = max(0, (int) $titleFontSize + 10);

                // Same corner (BL/BR): stack timer above title.
                if (($timerPos === $titlePos) && in_array($timerPos, ['BL', 'BR'], true) && $offset > 0) {
                    $timerYExpr = "({$timerYExpr}-{$offset})";
                }

                // Same custom XY: stack timer above title.
                if (($timerPos === 'CUSTOM') && ($titlePos === 'CUSTOM') && ($timerX === $titleX) && ($timerY === $titleY) && $offset > 0) {
                    $timerYExpr = "({$timerYExpr}-{$offset})";
                }
            }

            // For VOD preview/encode we want a single countdown HH:MM:SS based on real input duration.
            $mode = strtolower(trim((string) $this->setting('overlay_timer_mode', (string) ($this->channel->overlay_timer_mode ?? 'countdown'))));
            $fmt = (string) $this->setting('overlay_timer_format', (string) ($this->channel->overlay_timer_format ?? 'HH:mm:ss'));

            // Always compute duration from ffprobe when possible (DB metadata can be wrong).
            $durProbe = $this->probeDurationSeconds($this->job->input_path);
            $durDb = (int) ($this->job->video?->duration_seconds ?? 0);
            $dur = $durProbe > 0 ? $durProbe : $durDb;

            // If we have a real duration, always use countdown for VOD encodes.
            // This avoids showing elapsed/realtime clocks and ensures a decreasing HH:MM:SS.
            if ($dur > 0) {
                $mode = 'countdown';
                $fmt = 'HH:mm:ss';
            }

            // Default: elapsed time since filter start (monotonic).
            // `%{pts\:hms}` can jump/reset with odd or concatenated timestamps.
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

            if ($mode === 'countdown') {
                if ($dur > 0) {
                    $D = $dur;
                    $timeExpr = "%{eif\\:floor(max(0\\,{$D}-t)/3600)\\:d\\:2}\\:%{eif\\:floor(mod(max(0\\,{$D}-t)\\,3600)/60)\\:d\\:2}\\:%{eif\\:floor(mod(max(0\\,{$D}-t)\\,60))\\:d\\:2}";
                }
            }

            $this->appendLog("Overlay timer: enabled=1 pos={$timerPos} x={$timerX} y={$timerY} expr={$timerXExpr}:{$timerYExpr} fontsize={$timerFont} mode={$mode}");

            $filters[] = "{$lastLabel}drawtext=text='{$timeExpr}'{$fontOpt}:x={$timerXExpr}:y={$timerYExpr}:fontsize={$timerFont}:fontcolor={$timerColor}[timer]";
            $lastLabel = '[timer]';
        }

        // Final output
        if ($lastLabel !== '[padded]') {
            // Label final output for -map [out].
            // We need a real filter between two labels; `copy` is a no-op filter.
            $filters[] = "{$lastLabel}copy[out]";
            return implode(';', $filters);
        }

        return '';
    }

    /**
     * Public wrapper used by preview endpoints.
     * Generates the exact same filter graph used for final encoding.
     */
    public function buildFilterComplexForPreview(): string
    {
        return $this->buildFilterComplex();
    }

    protected function probeDurationSeconds(string $inputPath): int
    {
        $p = trim($inputPath);
        if ($p === '' || !is_file($p)) return 0;

        try {
            $process = new Process([
                'ffprobe',
                '-v', 'error',
                '-show_entries', 'format=duration',
                '-of', 'default=nw=1:nk=1',
                $p,
            ]);
            $process->setTimeout(15);
            $process->run();
            if (!$process->isSuccessful()) return 0;

            $out = trim((string) $process->getOutput());
            if ($out === '' || !is_numeric($out)) return 0;
            $seconds = (int) floor((float) $out);
            return max(0, $seconds);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function parseResolution(string $res): array
    {
        $res = trim(strtolower($res));
        if ($res === '') return [0, 0];
        if (!preg_match('/^(\d{2,5})\s*x\s*(\d{2,5})$/', $res, $m)) return [0, 0];
        return [(int) $m[1], (int) $m[2]];
    }

    protected function resolveOverlayText(string $mode): string
    {
        $mode = strtolower(trim($mode));

        if ($mode === 'title') {
            return (string)($this->job->video?->title ?? $this->job->playlistItem?->video?->title ?? $this->channel->name ?? '');
        }

        if ($mode === 'custom') {
            $custom = (string) $this->setting('overlay_text_custom', (string) ($this->channel->overlay_text_custom ?? ''));
            if (trim($custom) !== '') return $custom;
            return (string)($this->channel->name ?? '');
        }

        // channel_name (default)
        return (string)($this->channel->name ?? '');
    }

    protected function escapeForDrawtext(string $text): string
    {
        // drawtext uses ':' as separator and supports escaping with backslashes.
        // We wrap in single quotes, so escape single quotes too.
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace("'", "\\'", $text);
        $text = str_replace(':', '\\:', $text);
        $text = str_replace("\n", ' ', $text);
        $text = str_replace("\r", ' ', $text);
        return $text;
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
     */    /**
     * Start encoding async (non-blocking)
     */
    public function startAsync(): void
    {
        try {
            $command = $this->buildEncodeCommand();
            
            // Update job status
            $this->job->update([
                'status' => 'running',
                'started_at' => Carbon::now(),
            ]);

            $this->appendLog("Starting encoding in background...");
            $this->appendLog("Command: $command");

            // Start ffmpeg process directly with nohup and capture PID
            $cmd = "nohup $command >> " . escapeshellarg($this->logPath) . " 2>&1 & echo $!";
            $out = [];
            exec($cmd, $out);
            $pid = isset($out[0]) ? (int) trim((string) $out[0]) : null;

            if ($pid && $pid > 0) {
                $this->job->update(['pid' => $pid]);
            }

            Log::info("Encoding job {$this->job->id} started async", ['pid' => $pid]);
            
        } catch (\Exception $e) {
            Log::error("Failed to start encoding async for job {$this->job->id}: {$e->getMessage()}");
            $this->job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
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
