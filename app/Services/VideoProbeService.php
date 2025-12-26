<?php

namespace App\Services;

use App\Models\Video;
use Symfony\Component\Process\Process;

class VideoProbeService
{
    public function probe(Video $video): array
    {
        $filePath = (string) ($video->file_path ?? '');

        if ($filePath === '' || !is_file($filePath)) {
            return [
                'ok' => false,
                'message' => 'Video file not found on disk',
            ];
        }

        $process = new Process([
            'ffprobe',
            '-v', 'error',
            '-print_format', 'json',
            '-show_entries', 'format=duration,bit_rate,format_name',
            '-show_entries', 'stream=codec_type,width,height',
            $filePath,
        ]);

        // Keep it snappy; this runs on-demand from UI
        $process->setTimeout(12);

        try {
            $process->mustRun();
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'ffprobe failed: ' . $e->getMessage(),
            ];
        }

        $raw = $process->getOutput();
        $json = json_decode($raw, true);

        if (!is_array($json)) {
            return [
                'ok' => false,
                'message' => 'ffprobe returned invalid JSON',
            ];
        }

        $format = $json['format'] ?? [];
        $streams = $json['streams'] ?? [];

        $durationSeconds = null;
        if (isset($format['duration']) && is_numeric($format['duration'])) {
            $durationSeconds = (int) round((float) $format['duration']);
        }

        $bitrateKbps = null;
        if (isset($format['bit_rate']) && is_numeric($format['bit_rate'])) {
            $bitrateKbps = (int) round(((float) $format['bit_rate']) / 1000);
        }

        $formatName = null;
        if (!empty($format['format_name'])) {
            $formatName = (string) $format['format_name'];
        }

        $width = null;
        $height = null;
        if (is_array($streams)) {
            foreach ($streams as $s) {
                if (($s['codec_type'] ?? null) === 'video') {
                    if (isset($s['width']) && is_numeric($s['width'])) {
                        $width = (int) $s['width'];
                    }
                    if (isset($s['height']) && is_numeric($s['height'])) {
                        $height = (int) $s['height'];
                    }
                    break;
                }
            }
        }

        $resolution = null;
        if ($width && $height) {
            $resolution = $width . 'x' . $height;
        }

        $sizeBytes = null;
        try {
            $size = @filesize($filePath);
            if ($size !== false) {
                $sizeBytes = (int) $size;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [
            'ok' => true,
            'duration_seconds' => $durationSeconds,
            'bitrate_kbps' => $bitrateKbps,
            'resolution' => $resolution,
            'size_bytes' => $sizeBytes,
            'format' => $formatName,
        ];
    }

    public function ffprobeAvailable(): bool
    {
        $process = new Process(['ffprobe', '-version']);
        $process->setTimeout(3);

        try {
            $process->run();
        } catch (\Throwable $e) {
            return false;
        }

        return $process->isSuccessful();
    }
}
