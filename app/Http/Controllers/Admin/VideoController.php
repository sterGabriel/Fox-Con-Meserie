<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    /**
     * VIDEO LIBRARY
     *
     * - când intri pe /videos:
     *   1. scanează /home/videos pentru fișiere .mp4
     *   2. pentru fiecare fișier care NU există în DB, îl adaugă automat
     *   3. apoi afișează lista de video-uri din tabelul `videos`
     */
    public function index()
    {
        $videoDir = '/home/videos';

        if (is_dir($videoDir)) {
            // toate fișierele .mp4 din /home/videos
            $files = glob($videoDir . '/*.mp4');

            foreach ($files as $path) {
                // dacă nu există deja în DB cu acest file_path, îl creăm
                if (! Video::where('file_path', $path)->exists()) {
                    $filename = basename($path);

                    // facem un titlu frumos din numele fișierului
                    $title = Str::of(pathinfo($filename, PATHINFO_FILENAME))
                        ->replace(['-', '_'], ' ')
                        ->title();

                    Video::create([
                        'title'             => $title,
                        'file_path'         => $path,
                        'video_category_id' => null, // implicit fără categorie
                    ]);
                }
            }
        }

        // după sync, luăm din DB ce avem
        $videos = Video::with('category')
            ->orderBy('id', 'desc')
            ->get();

        $categories = VideoCategory::orderBy('name')->get();

        return view('admin.videos.index', [
            'videos'     => $videos,
            'categories' => $categories,
        ]);
    }

    /**
     * FORMULAR MANUAL (Create Video)
     */
    public function create()
    {
        $categories = VideoCategory::orderBy('name')->get();

        return view('admin.videos.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * SALVARE MANUALĂ VIDEO (cu categorie)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'             => ['required', 'string', 'max:255'],
            'file_path'         => ['required', 'string', 'max:1024', 'unique:videos,file_path'],
            'video_category_id' => ['nullable', 'integer', 'exists:video_categories,id'],
        ]);

        Video::create($data);

        return redirect()
            ->route('videos.index')
            ->with('success', 'Video added.');
    }

    /**
     * EDIT VIDEO (alegi și categoria)
     */
    public function edit(Video $video)
    {
        $categories = VideoCategory::orderBy('name')->get();

        return view('admin.videos.edit', [
            'video'      => $video,
            'categories' => $categories,
        ]);
    }

    /**
     * UPDATE VIDEO
     */
    public function update(Request $request, Video $video)
    {
        $data = $request->validate([
            'title'             => ['required', 'string', 'max:255'],
            'file_path'         => ['required', 'string', 'max:1024', 'unique:videos,file_path,' . $video->id],
            'video_category_id' => ['nullable', 'integer', 'exists:video_categories,id'],
        ]);

        $video->update($data);

        return redirect()
            ->route('videos.index')
            ->with('success', 'Video updated.');
    }

    /**
     * SETEAZĂ CATEGORIA pentru MAI MULTE VIDEOURI odată
     */
    public function bulkCategory(Request $request)
    {
        $data = $request->validate([
            'video_category_id' => ['nullable', 'integer', 'exists:video_categories,id'],
            'video_ids'         => ['required', 'array'],
            'video_ids.*'       => ['integer', 'exists:videos,id'],
        ]);

        $categoryId = $data['video_category_id'] ?? null;

        Video::whereIn('id', $data['video_ids'])
            ->update(['video_category_id' => $categoryId]);

        return redirect()
            ->route('videos.index')
            ->with('success', 'Category updated for selected videos.');
    }

    /**
     * FFPROBE - Get video metadata (codec, bitrate, resolution, duration, etc)
     */
    public function probe(Video $video)
    {
        if (!$video->file_path || !file_exists($video->file_path)) {
            return response()->json([
                'error' => 'Video file not found',
            ], 404);
        }

        try {
            // Build ffprobe command to get JSON output
            $cmd = 'ffprobe -v quiet -print_format json -show_format -show_streams ' . escapeshellarg($video->file_path);
            
            $output = shell_exec($cmd);
            if (!$output) {
                return response()->json([
                    'error' => 'ffprobe failed to analyze video',
                ], 500);
            }

            $data = json_decode($output, true);

            // Extract relevant stream info
            $videoStream = null;
            $audioStream = null;
            
            foreach ($data['streams'] ?? [] as $stream) {
                if ($stream['codec_type'] === 'video' && !$videoStream) {
                    $videoStream = $stream;
                } elseif ($stream['codec_type'] === 'audio' && !$audioStream) {
                    $audioStream = $stream;
                }
            }

            // Build response
            $response = [
                'title' => $video->title,
                'file' => basename($video->file_path),
                'duration' => isset($data['format']['duration']) ? (float)$data['format']['duration'] : null,
                'bit_rate' => isset($data['format']['bit_rate']) ? (int)$data['format']['bit_rate'] / 1000 . ' kbps' : null,
                'video' => null,
                'audio' => null,
            ];

            if ($videoStream) {
                $response['video'] = [
                    'codec' => $videoStream['codec_name'] ?? 'unknown',
                    'codec_long' => $videoStream['codec_long_name'] ?? 'unknown',
                    'width' => $videoStream['width'] ?? null,
                    'height' => $videoStream['height'] ?? null,
                    'fps' => isset($videoStream['r_frame_rate']) 
                        ? round(eval('return ' . str_replace('/', '.0/', $videoStream['r_frame_rate']) . ';'), 2)
                        : null,
                    'bitrate' => isset($videoStream['bit_rate']) ? ((int)$videoStream['bit_rate'] / 1000) . ' kbps' : 'N/A',
                    'duration' => $videoStream['duration'] ?? null,
                    'pix_fmt' => $videoStream['pix_fmt'] ?? 'unknown',
                ];
            }

            if ($audioStream) {
                $response['audio'] = [
                    'codec' => $audioStream['codec_name'] ?? 'unknown',
                    'codec_long' => $audioStream['codec_long_name'] ?? 'unknown',
                    'channels' => $audioStream['channels'] ?? 0,
                    'sample_rate' => isset($audioStream['sample_rate']) ? ((int)$audioStream['sample_rate'] / 1000) . ' kHz' : 'unknown',
                    'bitrate' => isset($audioStream['bit_rate']) ? ((int)$audioStream['bit_rate'] / 1000) . ' kbps' : 'N/A',
                    'duration' => $audioStream['duration'] ?? null,
                ];
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Probe error: ' . $e->getMessage(),
            ], 500);
        }
    }
}

