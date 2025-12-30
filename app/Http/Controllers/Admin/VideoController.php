<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Video;
use App\Models\VideoCategory;
use App\Services\TmdbService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

class VideoController extends Controller
{
    /**
     * VIDEO LIBRARY
     *
     * - când intri pe /videos:
     *   1. scanează directorul configurat (env: IPTV_VIDEO_DIR) pentru fișiere .mp4
     *   2. pentru fiecare fișier care NU există în DB, îl adaugă automat
     *   3. apoi afișează lista de video-uri din tabelul `videos`
     */
    public function index()
    {
        $videoDir = rtrim(env('IPTV_VIDEO_DIR', storage_path('app/videos')), '/');

        if (is_dir($videoDir)) {
            // toate fișierele .mp4 din director
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

    /**
     * Get video metadata for modal display
     */
    public function getInfo(Video $video, TmdbService $tmdb)
    {
        try {
            $video->loadMissing('category');

            $durationSeconds = (int) ($video->duration_seconds ?? 0);
            $sizeBytes = (int) ($video->size_bytes ?? 0);

            $tmdbPayload = null;
            $tmdbId = (int) ($video->tmdb_id ?? 0);

            if ($tmdbId > 0) {
                $apiKey = (string) AppSetting::getValue('tmdb_api_key', (string) env('TMDB_API_KEY', ''));
                if (trim($apiKey) !== '') {
                    $res = $tmdb->getMovieDetails($apiKey, $tmdbId);
                    if (($res['ok'] ?? false) === true) {
                        $tmdbPayload = [
                            'ok' => true,
                            'id' => $res['id'] ?? $tmdbId,
                            'title' => $res['title'] ?? null,
                            'original_title' => $res['original_title'] ?? null,
                            'overview' => $res['overview'] ?? null,
                            'release_date' => $res['release_date'] ?? null,
                            'runtime' => $res['runtime'] ?? null,
                            'genres' => $res['genres'] ?? [],
                            'vote_average' => $res['vote_average'] ?? null,
                            'vote_count' => $res['vote_count'] ?? null,
                            'homepage' => $res['homepage'] ?? null,
                            'imdb_id' => $res['imdb_id'] ?? null,
                            'poster_url' => !empty($res['poster_path']) ? ('https://image.tmdb.org/t/p/w342' . $res['poster_path']) : null,
                            'backdrop_url' => !empty($res['backdrop_path']) ? ('https://image.tmdb.org/t/p/w780' . $res['backdrop_path']) : null,
                        ];
                    } else {
                        $tmdbPayload = [
                            'ok' => false,
                            'message' => $res['message'] ?? 'TMDB fetch failed',
                        ];
                    }
                } else {
                    $tmdbPayload = [
                        'ok' => false,
                        'message' => 'TMDB key missing. Set it in TMDB Settings.',
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'video' => [
                    'id' => (int) $video->id,
                    'title' => (string) ($video->title ?? ''),
                    'file_path' => (string) ($video->file_path ?? ''),
                    'category' => (string) ($video->category?->name ?? 'Uncategorized'),
                    'duration_seconds' => $durationSeconds,
                    'duration' => $durationSeconds > 0 ? gmdate('H:i:s', $durationSeconds) : null,
                    'bitrate_kbps' => $video->bitrate_kbps !== null ? (int) $video->bitrate_kbps : null,
                    'resolution' => $video->resolution,
                    'size_bytes' => $sizeBytes > 0 ? $sizeBytes : null,
                    'format' => $video->format,
                    'tmdb_id' => $tmdbId > 0 ? $tmdbId : null,
                    'tmdb_poster_url' => !empty($video->tmdb_poster_path) ? ('https://image.tmdb.org/t/p/w185' . $video->tmdb_poster_path) : null,
                    'tmdb_backdrop_url' => !empty($video->tmdb_backdrop_path) ? ('https://image.tmdb.org/t/p/w780' . $video->tmdb_backdrop_path) : null,
                ],
                'tmdb' => $tmdbPayload,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream/play the original video file.
     * This is auth-protected via routes/web.php.
     */
    public function play(Video $video)
    {
        $path = (string) ($video->file_path ?? '');
        if ($path === '' || !file_exists($path)) {
            abort(404);
        }

        $mime = MimeTypes::getDefault()->guessMimeType($path) ?? 'application/octet-stream';
        $filename = basename($path) ?: 'video';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
