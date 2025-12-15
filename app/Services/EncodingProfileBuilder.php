<?php

namespace App\Services;

use App\Models\EncodeProfile;
use App\Models\LiveChannel;

class EncodingProfileBuilder
{
    public static function buildCommand(LiveChannel $channel, $inputFile, $outputFile): string
    {
        $profile = null;
        $config = null;

        // Determine which config to use
        if ($channel->manual_encode_enabled && $channel->manual_encode_config) {
            $config = $channel->manual_encode_config;
        } else {
            $profile = $channel->encodeProfile ?? EncodeProfile::where('is_system', true)->first();
            $config = self::profileToConfig($profile);
        }

        return self::buildFFmpegCommand($inputFile, $outputFile, $config, $channel);
    }

    public static function profileToConfig(EncodeProfile $profile): array
    {
        return [
            'mode' => $profile->mode,
            'video_codec' => self::codecFromType($profile->type),
            'width' => $profile->width,
            'height' => $profile->height,
            'fps_mode' => $profile->fps_mode,
            'fps' => $profile->fps,
            'bitrate' => $profile->video_bitrate_k . 'k',
            'maxrate' => $profile->maxrate_k . 'k',
            'bufsize' => $profile->bufsize_k . 'k',
            'crf' => $profile->crf,
            'preset' => $profile->preset,
            'profile' => $profile->profile,
            'pix_fmt' => $profile->pix_fmt,
            'gop' => $profile->gop,
            'audio_codec' => $profile->audio_codec,
            'audio_bitrate' => $profile->audio_bitrate_k . 'k',
            'audio_channels' => $profile->audio_channels,
            'container' => $profile->container,
            'extra' => $profile->extra_ffmpeg,
            // LIVE-specific
            'ts_service_name' => $profile->ts_service_name,
            'ts_service_provider' => $profile->ts_service_provider,
            'pcr_period_ms' => $profile->pcr_period_ms,
            'pat_period_ms' => $profile->pat_period_ms,
            'pmt_period_ms' => $profile->pmt_period_ms,
            'muxrate_k' => $profile->muxrate_k,
        ];
    }

