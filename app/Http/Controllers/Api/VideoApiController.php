<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Video;
use App\Services\VideoProbeService;
use App\Services\TmdbService;
use Illuminate\Http\Request;

class VideoApiController extends Controller
{
    /**
     * Get videos by category
     * GET /api/videos?category_id=X
     */
    public function index(Request $request)
    {
        $categoryId = $request->query('category_id');
        
        if (!$categoryId) {
            return response()->json([], 200);
        }

        $videos = Video::query()
            ->where('video_category_id', (int)$categoryId)
            ->orderByDesc('id')
            ->limit(1000)
            ->get([
                'id', 'title', 'file_path', 'duration_seconds', 
                'bitrate_kbps', 'resolution', 'size_bytes', 'format',
                'tmdb_id', 'tmdb_poster_path', 'tmdb_backdrop_path'
            ]);

        return response()->json($videos);
    }

    /**
     * Auto fetch poster using TMDB.
     * POST /api/videos/tmdb-scan
     * Body: { video_ids: [1,2,3] }
     */
    public function tmdbScan(Request $request, TmdbService $tmdb)
    {
        $data = $request->validate([
            'video_ids' => ['required', 'array', 'min:1', 'max:10'],
            'video_ids.*' => ['integer', 'exists:videos,id'],
        ]);

        $apiKey = (string) AppSetting::getValue('tmdb_api_key', (string) env('TMDB_API_KEY', ''));
        if (trim($apiKey) === '') {
            return response()->json([
                'ok' => false,
                'message' => 'TMDB key missing. Set it in TMDB Settings.',
            ], 422);
        }

        $videos = Video::query()
            ->whereIn('id', $data['video_ids'])
            ->get(['id', 'title', 'tmdb_id', 'tmdb_poster_path', 'tmdb_backdrop_path']);

        $results = [];

        foreach ($videos as $video) {
            $parsed = $tmdb->parseTitle((string)($video->title ?? ''));
            $res = $tmdb->searchMovie($apiKey, (string)($parsed['title'] ?? ''), $parsed['year'] ?? null);

            if (($res['ok'] ?? false) === true && !empty($res['tmdb_id'])) {
                $video->update([
                    'tmdb_id' => (int) $res['tmdb_id'],
                    'tmdb_poster_path' => $res['poster_path'] ?? null,
                    'tmdb_backdrop_path' => $res['backdrop_path'] ?? null,
                ]);
            }

            $results[] = [
                'id' => $video->id,
                'title' => $video->title,
                'ok' => (bool) ($res['ok'] ?? false),
                'message' => $res['message'] ?? null,
                'tmdb_id' => $res['tmdb_id'] ?? null,
                'tmdb_poster_path' => $res['poster_path'] ?? null,
            ];
        }

        return response()->json([
            'ok' => true,
            'results' => $results,
        ]);
    }

    /**
     * Delete a video
     * DELETE /api/videos/{video}
     */
    public function destroy(Video $video)
    {
        // Optional: Check if video has encoding jobs (prevent deletion if jobs exist)
        // if ($video->encodingJobs()->exists()) {
        //     return response()->json(['message' => 'Cannot delete video with active encoding jobs'], 409);
        // }

        $video->delete();
        return response()->json(['message' => 'Video deleted successfully']);
    }

    /**
     * Probe videos metadata using ffprobe (FFmpeg)
     * POST /api/videos/probe
     * Body: { video_ids: [1,2,3] }
     */
    public function probe(Request $request, VideoProbeService $probe)
    {
        $data = $request->validate([
            'video_ids' => ['required', 'array', 'min:1', 'max:10'],
            'video_ids.*' => ['integer', 'exists:videos,id'],
        ]);

        if (!$probe->ffprobeAvailable()) {
            return response()->json([
                'ok' => false,
                'message' => 'ffprobe not available on server',
            ], 422);
        }

        $videos = Video::query()
            ->whereIn('id', $data['video_ids'])
            ->get(['id', 'file_path', 'title', 'duration_seconds', 'bitrate_kbps', 'resolution', 'size_bytes', 'format']);

        $results = [];

        foreach ($videos as $video) {
            $res = $probe->probe($video);

            if (($res['ok'] ?? false) === true) {
                $video->update([
                    'duration_seconds' => $res['duration_seconds'],
                    'bitrate_kbps' => $res['bitrate_kbps'],
                    'resolution' => $res['resolution'],
                    'size_bytes' => $res['size_bytes'],
                    'format' => $res['format'],
                ]);
            }

            $results[] = [
                'id' => $video->id,
                'title' => $video->title,
                'ok' => (bool) ($res['ok'] ?? false),
                'message' => $res['message'] ?? null,
                'duration_seconds' => $res['duration_seconds'] ?? null,
                'resolution' => $res['resolution'] ?? null,
            ];
        }

        return response()->json([
            'ok' => true,
            'results' => $results,
        ]);
    }
}
