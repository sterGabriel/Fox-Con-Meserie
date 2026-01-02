<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\EncodingJob;
use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use App\Models\Video;
use App\Jobs\TmdbSyncVideosJob;
use App\Services\EncodingService;
use App\Services\VideoProbeService;
use App\Services\TmdbService;
use App\Services\TmdbSyncService;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class VideoApiController extends Controller
{
    /**
     * Get videos by category
     * GET /api/videos?category_id=X
     */
    public function index(Request $request)
    {
        $categoryId = $request->query('category_id');
        $channelId = (int) $request->query('channel_id', 0);
        $limit = (int) $request->query('limit', 1000);
        if ($limit <= 0) $limit = 1000;
        if ($limit > 1000) $limit = 1000;

        $excludeInPlaylist = (string) $request->query('exclude_in_playlist', '0');
        $excludeInPlaylist = in_array(strtolower($excludeInPlaylist), ['1', 'true', 'yes', 'on'], true);

        $excludeEncoded = (string) $request->query('exclude_encoded', '0');
        $excludeEncoded = in_array(strtolower($excludeEncoded), ['1', 'true', 'yes', 'on'], true);
        
        if (!$categoryId) {
            return response()->json([], 200);
        }

        $excludeVideoIds = [];

        // Optional: hide videos that are already present in this channel's playlist.
        // This prevents accidental duplicates and makes the selection list reflect what's still available.
        if ($channelId > 0 && $excludeInPlaylist) {
            $inPlaylistIds = PlaylistItem::query()
                ->where(function ($q) use ($channelId) {
                    $q->where('live_channel_id', $channelId)
                      ->orWhere('vod_channel_id', $channelId);
                })
                ->whereNotNull('video_id')
                ->distinct()
                ->pluck('video_id')
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->values()
                ->all();

            if (!empty($inPlaylistIds)) {
                $excludeVideoIds = array_values(array_unique(array_merge($excludeVideoIds, $inPlaylistIds)));
            }
        }

        // Optional: hide videos already encoded (TS-ready) for this channel.
        // This is used by the per-channel "Encoding / Import" screen to avoid showing work that's already done.
        if ($channelId > 0 && $excludeEncoded) {
            $streamsDir = storage_path('app/streams/' . $channelId);

            if (is_dir($streamsDir)) {
                $items = PlaylistItem::query()
                    ->where(function ($q) use ($channelId) {
                        $q->where('live_channel_id', $channelId)
                          ->orWhere('vod_channel_id', $channelId);
                    })
                    ->whereNotNull('video_id')
                    ->get(['id', 'video_id']);

                $encodedByVideoId = [];
                foreach ($items as $item) {
                    $vid = (int) ($item->video_id ?? 0);
                    if ($vid <= 0) continue;

                    // Primary naming: video_{playlist_item_id}.ts
                    $primary = $streamsDir . '/video_' . (int) $item->id . '.ts';
                    // Legacy/stable fallback: video_{video_id}.ts
                    $fallback = $streamsDir . '/video_' . $vid . '.ts';

                    if (is_file($primary) || is_file($fallback)) {
                        $encodedByVideoId[$vid] = true;
                    }
                }

                $excludeVideoIds = array_values(array_unique(array_merge($excludeVideoIds, array_keys($encodedByVideoId))));
            }
        }

        $videosQuery = Video::query()
            ->where('video_category_id', (int)$categoryId)
            ->orderByDesc('id');

        if (!empty($excludeVideoIds)) {
            $videosQuery->whereNotIn('id', $excludeVideoIds);
        }

        $videos = $videosQuery
            ->limit($limit)
            ->get([
                'id', 'title', 'file_path', 'duration_seconds',
                'bitrate_kbps', 'resolution', 'size_bytes', 'format',
                'tmdb_id', 'tmdb_poster_path', 'tmdb_backdrop_path'
            ]);

        return response()->json($videos);
    }

    /**
     * Extract a preview frame from the video.
     * GET /api/videos/{video}/preview-frame?ss=2
     */
    public function previewFrame(Request $request, Video $video)
    {
        $filePath = (string) ($video->file_path ?? '');
        if ($filePath === '' || !is_file($filePath)) {
            return response()->json([
                'ok' => false,
                'message' => 'Video file not found on disk.',
            ], 404);
        }

        $dur = (int) ($video->duration_seconds ?? 0);
        if ($request->has('ss')) {
            $ss = (float) $request->query('ss', 2);
        } else {
            // Pick a more representative frame than the first seconds (often black).
            $ss = $dur > 0 ? max(2.0, min(600.0, round($dur * 0.10))) : 30.0;
        }
        if (!is_finite($ss) || $ss < 0) $ss = 2;
        if ($dur > 0) {
            $maxWithin = max(0, $dur - 2);
            if ($ss > $maxWithin) $ss = (float) $maxWithin;
        }
        if ($ss > 600) $ss = 600;

        $dir = storage_path('app/video_previews');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $outPath = $dir . '/video_' . (int) $video->id . '_ss' . (int) round($ss) . '.jpg';

        // Cache for a short period to avoid hammering ffmpeg.
        $ttlSeconds = 300;
        $isFresh = is_file($outPath) && (time() - (int) @filemtime($outPath) < $ttlSeconds);

        if (!$isFresh) {
            // ffmpeg -ss <time> -i <input> -frames:v 1 -q:v 2 <output>
            $process = new Process([
                'ffmpeg',
                '-y',
                '-ss', (string) $ss,
                '-i', $filePath,
                '-frames:v', '1',
                '-q:v', '2',
                $outPath,
            ]);
            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful() || !is_file($outPath)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'ffmpeg failed to extract preview frame.',
                    'error' => trim($process->getErrorOutput()),
                ], 422);
            }
        }

        return response()->file($outPath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Generate a preview frame WITH overlay applied by FFmpeg.
     * This uses the same overlay math as the final encoding pipeline.
     * POST /api/videos/{video}/overlay-preview
     * Body: { live_channel_id: int, ss?: float, settings: object }
     */
    public function overlayPreview(Request $request, Video $video)
    {
        $data = $request->validate([
            'live_channel_id' => ['required', 'integer', 'min:1'],
            'ss' => ['nullable', 'numeric', 'min:0', 'max:600'],
            'settings' => ['required', 'array'],
        ]);

        $filePath = (string) ($video->file_path ?? '');
        if ($filePath === '' || !is_file($filePath)) {
            return response()->json([
                'ok' => false,
                'message' => 'Video file not found on disk.',
            ], 404);
        }

        $channel = LiveChannel::find((int) $data['live_channel_id']);
        if (!$channel) {
            return response()->json([
                'ok' => false,
                'message' => 'Channel not found.',
            ], 404);
        }

        $dur = (int) ($video->duration_seconds ?? 0);
        if (array_key_exists('ss', $data) && $data['ss'] !== null) {
            $ss = (float) $data['ss'];
        } else {
            // Pick a more representative frame than the first seconds (often black).
            $ss = $dur > 0 ? max(2.0, min(600.0, round($dur * 0.10))) : 30.0;
        }
        if (!is_finite($ss) || $ss < 0) $ss = 2;
        if ($dur > 0) {
            $maxWithin = max(0, $dur - 2);
            if ($ss > $maxWithin) $ss = (float) $maxWithin;
        }
        if ($ss > 600) $ss = 600;

        $settings = $request->input('settings', []);
        if (!is_array($settings)) $settings = [];

        // Build a deterministic cache key.
        $sig = md5(json_encode([
            'v' => (int) $video->id,
            'c' => (int) $channel->id,
            'ss' => (int) round($ss),
            'settings' => $settings,
        ]));

        $ttlSeconds = 60;
        $relDir = 'previews/' . (int) $channel->id . '/overlay_previews';
        $absDir = storage_path('app/public/' . $relDir);
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }

        $outFile = 'video_' . (int) $video->id . '_ss' . (int) round($ss) . '_' . $sig . '.jpg';
        $outPath = $absDir . '/' . $outFile;
        $isFresh = is_file($outPath) && (time() - (int) @filemtime($outPath) < $ttlSeconds);

        if (!$isFresh) {
            // Create a lightweight job object (not persisted) to reuse EncodingService overlay math.
            $job = new EncodingJob();
            $job->id = 0;
            $job->live_channel_id = $channel->id;
            $job->channel_id = $channel->id;
            $job->video_id = $video->id;
            $job->input_path = $filePath;
            $job->settings = $settings;
            $job->setRelation('video', $video);

            $service = new EncodingService($job, $channel);
            $filterComplex = $service->buildFilterComplexForPreview();

            $tmpDir = storage_path('app/tmp');
            if (!is_dir($tmpDir)) {
                @mkdir($tmpDir, 0775, true);
            }

            $filterFile = $tmpDir . '/overlay_preview_filter_' . $sig . '.txt';
            if ($filterComplex !== '') {
                @file_put_contents($filterFile, $filterComplex);
            }

            try {
                if ($filterComplex !== '') {
                    $process = new Process([
                        'ffmpeg',
                        '-y',
                        '-ss', (string) $ss,
                        '-i', $filePath,
                        '-frames:v', '1',
                        '-filter_complex_script', $filterFile,
                        '-map', '[out]',
                        '-q:v', '2',
                        $outPath,
                    ]);
                } else {
                    // No overlay enabled; fallback to a raw frame.
                    $process = new Process([
                        'ffmpeg',
                        '-y',
                        '-ss', (string) $ss,
                        '-i', $filePath,
                        '-frames:v', '1',
                        '-q:v', '2',
                        $outPath,
                    ]);
                }

                $process->setTimeout(30);
                $process->run();

                if (!$process->isSuccessful() || !is_file($outPath)) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'ffmpeg failed to generate overlay preview.',
                        'error' => trim($process->getErrorOutput()),
                    ], 422);
                }
            } finally {
                if (is_file($filterFile ?? '')) {
                    @unlink($filterFile);
                }
            }
        }

        return response()->json([
            'ok' => true,
            'preview_url' => '/storage/' . $relDir . '/' . $outFile,
        ], 200);
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
            ->get(['id', 'title', 'file_path', 'tmdb_id', 'tmdb_type', 'tmdb_poster_path', 'tmdb_backdrop_path', 'tmdb_genres']);

        $results = [];

        $sync = app(TmdbSyncService::class);

        foreach ($videos as $video) {
            $res = $sync->syncVideo($video, $apiKey);

            $results[] = [
                'id' => $video->id,
                'title' => $video->title,
                'ok' => (bool) ($res['ok'] ?? false),
                'message' => $res['message'] ?? null,
                'tmdb_id' => $res['tmdb_id'] ?? ($res['id'] ?? ($video->tmdb_id ?? null)),
                'tmdb_type' => $res['type'] ?? ($video->tmdb_type ?? null),
                'tmdb_poster_path' => $res['poster_path'] ?? ($video->tmdb_poster_path ?? null),
                'tmdb_genres' => $video->tmdb_genres ?? null,
            ];
        }

        return response()->json([
            'ok' => true,
            'results' => $results,
        ]);
    }

    /**
     * Queue TMDB sync for many videos.
     * POST /api/videos/tmdb-scan-all
     * Body: { category_id?: int }
     */
    public function tmdbScanAll(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer'],
        ]);

        $apiKey = (string) AppSetting::getValue('tmdb_api_key', (string) env('TMDB_API_KEY', ''));
        if (trim($apiKey) === '') {
            return response()->json([
                'ok' => false,
                'message' => 'TMDB key missing. Set it in TMDB Settings.',
            ], 422);
        }

        $categoryId = (int) ($data['category_id'] ?? 0);

        $q = Video::query();
        if ($categoryId > 0) {
            $q->where('video_category_id', $categoryId);
        }

        // Only queue those that likely need TMDB metadata.
        $q->where(function ($w) {
            $w->whereNull('tmdb_id')
              ->orWhereNull('tmdb_poster_path')
              ->orWhereNull('tmdb_genres')
              ->orWhereRaw("TRIM(title) = ''")
              ->orWhereRaw("title REGEXP '^[0-9]+$'");
        });

        $ids = $q->orderBy('id')->pluck('id')->map(fn ($v) => (int) $v)->all();
        if (empty($ids)) {
            return response()->json([
                'ok' => true,
                'queued' => 0,
                'jobs' => 0,
                'message' => 'Nothing to sync.',
            ]);
        }

        $chunks = array_chunk($ids, 10);
        foreach ($chunks as $chunk) {
            TmdbSyncVideosJob::dispatch($chunk);
        }

        return response()->json([
            'ok' => true,
            'queued' => count($ids),
            'jobs' => count($chunks),
        ]);
    }

    /**
     * Fetch TMDB details for a single video (auto-sync if needed).
     * GET /api/videos/{video}/tmdb-details
     */
    public function tmdbDetails(Video $video, TmdbSyncService $sync)
    {
        $apiKey = (string) AppSetting::getValue('tmdb_api_key', (string) env('TMDB_API_KEY', ''));
        if (trim($apiKey) === '') {
            return response()->json([
                'ok' => false,
                'message' => 'TMDB key missing. Set it in TMDB Settings.',
            ], 422);
        }

        $res = $sync->syncVideo($video, $apiKey);
        if (!(bool) ($res['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'video_id' => $video->id,
                'message' => $res['message'] ?? 'TMDB lookup failed',
            ], 404);
        }

        $posterPath = (string) ($res['poster_path'] ?? ($video->tmdb_poster_path ?? ''));
        $backdropPath = (string) ($res['backdrop_path'] ?? ($video->tmdb_backdrop_path ?? ''));

        $posterUrl = $posterPath !== '' ? ('https://image.tmdb.org/t/p/w342' . $posterPath) : null;
        $backdropUrl = $backdropPath !== '' ? ('https://image.tmdb.org/t/p/w780' . $backdropPath) : null;

        return response()->json([
            'ok' => true,
            'video_id' => $video->id,
            'tmdb_id' => $res['id'] ?? ($video->tmdb_id ?? null),
            'tmdb_type' => $res['type'] ?? ($video->tmdb_type ?? null),
            'details' => $res,
            'poster_url' => $posterUrl,
            'backdrop_url' => $backdropUrl,
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