    public static function buildFFmpegCommand($input, $output, $config, $channel): string
    {
        $isLive = ($config['mode'] ?? 'vod') === 'live';

        // Start with input (for LIVE, add -re flag for realtime reading)
        $cmd = 'ffmpeg';
        if ($isLive) {
            $cmd .= ' -re'; // Read at native frame rate
        }
        $cmd .= ' -i ' . escapeshellarg($input);

        // Video codec
        $codec = $config['video_codec'] ?? 'libx264';
        $cmd .= ' -c:v ' . $codec;

        // Resolution (with scale filter if needed)
        if ($config['width'] && $config['height']) {
            // Ensure even dimensions
            $w = $config['width'];
            $h = $config['height'];
            if ($w % 2 !== 0) $w++;
            if ($h % 2 !== 0) $h++;
            
            // For LIVE, add padding to maintain aspect ratio
            if ($isLive) {
                $cmd .= ' -vf "scale=' . $w . ':' . $h . ':force_original_aspect_ratio=decrease,pad=' . $w . ':' . $h . ':(ow-iw)/2:(oh-ih)/2,format=yuv420p"';
            } else {
                $cmd .= ' -vf "scale=' . $w . ':' . $h . '"';
            }
        } else {
            // For LIVE without explicit resolution, ensure yuv420p format
            if ($isLive) {
                $cmd .= ' -vf "format=yuv420p"';
            }
        }

        // FPS - for LIVE use constant frame rate (CFR)
        if ($isLive) {
            $fps = $config['fps'] ?? 25;
            $cmd .= ' -r ' . $fps;
            $cmd .= ' -vsync cfr'; // Constant frame rate
            $cmd .= ' -g ' . (int)($fps * 2); // GOP = 2 seconds
        } elseif ($config['fps_mode'] === 'cfr' && $config['fps']) {
            $cmd .= ' -r ' . $config['fps'];
        }

        // Bitrate & rate control
        if ($config['crf'] !== null && !$isLive) {
            $cmd .= ' -crf ' . $config['crf'];
        } else {
            // For LIVE or bitrate mode
            $cmd .= ' -b:v ' . $config['bitrate'];
            if ($isLive) {
                // LIVE: set maxrate = bitrate for CBR
                $cmd .= ' -maxrate ' . $config['bitrate'];
                $cmd .= ' -bufsize ' . ($config['bufsize'] ?? (2 * (int)$config['maxrate_k']) . 'k');
            } else {
                $cmd .= ' -maxrate ' . $config['maxrate'];
                $cmd .= ' -bufsize ' . $config['bufsize'];
            }
        }

        // Encoder preset
        if ($config['preset']) {
            if (in_array($codec, ['libx264', 'libx265'])) {
                $cmd .= ' -preset ' . $config['preset'];
            } elseif (strpos($codec, 'nvenc') !== false) {
                $cmd .= ' -preset ' . $config['preset'];
            }
        }

        // Profile
        if ($config['profile'] && !$isLive) {
            $cmd .= ' -profile:v ' . $config['profile'];
        } elseif ($config['profile'] && $isLive) {
            // LIVE: use profile without level
            $cmd .= ' -profile:v ' . $config['profile'];
        }

        // Pixel format (already handled in vf for LIVE)
        if (!$isLive) {
            $cmd .= ' -pix_fmt ' . ($config['pix_fmt'] ?? 'yuv420p');
        }

        // GOP / Keyframe interval (for VOD, already set for LIVE above)
        if (!$isLive && $config['gop']) {
            $cmd .= ' -g ' . $config['gop'];
        }

        // Audio
        if ($config['audio_codec'] === 'copy') {
            $cmd .= ' -c:a copy';
        } else {
            $cmd .= ' -c:a aac';
            $cmd .= ' -b:a ' . ($config['audio_bitrate'] ?? '128k');
            $cmd .= ' -ac ' . ($config['audio_channels'] ?? 2);
            // For LIVE, enforce 48kHz audio
            if ($isLive) {
                $cmd .= ' -ar 48000';
            }
        }

        // LIVE MPEGTS-specific settings
        if ($isLive && $config['container'] === 'mpegts') {
            $cmd .= ' -mpegts_flags +resend_headers';
            // pcr_period is in milliseconds (integer)
            if ($config['pcr_period_ms']) {
                $cmd .= ' -pcr_period ' . (int)$config['pcr_period_ms'];
            }
            // pat_period and pmt_period are in seconds (float)
            if ($config['pat_period_ms']) {
                $cmd .= ' -pat_period ' . ($config['pat_period_ms'] / 1000);
            }
            if ($config['pmt_period_ms']) {
                $cmd .= ' -pmt_period ' . ($config['pmt_period_ms'] / 1000);
            }
        }

        // Extra args
        if ($config['extra']) {
            $cmd .= ' ' . $config['extra'];
        }

        // Output format
        if ($config['container'] === 'mpegts') {
            $cmd .= ' -f mpegts';
        }

        // Force overwrite + output
        $cmd .= ' -y ' . escapeshellarg($output);

        return $cmd;
    }

    public static function codecFromType($type): string
    {
        $map = [
            'h264_cpu' => 'libx264',
            'h265_cpu' => 'libx265',
            'h264_nvenc' => 'h264_nvenc',
            'h265_nvenc' => 'hevc_nvenc',
        ];
        return $map[$type] ?? 'libx264';
    }

    /**
     * Validate encoding config
     */
    public static function validate(array $config): array
    {
        $errors = [];

        // Bitrate
        if (!isset($config['bitrate']) || !is_numeric($config['bitrate']) || $config['bitrate'] <= 0) {
            $errors[] = 'Bitrate must be positive number';
        }

        // Max rate >= bitrate
        if (isset($config['maxrate']) && isset($config['bitrate'])) {
            if ((int)$config['maxrate'] < (int)$config['bitrate']) {
                $errors[] = 'Max rate must be >= bitrate';
            }
        }

        // Bufsize >= maxrate
        if (isset($config['bufsize']) && isset($config['maxrate'])) {
            if ((int)$config['bufsize'] < (int)$config['maxrate']) {
                $errors[] = 'Buffer size must be >= max rate';
            }
        }

        // FPS
        if ($config['fps'] && ($config['fps'] < 10 || $config['fps'] > 60)) {
            $errors[] = 'FPS must be between 10-60';
        }

        // Resolution - must be even
        if ($config['width'] && $config['width'] % 2 !== 0) {
            $errors[] = 'Width must be even';
        }
        if ($config['height'] && $config['height'] % 2 !== 0) {
            $errors[] = 'Height must be even';
        }

        // CRF
        if ($config['crf'] && ($config['crf'] < 0 || $config['crf'] > 51)) {
            $errors[] = 'CRF must be between 0-51';
        }

        // Copy audio only if no rescale
        if ($config['audio_codec'] === 'copy' && ($config['width'] || $config['height'])) {
            // Actually this is OK - just audio copy with video rescale is fine
        }

        return $errors;
    }
}
