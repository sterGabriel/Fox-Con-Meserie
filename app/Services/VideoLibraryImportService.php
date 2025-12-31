<?php

namespace App\Services;

use App\Models\EncodingJob;
use App\Models\LiveChannel;
use App\Models\Video;
use App\Models\VideoCategory;

class VideoLibraryImportService
{
    /**
     * Create or update a Video record for a given media file path.
     *
     * @return array{ok:bool, message:string, video_id?:int}
     */
    public function importToCategory(string $filePath, VideoCategory $category, string $mode = 'move', ?string $sourcePathForUpdate = null): array
    {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['move', 'copy'], true)) {
            $mode = 'move';
        }

        $canonical = realpath($filePath) ?: $filePath;
        if ($canonical === '' || !is_file($canonical) || !is_readable($canonical)) {
            return ['ok' => false, 'message' => 'Fișierul nu există sau nu poate fi citit: ' . basename($filePath)];
        }

        $existing = null;

        // If we moved the file, try to update the existing record that pointed to the old path.
        if ($mode === 'move' && $sourcePathForUpdate) {
            $src = $sourcePathForUpdate;
            $srcCanonical = realpath($src) ?: $src;
            $existing = Video::query()
                ->where('file_path', $src)
                ->orWhere('file_path', $srcCanonical)
                ->first();
        }

        // Otherwise match by destination path.
        if (!$existing) {
            $existing = Video::query()
                ->where('file_path', $filePath)
                ->orWhere('file_path', $canonical)
                ->first();
        }

        $media = $this->probe($canonical);

        $payload = [
            'title' => pathinfo($canonical, PATHINFO_FILENAME),
            'file_path' => $canonical,
            'video_category_id' => $category->id,
            'duration_seconds' => $media['duration_seconds'],
            'bitrate_kbps' => $media['bitrate_kbps'],
            'resolution' => $media['resolution'],
            'size_bytes' => @filesize($canonical) ?: null,
            'format' => strtolower((string) pathinfo($canonical, PATHINFO_EXTENSION)),
            'metadata' => $media['metadata'],
        ];

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return [
                'ok' => true,
                'message' => 'Video actualizat în library: ' . $existing->title,
                'video_id' => $existing->id,
            ];
        }

        $video = Video::create($payload);

        // Mirror existing import behavior: create an encoding job for VOD if possible.
        $channel = LiveChannel::first();
        if ($channel && !$video->encodingJobs()->exists()) {
            $outputDir = '/streams/videos';
            @mkdir($outputDir, 0755, true);
            $outputFile = $outputDir . DIRECTORY_SEPARATOR . $video->id . '.ts';

            EncodingJob::create([
                'live_channel_id' => $channel->id,
                'video_id' => $video->id,
                'input_path' => $canonical,
                'output_path' => $outputFile,
                'status' => 'queued',
            ]);
        }

        return [
            'ok' => true,
            'message' => 'Video importat în library: ' . $video->title,
            'video_id' => $video->id,
        ];
    }

    /**
     * @return array{duration_seconds:?int, bitrate_kbps:?int, resolution:?string, metadata:array}
     */
    private function probe(string $path): array
    {
        $out = [
            'duration_seconds' => null,
            'bitrate_kbps' => null,
            'resolution' => null,
            'metadata' => [],
        ];

        try {
            $cmd = sprintf(
                'ffprobe -v quiet -print_format json -show_format -show_streams %s 2>/dev/null',
                escapeshellarg($path)
            );
            $json = shell_exec($cmd);
            $data = is_string($json) ? json_decode($json, true) : null;
            if (!is_array($data)) {
                return $out;
            }

            if (isset($data['format']['duration'])) {
                $dur = (float) $data['format']['duration'];
                if ($dur > 0) {
                    $out['duration_seconds'] = (int) round($dur);
                }
            }

            if (isset($data['format']['bit_rate'])) {
                $br = (int) $data['format']['bit_rate'];
                if ($br > 0) {
                    $out['bitrate_kbps'] = (int) round($br / 1000);
                }
            }

            $video = null;
            $audio = null;
            if (isset($data['streams']) && is_array($data['streams'])) {
                foreach ($data['streams'] as $stream) {
                    if (!is_array($stream) || !isset($stream['codec_type'])) continue;
                    if ($stream['codec_type'] === 'video' && !$video) {
                        $video = $stream;
                    }
                    if ($stream['codec_type'] === 'audio' && !$audio) {
                        $audio = $stream;
                    }
                }
            }

            $meta = [
                'video' => null,
                'audio' => null,
            ];

            if (is_array($video)) {
                $w = isset($video['width']) ? (int) $video['width'] : 0;
                $h = isset($video['height']) ? (int) $video['height'] : 0;
                if ($w > 0 && $h > 0) {
                    $out['resolution'] = $w . 'x' . $h;
                }
                $meta['video'] = [
                    'codec' => $video['codec_name'] ?? 'unknown',
                    'width' => $w,
                    'height' => $h,
                ];
            }

            if (is_array($audio)) {
                $meta['audio'] = [
                    'codec' => $audio['codec_name'] ?? 'unknown',
                    'channels' => $audio['channels'] ?? 0,
                ];
            }

            $out['metadata'] = $meta;
            return $out;
        } catch (\Throwable $e) {
            return $out;
        }
    }
}
