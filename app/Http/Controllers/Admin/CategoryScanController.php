<?php

namespace App\Http\Controllers\Admin;

use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class CategoryScanController extends Controller
{
    private $allowedExtensions = ['mp4', 'mkv', 'avi', 'mov', 'webm', 'flv', 'wmv', 'ts'];

    /**
     * Show category with scan UI
     */
    public function showCategory(VideoCategory $category)
    {
        return view('admin.video_categories.scan', [
            'category' => $category,
            'videos' => [],
            'sourceStatus' => null,
        ]);
    }

    /**
     * Scan folder for videos (recursive)
     */
    public function scan(Request $request, VideoCategory $category)
    {
        $request->validate([
            'source_path' => 'required|string|min:3',
        ]);

        $sourcePath = rtrim($request->input('source_path'), '/');

        // Security: check if path exists and is readable
        if (!is_dir($sourcePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Folder not found: ' . $sourcePath,
            ], 404);
        }

        if (!is_readable($sourcePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Folder not readable: ' . $sourcePath,
            ], 403);
        }

        try {
            // Scan recursively
            $videos = $this->scanFolder($sourcePath);

            // Get already imported videos
            $importedPaths = Video::where('category_id', $category->id)
                ->pluck('file_path')
                ->toArray();

            // Mark already imported
            foreach ($videos as &$video) {
                $video['imported'] = in_array($video['file_path'], $importedPaths);
            }

            // Save source_path to category
            $category->update(['source_path' => $sourcePath]);

            return response()->json([
                'success' => true,
                'message' => count($videos) . ' video(s) found',
                'videos' => $videos,
            ]);
        } catch (\Exception $e) {
            Log::error('CategoryScan error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error scanning folder: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Scan folder recursively and extract video metadata
     */
    private function scanFolder(string $path): array
    {
        $videos = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile()) {
                    $ext = strtolower($fileinfo->getExtension());
                    if (in_array($ext, $this->allowedExtensions)) {
                        $fullPath = $fileinfo->getRealPath();
                        $duration = $this->getVideoDuration($fullPath);
                        $metadata = $this->getVideoMetadata($fullPath);

                        $videos[] = [
                            'file_path' => $fullPath,
                            'filename' => $fileinfo->getFilename(),
                            'size' => $fileinfo->getSize(),
                            'size_formatted' => $this->formatBytes($fileinfo->getSize()),
                            'duration' => $duration,
                            'modified' => $fileinfo->getMTime(),
                            'modified_formatted' => date('d M Y H:i', $fileinfo->getMTime()),
                            'metadata' => $metadata,
                            'imported' => false,
                        ];
                    }
                }
            }

            // Sort by filename
            usort($videos, fn($a, $b) => strcmp($a['filename'], $b['filename']));

            return $videos;
        } catch (\Exception $e) {
            Log::error('Scan folder error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get video duration using ffprobe
     */
    private function getVideoDuration(string $filePath): string
    {
        try {
            $cmd = sprintf(
                'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1:noprint_wrappers=1 "%s" 2>/dev/null',
                escapeshellarg($filePath)
            );

            $duration = (float) shell_exec($cmd);
            $hours = (int) ($duration / 3600);
            $minutes = (int) (($duration % 3600) / 60);
            $seconds = (int) ($duration % 60);

            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }

    /**
     * Get video metadata using ffprobe (JSON)
     */
    private function getVideoMetadata(string $filePath): array
    {
        try {
            $cmd = sprintf(
                'ffprobe -v quiet -print_format json -show_format -show_streams "%s" 2>/dev/null',
                escapeshellarg($filePath)
            );

            $json = shell_exec($cmd);
            $data = json_decode($json, true);

            $videoStream = null;
            $audioStream = null;

            if (isset($data['streams'])) {
                foreach ($data['streams'] as $stream) {
                    if ($stream['codec_type'] === 'video' && !$videoStream) {
                        $videoStream = $stream;
                    } elseif ($stream['codec_type'] === 'audio' && !$audioStream) {
                        $audioStream = $stream;
                    }
                }
            }

            return [
                'video' => $videoStream ? [
                    'codec' => $videoStream['codec_name'] ?? 'unknown',
                    'width' => $videoStream['width'] ?? 0,
                    'height' => $videoStream['height'] ?? 0,
                    'fps' => isset($videoStream['r_frame_rate']) 
                        ? round(eval('return ' . $videoStream['r_frame_rate'] . ';'), 2)
                        : 0,
                    'bitrate' => $videoStream['bit_rate'] ?? 0,
                ] : null,
                'audio' => $audioStream ? [
                    'codec' => $audioStream['codec_name'] ?? 'unknown',
                    'channels' => $audioStream['channels'] ?? 0,
                    'sample_rate' => $audioStream['sample_rate'] ?? 0,
                    'bitrate' => $audioStream['bit_rate'] ?? 0,
                ] : null,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Import selected videos to library
     */
    public function import(Request $request, VideoCategory $category)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'string',
        ]);

        $imported = [];
        $skipped = [];
        $errors = [];

        foreach ($request->input('files') as $filePath) {
            // Security: ensure file exists and is within category's source_path
            if (!file_exists($filePath) || !is_readable($filePath)) {
                $errors[] = basename($filePath) . ' - Not accessible';
                continue;
            }

            // Check if source_path is set and file is within it
            if ($category->source_path && !str_starts_with($filePath, $category->source_path)) {
                $errors[] = basename($filePath) . ' - Path mismatch';
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
                $errors[] = basename($filePath) . ' - ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'imported' => count($imported),
            'skipped' => count($skipped),
            'errors' => count($errors),
            'message' => count($imported) . ' imported, ' . count($skipped) . ' skipped, ' . count($errors) . ' errors',
            'details' => [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ],
        ]);
    }

    /**
     * Delete file from disk
     */
    public function deleteFile(Request $request, VideoCategory $category)
    {
        $request->validate([
            'file_path' => 'required|string',
        ]);

        $filePath = $request->input('file_path');

        // Security: ensure file is within category's source_path
        if ($category->source_path && !str_starts_with($filePath, $category->source_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File path not in category source folder',
            ], 403);
        }

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        try {
            unlink($filePath);

            // Also remove from database if it exists
            Video::where('file_path', $filePath)->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted: ' . basename($filePath),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get file info (for modal)
     */
    public function fileInfo(Request $request, VideoCategory $category)
    {
        $request->validate([
            'file_path' => 'required|string',
        ]);

        $filePath = $request->input('file_path');

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        $metadata = $this->getVideoMetadata($filePath);
        $fileinfo = new SplFileInfo($filePath);

        return response()->json([
            'success' => true,
            'file' => [
                'path' => $filePath,
                'name' => $fileinfo->getFilename(),
                'size' => $this->formatBytes($fileinfo->getSize()),
                'duration' => $this->getVideoDuration($filePath),
                'created' => date('d M Y H:i', $fileinfo->getMTime()),
                'metadata' => $metadata,
            ],
        ]);
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
