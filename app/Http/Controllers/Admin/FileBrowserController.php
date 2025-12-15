<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Http\Request;

class FileBrowserController extends Controller
{
    // Schimbă dacă videourile sunt în alt folder:
    private string $basePath = '/home/movies';
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
        $importedPaths = Video::where('category_id', $category->id)
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
     * Import selected files
     */
    public function import(Request $request, VideoCategory $category)
    {
        $files = $request->input('files', []);
        if (empty($files)) {
            return response()->json(['success' => false, 'message' => 'No files selected']);
        }

        $imported = [];
        $skipped = [];
        $errors = [];

        foreach ($files as $filePath) {
            // Security check
            if (!$this->isPathAllowed($filePath)) {
                $errors[] = basename($filePath) . ' - Invalid path';
                continue;
            }

            if (!file_exists($filePath) || !is_readable($filePath)) {
                $errors[] = basename($filePath) . ' - Not accessible';
                continue;
            }

            // Check if already imported
            if (Video::where('file_path', $filePath)->exists()) {
                $skipped[] = basename($filePath);
                continue;
            }

            try {
                $metadata = $this->getVideoMetadata($filePath);
                $duration = $this->getVideoDuration($filePath);

                Video::create([
                    'title' => pathinfo($filePath, PATHINFO_FILENAME),
                    'file_path' => $filePath,
                    'duration' => $duration,
                    'category_id' => $category->id,
                    'metadata' => json_encode($metadata),
                    'status' => 'pending',
                ]);

                $imported[] = basename($filePath);
            } catch (\Exception $e) {
                \Log::error('Video import failed: ' . $e->getMessage());
                $errors[] = basename($filePath) . ' - ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'imported' => count($imported),
            'skipped' => count($skipped),
            'errors' => count($errors),
            'message' => count($imported) . ' imported, ' . count($skipped) . ' skipped',
        ]);
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
                'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "%s" 2>/dev/null',
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
                'ffprobe -v quiet -print_format json -show_format -show_streams "%s" 2>/dev/null',
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
}
