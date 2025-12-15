<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class MediaImportController extends Controller
{
    private $mediaRoot;
    private $allowedExtensions = ['mp4', 'mkv', 'avi', 'mov', 'webm', 'flv', 'wmv', 'ts'];

    public function __construct()
    {
        $this->mediaRoot = config('app.media_root', env('MEDIA_ROOT', storage_path('app/media')));
    }

    /**
     * Show import page with file browser
     */
    public function index(Request $request)
    {
        $path = $request->query('path', '');
        $search = $request->query('search', '');
        
        // Security: prevent directory traversal
        if (str_contains($path, '..')) {
            abort(403, 'Directory traversal not allowed');
        }

        $fullPath = $this->mediaRoot . ($path ? "/$path" : '');

        // Ensure path exists and is readable
        if (!is_dir($fullPath)) {
            return redirect()->route('media.import')->with('error', 'Directory not found');
        }

        if (!is_readable($fullPath)) {
            return redirect()->route('media.import')->with('error', 'Directory not readable');
        }

        $items = [];
        $parent = null;

        try {
            // Get current directory contents
            $files = scandir($fullPath);
            
            // Get parent directory
            if ($path) {
                $parent = dirname($path);
                if ($parent === '.') {
                    $parent = null;
                }
            }

            foreach ($files as $file) {
                if (in_array($file, ['.', '..'])) {
                    continue;
                }

                $fullFilePath = "$fullPath/$file";
                
                if (is_dir($fullFilePath)) {
                    $items[] = [
                        'type' => 'folder',
                        'name' => $file,
                        'path' => $path ? "$path/$file" : $file,
                        'modified' => filemtime($fullFilePath),
                        'children_count' => count(array_diff(scandir($fullFilePath), ['.', '..'])) ?? 0,
                    ];
                } else {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    
                    // Only show allowed video extensions
                    if (!in_array($ext, $this->allowedExtensions)) {
                        continue;
                    }

                    $items[] = [
                        'type' => 'file',
                        'name' => $file,
                        'path' => $path ? "$path/$file" : $file,
                        'ext' => $ext,
                        'size' => filesize($fullFilePath),
                        'modified' => filemtime($fullFilePath),
                        'full_path' => $fullFilePath,
                    ];
                }
            }

            // Sort: folders first, then files by name
            usort($items, function ($a, $b) {
                if ($a['type'] !== $b['type']) {
                    return $a['type'] === 'folder' ? -1 : 1;
                }
                return strcasecmp($a['name'], $b['name']);
            });

            // Apply search filter
            if ($search) {
                $items = array_filter($items, function ($item) use ($search) {
                    return stripos($item['name'], $search) !== false;
                });
            }

        } catch (\Exception $e) {
            Log::error('Media import error: ' . $e->getMessage());
            return redirect()->route('media.import')->with('error', 'Error reading directory');
        }

        $categories = VideoCategory::orderBy('name')->get();
        $existingVideos = Video::pluck('file_path')->toArray();

        return view('admin.media.import', compact(
            'items',
            'path',
            'parent',
            'search',
            'categories',
            'existingVideos',
            'mediaRoot'
        ));
    }

    /**
     * Import selected videos to Video Library
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'string',
            'category_id' => 'nullable|exists:video_categories,id',
        ]);

        $files = $validated['files'];
        $categoryId = $validated['category_id'] ?? null;
        $imported = [];
        $skipped = [];
        $errors = [];

        foreach ($files as $filePath) {
            // Security: prevent directory traversal
            if (str_contains($filePath, '..') || !str_starts_with($filePath, '/')) {
                $errors[] = "Invalid path: $filePath";
                continue;
            }

            $fullPath = $this->mediaRoot . "/$filePath";

            // Verify file exists and is readable
            if (!file_exists($fullPath) || !is_readable($fullPath)) {
                $errors[] = "File not found or not readable: $filePath";
                continue;
            }

            // Check if already imported
            if (Video::where('file_path', $fullPath)->exists()) {
                $skipped[] = basename($filePath);
                continue;
            }

            try {
                $filename = basename($filePath);
                $duration = $this->getVideoDuration($fullPath);
                $metadata = $this->getVideoMetadata($fullPath);

                $video = Video::create([
                    'title' => pathinfo($filename, PATHINFO_FILENAME),
                    'file_path' => $fullPath,
                    'filename' => $filename,
                    'size' => filesize($fullPath),
                    'duration' => $duration,
                    'video_category_id' => $categoryId,
                    'metadata' => json_encode($metadata ?? []),
                    'encoding_status' => 'pending',
                ]);

                $imported[] = $video->title;

            } catch (\Exception $e) {
                Log::error("Import error for $filePath: " . $e->getMessage());
                $errors[] = "Failed to import: $filename - " . $e->getMessage();
            }
        }

        $message = count($imported) . ' videos imported';
        if (count($skipped) > 0) {
            $message .= ', ' . count($skipped) . ' skipped (already imported)';
        }

        return redirect()->route('media.import')
            ->with('success', $message)
            ->with('imported', $imported)
            ->with('skipped', $skipped)
            ->with('errors', $errors);
    }

    /**
     * Get video duration using ffprobe
     */
    private function getVideoDuration($filePath): ?string
    {
        try {
            $output = shell_exec(sprintf(
                "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1:noinvert_list=1 %s 2>/dev/null",
                escapeshellarg($filePath)
            ));

            if ($output) {
                $seconds = (int) $output;
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                $secs = $seconds % 60;
                return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
            }
        } catch (\Exception $e) {
            Log::warning("Could not get duration for $filePath: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get video metadata using ffprobe
     */
    private function getVideoMetadata($filePath): ?array
    {
        try {
            $jsonOutput = shell_exec(sprintf(
                "ffprobe -v error -print_format json -show_streams %s 2>/dev/null",
                escapeshellarg($filePath)
            ));

            if ($jsonOutput) {
                $data = json_decode($jsonOutput, true);
                $streams = $data['streams'] ?? [];

                $metadata = [];

                // Video stream
                foreach ($streams as $stream) {
                    if ($stream['codec_type'] === 'video') {
                        $metadata['video'] = [
                            'codec' => $stream['codec_name'] ?? null,
                            'width' => $stream['width'] ?? null,
                            'height' => $stream['height'] ?? null,
                            'fps' => $stream['r_frame_rate'] ?? null,
                            'bitrate' => $stream['bit_rate'] ?? null,
                        ];
                    }
                    if ($stream['codec_type'] === 'audio') {
                        $metadata['audio'][] = [
                            'codec' => $stream['codec_name'] ?? null,
                            'channels' => $stream['channels'] ?? null,
                            'sample_rate' => $stream['sample_rate'] ?? null,
                            'bitrate' => $stream['bit_rate'] ?? null,
                        ];
                    }
                }

                return $metadata;
            }
        } catch (\Exception $e) {
            Log::warning("Could not get metadata for $filePath: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get allowed extensions
     */
    public function getExtensions()
    {
        return $this->allowedExtensions;
    }
}
