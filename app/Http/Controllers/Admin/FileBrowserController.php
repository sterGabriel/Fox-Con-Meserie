<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\EncodingJob;
use App\Models\LiveChannel;

class FileBrowserController extends Controller
{
    // ROOT: /media - can browse all subfolders
    private string $basePath = '/media';
    private array $allowedExtensions = ['mp4', 'mkv', 'avi', 'mov', 'webm', 'flv', 'wmv', 'ts'];

    public function browse(VideoCategory $category, Request $request)
    {
        $requested = $request->query('path', $this->basePath);

        // Path real
        $realBase = realpath($this->basePath) ?: $this->basePath;
        $realPath = realpath($requested) ?: $realBase;

        // Securitate: NU permitem acces în afara folderului de bază
        if (strpos($realPath, $realBase) !== 0) {
            $realPath = $realBase;
        }

        $dirs  = [];
        $files = [];

        // Get already imported videos for this category
        $importedPaths = Video::where('video_category_id', $category->id)
            ->pluck('file_path')
            ->toArray();

        if (is_dir($realPath)) {
            foreach (scandir($realPath) as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $full = $realPath . DIRECTORY_SEPARATOR . $entry;
                if (!is_readable($full)) continue;

                if (is_dir($full)) {
                    $dirs[] = [
                        'name' => $entry,
                        'path' => $full,
                        'type' => 'folder',
                    ];
                } else {
                    $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                    if (!in_array($ext, $this->allowedExtensions)) {
                        continue;
                    }

                    $isImported = in_array($full, $importedPaths);
                    $files[] = [
                        'name' => $entry,
                        'path' => $full,
                        'type' => 'file',
                        'size' => filesize($full),
                        'size_formatted' => $this->formatBytes(filesize($full)),
                        'duration' => $this->getVideoDuration($full),
                        'metadata' => $this->getVideoMetadata($full),
                        'imported' => $isImported,
                    ];
                }
            }
        }

        // Sort: folders first, then files
        usort($dirs, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        $parent = $realBase;
        if ($realPath !== $realBase) {
            $parent = dirname($realPath);
        }

        // Breadcrumb
        $breadcrumb = $this->getBreadcrumb($realPath, $realBase);

        return view('admin.video_categories.browse', [
            'category' => $category,
            'currentPath' => $realPath,
            'basePath'    => $realBase,
            'parentPath'  => $parent,
            'dirs'        => $dirs,
            'files'       => $files,
            'breadcrumb'  => $breadcrumb,
        ]);
    }

    /**
     * Import selected files (POST handler)
     */
    public function import(Request $request, VideoCategory $category)
    {
        $files = $request->input('files', []);
        
        if (empty($files)) {
            return redirect()
                ->back()
                ->with('error', 'No files selected');
        }

        $imported = 0;
        $errors = 0;

        foreach ($files as $filePath) {
            // Security check: path must be under /media
            if (!$this->isPathAllowed($filePath)) {
                \Log::warning("Attempted import from unauthorized path: $filePath");
                $errors++;
                continue;
            }

            // Check if file exists on disk
            if (!file_exists($filePath) || !is_readable($filePath)) {
                \Log::warning("File not accessible: $filePath");
                $errors++;
                continue;
            }

            // Check if already imported
            if (Video::where('file_path', $filePath)->exists()) {
                \Log::info("File already imported: $filePath");
                continue;
            }

            try {
                $metadata = $this->getVideoMetadata($filePath);
                $duration = $this->getVideoDuration($filePath);

                // Create video record in DB
                $video = Video::create([
                    'title' => pathinfo($filePath, PATHINFO_FILENAME),
                    'file_path' => $filePath,
                    'duration_seconds' => $this->parseDurationToSeconds($duration),
                    'video_category_id' => $category->id,
                    'metadata' => json_encode($metadata),
                    'format' => pathinfo($filePath, PATHINFO_EXTENSION),
                ]);

                // Create encoding job for offline encoding to TS
                $channel = LiveChannel::first(); // Use first VOD channel
                if ($channel) {
                    $outputDir = '/streams/videos';
                    @mkdir($outputDir, 0755, true);
                    $outputFile = $outputDir . DIRECTORY_SEPARATOR . $video->id . '.ts';

                    EncodingJob::create([
                        'live_channel_id' => $channel->id,
                        'video_id' => $video->id,
                        'input_path' => $filePath,
                        'output_path' => $outputFile,
                        'status' => 'queued',
                    ]);
                }

                $imported++;
                \Log::info("Video imported: {$video->title} → {$filePath}");

            } catch (\Exception $e) {
                \Log::error("Import failed for {$filePath}: " . $e->getMessage());
                $errors++;
            }
        }

        // Build message
        $message = "Imported: $imported video" . ($imported !== 1 ? 's' : '');
        if ($errors > 0) {
            $message .= " | Errors: $errors";
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Check if path is allowed
     */
    private function isPathAllowed($path)
    {
        $realBase = realpath($this->basePath) ?: $this->basePath;
        $realPath = realpath($path) ?: $path;

        if (strpos($realPath, $realBase) !== 0) {
            return false;
        }

        return true;
    }

    /**
     * Get breadcrumb navigation
     */
    private function getBreadcrumb($currentPath, $basePath)
    {
        $breadcrumb = [];
        $parts = explode(DIRECTORY_SEPARATOR, trim(str_replace($basePath, '', $currentPath), DIRECTORY_SEPARATOR));

        $current = $basePath;
        $breadcrumb[] = [
            'name' => basename($basePath) ?: 'Videos',
            'path' => $basePath,
        ];

        foreach ($parts as $part) {
            if (empty($part)) continue;
            $current .= DIRECTORY_SEPARATOR . $part;
            $breadcrumb[] = [
                'name' => $part,
                'path' => $current,
            ];
        }

        return $breadcrumb;
    }

    /**
     * Get video duration
     */
    private function getVideoDuration($filePath)
    {
        try {
            $cmd = sprintf(
                'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>/dev/null',
                escapeshellarg($filePath)
            );
            $duration = (float) shell_exec($cmd);
            if ($duration > 0) {
                $h = (int) ($duration / 3600);
                $m = (int) (($duration % 3600) / 60);
                $s = (int) ($duration % 60);
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            }
            return '00:00:00';
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }

    /**
     * Get video metadata (resolution, codecs)
     */
    private function getVideoMetadata($filePath)
    {
        try {
            $cmd = sprintf(
                'ffprobe -v quiet -print_format json -show_format -show_streams %s 2>/dev/null',
                escapeshellarg($filePath)
            );
            $json = shell_exec($cmd);
            $data = json_decode($json, true);

            $video = null;
            $audio = null;

            if (isset($data['streams'])) {
                foreach ($data['streams'] as $stream) {
                    if ($stream['codec_type'] === 'video' && !$video) {
                        $video = $stream;
                    } elseif ($stream['codec_type'] === 'audio' && !$audio) {
                        $audio = $stream;
                    }
                }
            }

            return [
                'video' => $video ? [
                    'codec' => $video['codec_name'] ?? 'unknown',
                    'width' => $video['width'] ?? 0,
                    'height' => $video['height'] ?? 0,
                ] : null,
                'audio' => $audio ? [
                    'codec' => $audio['codec_name'] ?? 'unknown',
                    'channels' => $audio['channels'] ?? 0,
                ] : null,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Convert HH:MM:SS to seconds
     */
    private function parseDurationToSeconds($duration)
    {
        $parts = explode(':', $duration);
        if (count($parts) == 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }
        return 0;
    }

    /**
     * Generate 10-second preview
     */
    public function generatePreview(Request $request)
    {
        $filePath = $request->input('path');
        
        // Security check
        if (!$this->isPathAllowed($filePath) || !file_exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'Invalid path'], 403);
        }

        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $previewDir = storage_path('app/public/previews');
        @mkdir($previewDir, 0755, true);

        $previewFile = $previewDir . DIRECTORY_SEPARATOR . $filename . '_preview_10s.mp4';
        $previewUrl = '/storage/previews/' . basename($previewFile);

        // Generate if doesn't exist
        if (!file_exists($previewFile)) {
            $cmd = sprintf(
                'ffmpeg -i %s -t 10 -c:v libx264 -preset ultrafast -crf 28 -c:a aac -b:a 128k -y %s 2>/dev/null',
                escapeshellarg($filePath),
                escapeshellarg($previewFile)
            );
            
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0 || !file_exists($previewFile)) {
                return response()->json(['success' => false, 'message' => 'Preview generation failed'], 500);
            }
        }

        return response()->json([
            'success' => true,
            'url' => $previewUrl,
            'path' => $previewFile,
        ]);
    }
}

