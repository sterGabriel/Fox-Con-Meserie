<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\TmdbSyncVideosJob;
use App\Models\AppSetting;
use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use App\Models\Video;
use App\Models\VideoCategory;
use App\Models\EncodeProfile;
use App\Services\EncodingService;
use App\Services\EncodingProfileBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class LiveChannelController extends Controller
{
    /**
     * JSON API - Returns KPI + table rows
     */
    public function apiIndex()
    {
        $serverId = request()->get('serverId', '1');
        $pageSize = request()->get('pageSize', 60);
        $search = request()->get('search', '');

        $redisStatus = $this->getRedisStatusLabel();

        // Get channels with pagination
        $query = LiveChannel::with(['playlistItems.video', 'encodeProfile'])
            ->orderBy('id', 'desc');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $channels = $query->paginate($pageSize);

        // Calculate KPIs
        $totalChannels = LiveChannel::count();
        $enabledChannels = LiveChannel::where('enabled', true)->count();
        $totalVideos = Video::count();

        // Get disk stats for ENTIRE server
        $totalBytes = 0;
        $freeBytes = 0;
        foreach (['/home', '/'] as $mount) {
            $totalBytes += disk_total_space($mount);
            $freeBytes += disk_free_space($mount);
        }

        $totalTB = round($totalBytes / (1024 ** 4), 2);
        $freeTB = round($freeBytes / (1024 ** 4), 2);

        $kpi = [
            'totalChannels' => $totalChannels,
            'activeChannels' => $enabledChannels,
            'passiveChannels' => $totalChannels - $enabledChannels,
            'totalVideo' => $totalVideos,
            'totalSpace' => $totalTB . ' TB',
            'freeSpace' => $freeTB . ' TB',
        ];

        // Transform rows for table
        $rows = $channels->map(function ($channel) {
            $videos = $channel->playlistItems->map(fn($pi) => $pi->video)->filter();
            $totalDuration = $videos->sum(fn($v) => $v->duration_seconds ?? 0);
            $totalSize = $videos->sum(fn($v) => $v->size_bytes ?? 0);
            $targetBitrateK = (int) (
                $channel->encodeProfile?->video_bitrate_k
                ?? $channel->video_bitrate
                ?? 0
            );

            $hours = intdiv($totalDuration, 3600);
            $minutes = intdiv($totalDuration % 3600, 60);
            $seconds = $totalDuration % 60;

            $pid = (int) ($channel->encoder_pid ?? 0);
            $isRunning = $pid > 0 && @is_dir('/proc/' . $pid);

            $speed = '—';
            if ($isRunning) {
                try {
                    $engine = new \App\Services\ChannelEngineService($channel);
                    $s = $engine->getLastFfmpegSpeed();
                    if (is_string($s) && $s !== '') {
                        $speed = $s;
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            $uptime = '—';
            if ($isRunning && !empty($channel->started_at)) {
                $startedAt = Carbon::parse($channel->started_at);
                $elapsed = max(0, $startedAt->diffInSeconds(Carbon::now()));
                $d = intdiv($elapsed, 86400);
                $h = intdiv($elapsed % 86400, 3600);
                $m = intdiv($elapsed % 3600, 60);
                $s = $elapsed % 60;
                $uptime = ($d > 0 ? ($d . 'd ') : '') . ($h > 0 ? ($h . 'h ') : '') . $m . 'm ' . $s . 's';
            }

            // Size formatting
            if ($totalSize < 1024 * 1024) {
                $sizeStr = round($totalSize / 1024) . 'K';
            } elseif ($totalSize < 1024 * 1024 * 1024) {
                $sizeStr = round($totalSize / (1024 * 1024)) . 'M';
            } else {
                $sizeStr = round($totalSize / (1024 * 1024 * 1024), 2) . 'G';
            }

            return [
                'id' => $channel->id,
                'name' => $channel->name,
                'transcodingA' => $videos->count(),
                'transcodingB' => $videos->where('format', 'mp4')->count(),
                'transcodingC' => $videos->where('format', 'mkv')->count(),
                'playing' => $videos->first()?->title ? substr($videos->first()->title, 0, 20) : '-',
                'bitrate' => $targetBitrateK . 'k',
                'speed' => $speed,
                'redis' => $redisStatus,
                'uptime' => $uptime,
                'statusOk' => $isRunning,
                'epg' => 'OPEN',
                'size' => $sizeStr,
                'totalTime' => $hours . 'h ' . $minutes . 'm ' . $seconds . 's',
                'playlistCount' => $videos->count(),
                'convertedCount' => $videos->count(),
                'errorCount' => 0,
                'isDisabled' => !$channel->enabled,
            ];
        })->toArray();

        return response()->json([
            'kpi' => $kpi,
            'rows' => $rows,
        ]);
    }

    public function index()
    {
        $perPage = request()->get('per_page', 60);
        $channels = LiveChannel::with(['playlistItems.video', 'encodeProfile'])
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $redisStatus = $this->getRedisStatusLabel();

        // Attach real-time speed factor for running channels (parsed from FFmpeg log tail).
        foreach ($channels as $channel) {
            $pid = (int) ($channel->encoder_pid ?? 0);
            $isRunning = $pid > 0 && @is_dir('/proc/' . $pid);
            $channel->runtime_speed = '—';
            if ($isRunning) {
                try {
                    $engine = new \App\Services\ChannelEngineService($channel);
                    $speed = $engine->getLastFfmpegSpeed();
                    if (is_string($speed) && $speed !== '') {
                        $channel->runtime_speed = $speed;
                    }
                } catch (\Throwable $e) {
                    // keep default
                }
            }

            $channel->runtime_redis = $redisStatus;
        }

        // Calculate metrics
        $totalChannels = LiveChannel::count();
        $enabledChannels = LiveChannel::where('enabled', true)->count();
        $totalVideos = Video::count();

        // Get disk stats for ENTIRE server (all mounts)
        $totalBytes = 0;
        $freeBytes = 0;
        
        // Sum all mount points
        foreach (['/home', '/'] as $mount) {
            $totalBytes += disk_total_space($mount);
            $freeBytes += disk_free_space($mount);
        }
        
        $diskStats = [
            'total_gb' => round($totalBytes / (1024 ** 4), 2),  // Convert to TB
            'free_gb' => round($freeBytes / (1024 ** 4), 2),    // Convert to TB
        ];

        return view('admin.vod_channels.index', [
            'channels' => $channels,
            'totalChannels' => $totalChannels,
            'enabledChannels' => $enabledChannels,
            'totalVideos' => $totalVideos,
            'diskStats' => $diskStats,
        ]);
    }

    private function getRedisStatusLabel(): string
    {
        try {
            // If Redis isn't configured/available, this will throw.
            $pong = Redis::connection()->ping();
            $pongStr = is_string($pong) ? strtoupper(trim($pong)) : '';
            if ($pong === true || $pongStr === 'PONG' || str_contains($pongStr, 'PONG')) {
                return 'OK';
            }
            return 'OK';
        } catch (\Throwable $e) {
            return 'DOWN';
        }
    }

    public function create()
    {
        return view('admin.vod_channels.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $data['slug']            = Str::slug($data['name']) . '-' . uniqid();
        $data['input_url']       = null;
        $data['logo_path']       = null;
        $data['encoder_profile'] = 'h264_1500k';
        $data['enabled']         = true;
        $data['status']          = 'idle';
        $data['video_category']  = null;
        // auth()->id() returns the auth identifier, which is configured to be 'name' (string)
        // in this project; created_by is numeric in DB, so store the numeric user id.
        $data['created_by']      = auth()->user()?->id;

        $data['resolution']      = '1280x720';
        $data['video_bitrate']   = 1500;
        $data['audio_bitrate']   = 128;
        $data['fps']             = 25;
        $data['audio_codec']     = 'aac';

        $data['overlay_title']   = true;
        $data['overlay_timer']   = true;

        $data['encoded_output_path'] = null;
        $data['hls_output_path']     = null;

        $channel = LiveChannel::create($data);

        return redirect()
            ->route('vod-channels.playlist', $channel)
            ->with('success', 'Vod channel created.');
    }

    public function destroy(Request $request, LiveChannel $channel)
    {
        try {
            // Defensive cleanup: playlist_items has both live_channel_id and vod_channel_id.
            // Remove any rows referencing this channel via either column.
            PlaylistItem::query()
                ->where('live_channel_id', $channel->id)
                ->orWhere('vod_channel_id', $channel->id)
                ->delete();

            $channel->delete();

            if ($request->expectsJson()) {
                return response()->json(['ok' => true]);
            }

            return redirect()
                ->route('vod-channels.index')
                ->with('success', 'Channel deleted.');
        } catch (\Throwable $e) {
            \Log::error('Delete channel failed: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Delete failed: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    public function playlist(LiveChannel $channel)
    {
        $allPlaylistItems = PlaylistItem::query()
            ->where(function ($q) use ($channel) {
                // Canonical FK is live_channel_id; keep a fallback for legacy data.
                $q->where('live_channel_id', $channel->id)
                  ->orWhere('vod_channel_id', $channel->id);
            })
            ->with('video')
            ->orderBy('sort_order')
            ->get();

        // Encoding outputs are stored as: storage/app/streams/{channel}/video_{playlist_item_id}.ts
        // IMPORTANT: sync-playlist-from-category used to recreate playlist items (new IDs).
        // To avoid "losing" already-encoded files, we also support a legacy/stable fallback:
        // storage/app/streams/{channel}/video_{video_id}.ts
        $outputDir = storage_path("app/streams/{$channel->id}");

        foreach ($allPlaylistItems as $item) {
            $primary = $outputDir . '/video_' . (int) $item->id . '.ts';
            $fallback = $outputDir . '/video_' . (int) ($item->video_id ?? 0) . '.ts';

            if (is_file($primary)) {
                $item->ts_path = $primary;
                $item->ts_file = basename($primary);
                $item->ts_exists = true;
            } elseif (($item->video_id ?? 0) && is_file($fallback)) {
                $item->ts_path = $fallback;
                $item->ts_file = basename($fallback);
                $item->ts_exists = true;
            } else {
                $item->ts_path = $primary;
                $item->ts_file = basename($primary);
                $item->ts_exists = false;
            }
        }

        $videoIds = $allPlaylistItems->pluck('video_id')->filter()->unique()->values();
        $streamsDir = storage_path("app/streams/{$channel->id}");
        $streamsRelLike = 'streams/' . $channel->id . '/video_%';

        // IMPORTANT: Expose only FULL TS encoding jobs on the playlist page.
        // Test preview jobs output to storage/app/public/previews/{channel}/test_video_{id}.mp4
        // and would incorrectly show 0% (30s vs full video duration).
        $jobs = \App\Models\EncodingJob::query()
            ->where(function ($q) use ($channel) {
                $q->where('live_channel_id', $channel->id)
                  ->orWhere('channel_id', $channel->id);
            })
            ->whereIn('video_id', $videoIds)
            ->where(function ($q) use ($streamsDir, $streamsRelLike) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('playlist_item_id')
                       ->where('playlist_item_id', '>', 0);
                })
                ->orWhere(function ($q2) use ($streamsDir, $streamsRelLike) {
                    $q2->whereNotNull('output_path')
                       ->where(function ($q3) use ($streamsDir, $streamsRelLike) {
                           $q3->where('output_path', 'like', $streamsDir . '/video_%')
                              ->orWhere('output_path', 'like', $streamsRelLike);
                       });
                });
            })
            ->orderByDesc('created_at')
            ->get(['id', 'video_id', 'playlist_item_id', 'status', 'progress', 'created_at', 'started_at', 'finished_at', 'output_path', 'error_message', 'settings']);

        // Enrich jobs with computed progress from ffmpeg -progress file.
        $parseProgress = function (int $jobId): array {
            $path = storage_path('app/encoding_progress/job_' . $jobId . '.txt');
            $out = ['out_time_ms' => null, 'speed' => null, 'progress' => null];
            if (!is_file($path)) return $out;
            $data = @file_get_contents($path);
            if (!is_string($data) || $data === '') return $out;

            if (preg_match_all('/^out_time_ms=(\d+)$/m', $data, $m) && !empty($m[1])) {
                $out['out_time_ms'] = (int) end($m[1]);
            }
            if (preg_match_all('/^speed=([^\r\n]+)$/m', $data, $m) && !empty($m[1])) {
                $out['speed'] = (string) end($m[1]);
            }
            if (preg_match_all('/^progress=([^\r\n]+)$/m', $data, $m) && !empty($m[1])) {
                $out['progress'] = (string) end($m[1]);
            }
            return $out;
        };

        $videoById = $allPlaylistItems->pluck('video', 'video_id');
        foreach ($jobs as $job) {
            $meta = $parseProgress((int) $job->id);
            $settings = $job->settings;
            if (!is_array($settings)) $settings = [];

            $durationSeconds = (int) ($settings['test_duration_seconds'] ?? 0);
            if ($durationSeconds <= 0) {
                $durationSeconds = (int) ($videoById->get((int) $job->video_id)?->duration_seconds ?? 0);
            }
            $pct = (int) ($job->progress ?? 0);
            if (is_int($meta['out_time_ms']) && $durationSeconds > 0) {
                $pct = (int) floor(min(99, max(0, ($meta['out_time_ms'] / ($durationSeconds * 1000000)) * 100)));
            }
            if ($job->status === 'done') {
                $pct = 100;
            }
            $job->display_progress = $pct;
            $job->display_speed = $meta['speed'];

            $job->display_eta = null;
            if ($job->status === 'running' && $durationSeconds > 0 && is_int($meta['out_time_ms'])) {
                $outSeconds = (int) floor(max(0, $meta['out_time_ms']) / 1000000);
                $remain = max(0, $durationSeconds - $outSeconds);
                $job->display_eta = gmdate('H:i:s', (int) $remain);
            }

            if (is_int($meta['out_time_ms'])) {
                $seconds = (int) floor(max(0, $meta['out_time_ms']) / 1000000);
                $job->display_out_time = gmdate('H:i:s', $seconds);
            } else {
                $job->display_out_time = null;
            }
        }

        // Queue position for queued jobs (matches sequential runner ordering: sort_order asc, created_at asc).
        $sortOrderByPlaylistItemId = $allPlaylistItems->pluck('sort_order', 'id');
        $queuedJobs = $jobs
            ->filter(fn ($j) => strtolower((string) ($j->status ?? '')) === 'queued')
            ->sort(function ($a, $b) use ($sortOrderByPlaylistItemId) {
                $sa = (int) ($sortOrderByPlaylistItemId->get((int) ($a->playlist_item_id ?? 0), PHP_INT_MAX));
                $sb = (int) ($sortOrderByPlaylistItemId->get((int) ($b->playlist_item_id ?? 0), PHP_INT_MAX));
                if ($sa !== $sb) return $sa <=> $sb;
                return ((string) ($a->created_at ?? '')) <=> ((string) ($b->created_at ?? ''));
            })
            ->values();

        foreach ($queuedJobs as $i => $job) {
            $job->display_queue_position = $i + 1;
        }

        // Prefer mapping by playlist_item_id (stable for TS outputs), fallback by video_id.
        $jobByPlaylistItemId = $jobs
            ->filter(fn ($j) => (int) ($j->playlist_item_id ?? 0) > 0)
            ->groupBy('playlist_item_id')
            ->map(fn ($g) => $g->first());

        $jobByVideoId = $jobs->groupBy('video_id')->map(fn ($g) => $g->first());

        // TS READY means: TS exists AND there is no RUNNING job for it.
        // NOTE: a TS file may already exist from a previous encode even if a new job is queued
        // (e.g. user clicked Start Encoding again). In that case we still show it as encoded.
        foreach ($allPlaylistItems as $item) {
            $job = null;
            if ((int) ($item->id ?? 0) > 0) {
                $job = $jobByPlaylistItemId->get((int) $item->id);
            }
            if (!$job && (int) ($item->video_id ?? 0) > 0) {
                $job = $jobByVideoId->get((int) $item->video_id);
            }

            $status = $job ? strtolower((string) ($job->status ?? '')) : '';
            $item->ts_ready = (bool) ($item->ts_exists ?? false) && ($status === '' || $status === 'done' || $status === 'queued');
        }

        // Playlist page shows encoded items, but we also expose the non-encoded queue for visibility.
        $encodedItems = $allPlaylistItems->filter(fn ($item) => (bool) ($item->ts_ready ?? false))->values();
        $pendingItems = $allPlaylistItems->filter(fn ($item) => !(bool) ($item->ts_ready ?? false))->values();

        $jobCounts = [
            'running' => (int) $jobs->where('status', 'running')->count(),
            'queued'  => (int) $jobs->where('status', 'queued')->count(),
            'failed'  => (int) $jobs->where('status', 'failed')->count(),
            'done'    => (int) $jobs->where('status', 'done')->count(),
            'total'   => (int) $jobs->count(),
        ];

        // Auto TMDB sync (titles/posters/genres) for items shown on this page.
        // Runs in background queue; safe to call repeatedly.
        try {
            $apiKey = (string) AppSetting::getValue('tmdb_api_key', (string) env('TMDB_API_KEY', ''));
            if (trim($apiKey) !== '') {
                $needs = [];
                foreach ($allPlaylistItems as $item) {
                    $video = $item->video;
                    if (!$video) continue;

                    $title = trim((string) ($video->title ?? ''));
                    $numericTitle = ($title !== '' && preg_match('/^\d+$/', $title) === 1);
                    $missingPoster = empty($video->tmdb_poster_path);
                    $missingGenres = empty($video->tmdb_genres);
                    $missingId = empty($video->tmdb_id);

                    if ($missingId || $missingPoster || $missingGenres || $title === '' || $numericTitle) {
                        $needs[(int) $video->id] = true;
                    }
                }

                $ids = array_keys($needs);
                if (!empty($ids)) {
                    foreach (array_chunk($ids, 10) as $chunk) {
                        TmdbSyncVideosJob::dispatch($chunk);
                    }
                }
            }
        } catch (\Throwable $e) {
            // never break the page
        }

        return view('admin.vod_channels.playlist', [
            'channel'       => $channel,
            'playlistItems' => $encodedItems,
            'pendingItems'  => $pendingItems,
            'jobByVideoId'  => $jobByVideoId,
            'jobByPlaylistItemId' => $jobByPlaylistItemId,
            'jobCounts'     => $jobCounts,
        ]);
    }

    public function encodingNow(LiveChannel $channel)
    {
        return view('admin.vod_channels.encoding_now', [
            'channel' => $channel,
        ]);
    }

    public function playlistPlayer(LiveChannel $channel, PlaylistItem $item)
    {
        // Safety: ensure the playlist item belongs to this channel.
        $belongs = ((int) ($item->live_channel_id ?? 0) === (int) $channel->id)
            || ((int) ($item->vod_channel_id ?? 0) === (int) $channel->id);

        if (!$belongs) {
            abort(404);
        }

        $item->loadMissing('video');

        $primary = storage_path("app/streams/{$channel->id}/video_" . (int) $item->id . '.ts');
        $fallback = storage_path("app/streams/{$channel->id}/video_" . (int) ($item->video_id ?? 0) . '.ts');

        $tsFile = null;
        if (is_file($primary)) {
            $tsFile = basename($primary);
        } elseif (($item->video_id ?? 0) && is_file($fallback)) {
            $tsFile = basename($fallback);
        }

        if ($tsFile === null) {
            return response()->view('admin.vod_channels.playlist_player', [
                'title' => optional($item->video)->title ?? 'Encoded video',
                'tsUrl' => null,
                'error' => 'Encoded TS file not found for this item.',
            ], 404);
        }

        return view('admin.vod_channels.playlist_player', [
            'title' => optional($item->video)->title ?? 'Encoded video',
            'tsUrl' => url("/streams/{$channel->id}/{$tsFile}"),
            'error' => null,
        ]);
    }

    public function addToPlaylist(Request $request, LiveChannel $channel)
    {
        $data = $request->validate([
            'video_id' => ['required', 'exists:videos,id'],
        ]);

        $exists = PlaylistItem::query()
            ->where(function ($q) use ($channel) {
                $q->where('live_channel_id', $channel->id)
                  ->orWhere('vod_channel_id', $channel->id);
            })
            ->where('video_id', $data['video_id'])
            ->exists();

        if ($exists) {
            return redirect()
                ->route('vod-channels.playlist', $channel)
                ->with('error', 'Video already in playlist for this channel.');
        }

        $maxOrder = PlaylistItem::query()
            ->where(function ($q) use ($channel) {
                $q->where('live_channel_id', $channel->id)
                  ->orWhere('vod_channel_id', $channel->id);
            })
            ->max('sort_order');

        PlaylistItem::create([
            // Keep both columns in sync for compatibility.
            'live_channel_id' => $channel->id,
            'vod_channel_id'  => $channel->id,
            'video_id'       => $data['video_id'],
            'sort_order'     => $maxOrder ? $maxOrder + 1 : 1,
        ]);

        return redirect()
            ->route('vod-channels.playlist', $channel)
            ->with('success', 'Video added to playlist.');
    }

    public function addToPlaylistBulk(Request $request, LiveChannel $channel)
    {
        $ids = array_filter(explode(',', (string)$request->input('video_ids')));

        if (empty($ids)) {
            return back()->with('error', 'No videos selected');
        }

        $maxOrder = (int)DB::table('playlist_items')
            ->where(function ($q) use ($channel) {
                $q->where('live_channel_id', $channel->id)
                  ->orWhere('vod_channel_id', $channel->id);
            })
            ->max('sort_order');

        foreach ($ids as $i => $videoId) {
            // Avoid duplicates
            $exists = DB::table('playlist_items')
                ->where(function ($q) use ($channel) {
                    $q->where('live_channel_id', $channel->id)
                      ->orWhere('vod_channel_id', $channel->id);
                })
                ->where('video_id', (int)$videoId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('playlist_items')->insert([
                'live_channel_id' => $channel->id,
                'vod_channel_id'  => $channel->id,
                'video_id'       => (int)$videoId,
                'sort_order'     => $maxOrder + 1 + $i,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return back()->with('success', 'Added selected videos to playlist.');
    }

    public function removeFromPlaylist(LiveChannel $channel, PlaylistItem $item)
    {
        if ((int)$item->live_channel_id !== (int)$channel->id && (int)$item->vod_channel_id !== (int)$channel->id) {
            abort(404);
        }

        $item->delete();

        return redirect()
            ->route('vod-channels.playlist', $channel)
            ->with('success', 'Item removed from playlist.');
    }

    public function moveUp(LiveChannel $channel, PlaylistItem $item)
    {
        if ((int)$item->live_channel_id !== (int)$channel->id && (int)$item->vod_channel_id !== (int)$channel->id) {
            abort(404);
        }

        $previous = PlaylistItem::query()
            ->where(function ($q) use ($channel) {
                $q->where('live_channel_id', $channel->id)
                  ->orWhere('vod_channel_id', $channel->id);
            })
            ->where('sort_order', '<', $item->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($previous) {
            [$item->sort_order, $previous->sort_order] = [$previous->sort_order, $item->sort_order];
            $item->save();
            $previous->save();
        }

        return redirect()->route('vod-channels.playlist', $channel);
    }

    public function moveDown(LiveChannel $channel, PlaylistItem $item)
    {
        if ((int)$item->live_channel_id !== (int)$channel->id && (int)$item->vod_channel_id !== (int)$channel->id) {
            abort(404);
        }

        $next = PlaylistItem::query()
            ->where(function ($q) use ($channel) {
                $q->where('live_channel_id', $channel->id)
                  ->orWhere('vod_channel_id', $channel->id);
            })
            ->where('sort_order', '>', $item->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($next) {
            [$item->sort_order, $next->sort_order] = [$next->sort_order, $item->sort_order];
            $item->save();
            $next->save();
        }

        return redirect()->route('vod-channels.playlist', $channel);
    }

    public function reorderPlaylist(Request $request, LiveChannel $channel)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['ok' => false], 422);
        }

        DB::transaction(function () use ($ids, $channel) {
            foreach ($ids as $index => $id) {
                DB::table('playlist_items')
                    ->where('id', $id)
                    ->where(function ($q) use ($channel) {
                        $q->where('live_channel_id', $channel->id)
                          ->orWhere('vod_channel_id', $channel->id);
                    })
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function logoPreview(LiveChannel $channel)
    {
        $path = (string) ($channel->logo_path ?? '');

        if ($path === '') {
            abort(404);
        }

        // Legacy absolute path support (backward compatibility)
        if (str_starts_with($path, '/')) {
            if (!is_file($path)) {
                abort(404);
            }
            return response()->file($path);
        }

        // Normalize relative path
        $path = ltrim($path, '/');

        // Basic path traversal protection
        if (str_contains($path, '..')) {
            abort(404);
        }

        // Enforce storage scope (supports legacy + current)
        if (!(str_starts_with($path, 'private/logos/') || str_starts_with($path, 'logos/') || str_starts_with($path, 'channel-logos/'))) {
            abort(404);
        }

        $candidates = [];

        // Legacy: storage/app/<path>
        $candidates[] = storage_path('app/' . $path);

        // Current: local disk root is storage/app/private
        $localRoot = (string) (config('filesystems.disks.local.root') ?? '');
        if ($localRoot !== '') {
            $candidates[] = rtrim($localRoot, '/') . '/' . $path;

            // If DB contains "private/..." but disk root already includes ".../private",
            // also try removing the leading "private/".
            if (str_starts_with($path, 'private/')) {
                $candidates[] = rtrim($localRoot, '/') . '/' . substr($path, strlen('private/'));
            }
        }

        foreach ($candidates as $abs) {
            if (is_file($abs)) {
                return response()->file($abs, [
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                ]);
            }
        }

        abort(404);
    }

    public function settings(LiveChannel $channel)
    {
        $categories = VideoCategory::orderBy('name')->get();

        return view('admin.vod_channels.edit', [
            'channel' => $channel,
            'categories' => $categories,
        ]);
    }

    /**
     * Dedicated page for import/encoding controls (Fox-style).
     */
    public function encoding(LiveChannel $channel)
    {
        // Legacy URL kept for compatibility. The single encoding page is /create-video/{channel}.
        return redirect()->route('create-video.show', ['channel' => $channel->id]);
    }

    public function updateSettings(Request $request, LiveChannel $channel)
    {
        $data = $request->validate([
            // Core channel fields (Edit page)
            'name'                          => ['sometimes', 'string', 'max:255'],
            'enabled'                       => ['sometimes', 'boolean'],
            'encoder_profile'               => ['sometimes', 'string', 'max:255'],
            'resolution'                    => ['sometimes', 'string', 'max:20'],

            // Category (prefer *_id)
            'video_category_id'             => ['nullable', 'integer', 'exists:video_categories,id'],
            'video_category'                => ['nullable', 'integer', 'exists:video_categories,id'],
            'is_24_7_channel'               => ['nullable', 'boolean'],
            'auto_sync_playlist'            => ['nullable', 'boolean'],
            'description'                   => ['nullable', 'string', 'max:500'],
            
            'encode_profile_id'             => ['nullable', 'integer', 'exists:encode_profiles,id'],
            'manual_override_encoding'      => ['nullable', 'boolean'],
            'manual_width'                  => ['nullable', 'integer'],
            'manual_height'                 => ['nullable', 'integer'],
            'manual_fps'                    => ['nullable', 'integer'],
            'manual_codec'                  => ['nullable', 'string'],
            'manual_preset'                 => ['nullable', 'string'],
            'manual_bitrate'                => ['nullable', 'integer'],
            'manual_audio_bitrate'          => ['nullable', 'integer'],
            'manual_audio_codec'            => ['nullable', 'string'],

            'overlay_logo_enabled'          => ['nullable', 'boolean'],
            'overlay_logo_file'             => ['nullable', 'file', 'mimes:png,svg'],
            'overlay_logo_position'         => ['nullable', 'string', 'in:TL,TR,BL,BR,CUSTOM'],
            'overlay_logo_x'                => ['nullable', 'integer'],
            'overlay_logo_y'                => ['nullable', 'integer'],
            'overlay_logo_width'            => ['nullable', 'integer'],
            'overlay_logo_height'           => ['nullable', 'integer'],
            'overlay_logo_opacity'          => ['nullable', 'numeric', 'min:0', 'max:100'],

            'overlay_text_enabled'          => ['nullable', 'boolean'],
            'overlay_text_content'          => ['nullable', 'string', 'in:channel_name,title,custom'],
            'overlay_text_custom'           => ['nullable', 'string', 'max:255'],
            'overlay_text_font_family'      => ['nullable', 'string', 'in:Arial,DejaVuSans,Helvetica,Courier,Times,Ubuntu'],
            'overlay_text_font_size'        => ['nullable', 'integer'],
            'overlay_text_color'            => ['nullable', 'string'],
            'overlay_text_padding'          => ['nullable', 'integer'],
            'overlay_text_position'         => ['nullable', 'string', 'in:TL,TR,BL,BR,CUSTOM'],
            'overlay_text_x'                => ['nullable', 'integer'],
            'overlay_text_y'                => ['nullable', 'integer'],
            'overlay_text_opacity'          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'overlay_text_bg_opacity'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'overlay_text_bg_color'         => ['nullable', 'string'],

            'overlay_timer_enabled'         => ['nullable', 'boolean'],
            'overlay_timer_mode'            => ['nullable', 'string', 'in:realtime,elapsed,countdown'],
            'overlay_timer_format'          => ['nullable', 'string', 'in:HH:mm,HH:mm:ss,HH:mm:ss.mmm'],
            'overlay_timer_position'        => ['nullable', 'string', 'in:TL,TR,BL,BR,CUSTOM'],
            'overlay_timer_x'               => ['nullable', 'integer'],
            'overlay_timer_y'               => ['nullable', 'integer'],
            'overlay_timer_font_size'       => ['nullable', 'integer'],
            'overlay_timer_color'           => ['nullable', 'string'],
            'overlay_timer_style'           => ['nullable', 'string', 'in:normal,bold,shadow'],
            'overlay_timer_bg'              => ['nullable', 'string', 'in:none,dark,colored'],
            'overlay_timer_opacity'         => ['nullable', 'numeric', 'min:0', 'max:100'],

            'overlay_safe_margin'           => ['nullable', 'integer', 'min:0', 'max:50'],

            // Channel list logo (16:9)
            'channel_logo_file'             => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg'],
        ]);

        $videoCategoryId = $data['video_category_id'] ?? $data['video_category'] ?? null;

        // Single logo per channel:
        // - main UI uses channel_logo_file
        // - backward compatibility: overlay_logo_file is treated as the same channel logo
        $logoFile = null;
        if ($request->hasFile('channel_logo_file')) {
            $logoFile = $request->file('channel_logo_file');
        } elseif ($request->hasFile('overlay_logo_file')) {
            $logoFile = $request->file('overlay_logo_file');
        }

        if ($logoFile) {
            // NOTE: local disk root is storage/app/private, so do NOT prefix with "private/".
            $dir = 'logos/channels/' . $channel->id;
            $name = 'channel_logo_' . date('Ymd_His') . '.' . $logoFile->getClientOriginalExtension();
            $relative = \Illuminate\Support\Facades\Storage::disk('local')->putFileAs($dir, $logoFile, $name);

            // single source of truth
            $data['logo_path'] = $relative;
            $data['overlay_logo_path'] = $relative;
        } elseif (!empty($channel->logo_path) && empty($channel->overlay_logo_path)) {
            // keep overlay in sync if it was never set
            $data['overlay_logo_path'] = $channel->logo_path;
        }

        $manualEnabled = $request->boolean('manual_override_encoding');
        $manualConfig = $manualEnabled ? [
            'width' => $data['manual_width'] ?? null,
            'height' => $data['manual_height'] ?? null,
            'fps' => $data['manual_fps'] ?? null,
            'codec' => $data['manual_codec'] ?? null,
            'preset' => $data['manual_preset'] ?? null,
            'bitrate' => $data['manual_bitrate'] ?? null,
            'audio_bitrate' => $data['manual_audio_bitrate'] ?? null,
            'audio_codec' => $data['manual_audio_codec'] ?? null,
        ] : null;

        $channel->update([
            // Core
            'name'                          => $data['name'] ?? $channel->name,
            'enabled'                       => array_key_exists('enabled', $data) ? (bool)$data['enabled'] : $channel->enabled,
            'encoder_profile'               => $data['encoder_profile'] ?? $channel->encoder_profile,
            'resolution'                    => $data['resolution'] ?? $channel->resolution,

            // Category & sync
            'video_category_id'             => $videoCategoryId,
            'auto_sync_playlist'            => $request->boolean('auto_sync_playlist'),
            'is_24_7_channel'               => $request->boolean('is_24_7_channel'),
            'description'                   => $data['description'] ?? null,
            
            'encode_profile_id'             => $data['encode_profile_id'] ?? null,
            'manual_override_encoding'      => $manualEnabled,
            'manual_width'                  => $data['manual_width'] ?? null,
            'manual_height'                 => $data['manual_height'] ?? null,
            'manual_fps'                    => $data['manual_fps'] ?? null,
            'manual_codec'                  => $data['manual_codec'] ?? null,
            'manual_preset'                 => $data['manual_preset'] ?? null,
            'manual_bitrate'                => $data['manual_bitrate'] ?? null,
            'manual_audio_bitrate'          => $data['manual_audio_bitrate'] ?? null,
            'manual_audio_codec'            => $data['manual_audio_codec'] ?? null,

            // Backward/compat fields used by EncodingProfileBuilder
            'manual_encode_enabled'          => $manualEnabled,
            'manual_encode_config'           => $manualConfig,

            'overlay_logo_enabled'          => $request->boolean('overlay_logo_enabled'),
            'overlay_logo_path'             => $data['overlay_logo_path'] ?? $channel->overlay_logo_path,
            'overlay_logo_position'         => $data['overlay_logo_position'] ?? 'TL',
            'overlay_logo_x'                => $data['overlay_logo_x'] ?? 20,
            'overlay_logo_y'                => $data['overlay_logo_y'] ?? 20,
            'overlay_logo_width'            => $data['overlay_logo_width'] ?? 150,
            'overlay_logo_height'           => $data['overlay_logo_height'] ?? 100,
            'overlay_logo_opacity'          => $data['overlay_logo_opacity'] ?? 80,

            'overlay_text_enabled'          => $request->boolean('overlay_text_enabled'),
            'overlay_text_content'          => $data['overlay_text_content'] ?? 'channel_name',
            'overlay_text_custom'           => $data['overlay_text_custom'] ?? null,
            'overlay_text_font_family'      => $data['overlay_text_font_family'] ?? 'Arial',
            'overlay_text_font_size'        => $data['overlay_text_font_size'] ?? 28,
            'overlay_text_color'            => $data['overlay_text_color'] ?? '#FFFFFF',
            'overlay_text_padding'          => $data['overlay_text_padding'] ?? 6,
            'overlay_text_position'         => $data['overlay_text_position'] ?? null,
            'overlay_text_x'                => $data['overlay_text_x'] ?? 20,
            'overlay_text_y'                => $data['overlay_text_y'] ?? 20,
            'overlay_text_opacity'          => $data['overlay_text_opacity'] ?? 100,
            'overlay_text_bg_opacity'       => $data['overlay_text_bg_opacity'] ?? 60,
            'overlay_text_bg_color'         => $data['overlay_text_bg_color'] ?? '#000000',

            'overlay_timer_enabled'         => $request->boolean('overlay_timer_enabled'),
            'overlay_timer_mode'            => $data['overlay_timer_mode'] ?? 'realtime',
            'overlay_timer_format'          => $data['overlay_timer_format'] ?? 'HH:mm',
            'overlay_timer_position'        => $data['overlay_timer_position'] ?? 'TR',
            'overlay_timer_x'               => $data['overlay_timer_x'] ?? 20,
            'overlay_timer_y'               => $data['overlay_timer_y'] ?? 20,
            'overlay_timer_font_size'       => $data['overlay_timer_font_size'] ?? 24,
            'overlay_timer_color'           => $data['overlay_timer_color'] ?? '#FFFFFF',
            'overlay_timer_style'           => $data['overlay_timer_style'] ?? 'normal',
            'overlay_timer_bg'              => $data['overlay_timer_bg'] ?? 'none',
            'overlay_timer_opacity'         => $data['overlay_timer_opacity'] ?? 100,

            'overlay_safe_margin'           => $data['overlay_safe_margin'] ?? 30,

            // Channel logo (16:9 list)
            'logo_path'                      => $data['logo_path'] ?? $channel->logo_path,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Settings saved successfully!');
    }

    public function previewFFmpeg(Request $request, LiveChannel $channel)
    {
        $profileId = $request->input('profile_id');
        $manualEnabled = $request->boolean('manual_enabled');
        
        try {
            $profile = null;
            
            if ($profileId) {
                $profile = EncodeProfile::find($profileId);
                if (!$profile) {
                    return response()->json(['error' => 'Profile not found'], 404);
                }
            } else {
                // Use default LIVE profile (720p)
                $profile = EncodeProfile::where('mode', 'live')
                    ->where('height', 720)
                    ->first();
                
                if (!$profile) {
                    return response()->json(['error' => 'No default profile found'], 404);
                }
            }

            // Build the ffmpeg command
            $builder = new EncodingProfileBuilder();
            
            // Mock input/output for preview
            $inputUrl = 'input.mp4';
            $outputUrl = 'rtmp://localhost/live/' . $channel->slug;
            
            $command = $builder->buildCommand($profile, $inputUrl, $outputUrl);

            return response()->json([
                'command' => $command,
                'profile_name' => $profile->name,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function startChannel(Request $request, LiveChannel $channel)
    {
        try {
            $engine = new \App\Services\ChannelEngineService($channel);

            // Strong duplicate protection: detect ffmpeg already writing into this channel output dir.
            $detectedPid = $engine->detectRunningFfmpegPid();
            if ($detectedPid) {
                $channel->update(['encoder_pid' => $detectedPid, 'status' => 'live']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Channel is already running (detected ffmpeg PID ' . $detectedPid . ')'
                ], 400);
            }

            // Check if already running
            if ($engine->isRunning($channel->encoder_pid)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Channel is already running'
                ], 400);
            }

            // Prefer looping from playlist TS-ready items (exactly what was encoded).
            $playlistItems = PlaylistItem::query()
                ->where(function ($q) use ($channel) {
                    $q->where('live_channel_id', $channel->id)
                      ->orWhere('vod_channel_id', $channel->id);
                })
                ->orderBy('sort_order')
                ->get(['id', 'video_id']);

            $outputDir = storage_path("app/streams/{$channel->id}");
            $encodedPaths = [];
            foreach ($playlistItems as $item) {
                $primary = $outputDir . '/video_' . (int) $item->id . '.ts';
                $fallback = $outputDir . '/video_' . (int) ($item->video_id ?? 0) . '.ts';
                if (is_file($primary)) {
                    $encodedPaths[] = $primary;
                } elseif (($item->video_id ?? 0) && is_file($fallback)) {
                    $encodedPaths[] = $fallback;
                }
            }

            if (!empty($encodedPaths)) {
                $ffmpegCommand = $engine->generatePlayCommandFromFiles($encodedPaths, loop: true);
                $mode = 'PLAY LOOP (playlist encoded: ' . count($encodedPaths) . ' files)';
            } else {
                $ffmpegCommand = $engine->generateLoopingCommand(includeOverlay: true);
                $mode = 'ENCODE LOOP (concat playlist)';
            }

            // Start the channel
            $result = $engine->start($ffmpegCommand);
            
            if ($result['status'] === 'success') {
                $result['mode'] = $mode;
                $result['encoded_count'] = isset($encodedPaths) ? count($encodedPaths) : 0;
            }

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            if (($result['status'] ?? '') === 'success') {
                return back()->with('success', ($result['message'] ?? 'Channel started') . (isset($result['pid']) ? ' (PID ' . $result['pid'] . ')' : ''));
            }

            return back()->with('error', $result['message'] ?? 'Failed to start channel');

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function stopChannel(Request $request, LiveChannel $channel)
    {
        try {
            $engine = new \App\Services\ChannelEngineService($channel);

            $result = $engine->stop();

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            if (($result['status'] ?? '') === 'success') {
                return back()->with('success', $result['message'] ?? 'Channel stopped');
            }

            return back()->with('error', $result['message'] ?? 'Failed to stop channel');

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function channelStatus(Request $request, LiveChannel $channel)
    {
        try {
            $engine = new \App\Services\ChannelEngineService($channel);
            $status = $engine->getStatus();
            $logs = $engine->getLogTail(50);

            return response()->json([
                'status' => $status,
                'logs' => $logs,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function testPreview(Request $request, LiveChannel $channel)
    {
        try {
            $normalizeFfmpegColor = function (?string $color): string {
                $c = trim((string) ($color ?? ''));
                if ($c === '') return 'white';
                if (preg_match('/^#?([0-9a-fA-F]{6})$/', $c, $m)) {
                    return '0x' . strtoupper($m[1]);
                }
                return $c;
            };

            $settings = $request->input('settings', []);
            if (is_string($settings)) {
                $decoded = json_decode($settings, true);
                $settings = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($settings)) {
                $settings = [];
            }

            $hasKey = function (string $key) use ($request, $settings): bool {
                return array_key_exists($key, $settings) || $request->has($key);
            };

            $getKey = function (string $key, $default = null) use ($request, $settings) {
                if (array_key_exists($key, $settings)) return $settings[$key];
                return $request->input($key, $default);
            };

            // Apply per-request overrides (without saving to DB)
            $effective = clone $channel;
            $overrideKeys = [
                'resolution',
                'manual_override_encoding',
                'manual_encode_enabled',
                'manual_width',
                'manual_height',
                'manual_bitrate',
                'manual_codec',
                'manual_preset',
                'manual_audio_codec',
                'manual_audio_bitrate',
                'overlay_logo_enabled',
                'overlay_logo_position',
                'overlay_logo_x',
                'overlay_logo_y',
                'overlay_logo_width',
                'overlay_logo_height',
                'overlay_logo_opacity',
                'overlay_text_enabled',
                'overlay_text_content',
                'overlay_text_custom',
                'overlay_text_font_family',
                'overlay_text_font_size',
                'overlay_text_color',
                'overlay_text_padding',
                'overlay_text_position',
                'overlay_text_x',
                'overlay_text_y',
                'overlay_text_opacity',
                'overlay_text_bg_color',
                'overlay_text_bg_opacity',
                'overlay_timer_enabled',
                'overlay_timer_mode',
                'overlay_timer_format',
                'overlay_timer_position',
                'overlay_timer_x',
                'overlay_timer_y',
                'overlay_timer_font_size',
                'overlay_timer_color',
                'overlay_timer_style',
                'overlay_timer_bg',
                'overlay_timer_opacity',
                'overlay_safe_margin',
                // Optional override (usually taken from the uploaded channel logo)
                'overlay_logo_path',
            ];

            foreach ($overrideKeys as $k) {
                if ($hasKey($k)) {
                    $effective->setAttribute($k, $getKey($k));
                }
            }

            // Get video from request or first from playlist
            $videoId = $request->input('video_id');
            $video = $videoId ? Video::find($videoId) : $channel->playlistItems()->orderBy('sort_order')->first()?->video;

            if (!$video) {
                return response()->json(['status' => 'error', 'message' => 'No video found'], 400);
            }

            $inputFile = $video->file_path;
            if (!file_exists($inputFile)) {
                return response()->json(['status' => 'error', 'message' => 'Video file not found'], 400);
            }

            // Create preview output
            $previewDir = storage_path("app/public/previews/{$channel->id}");
            @mkdir($previewDir, 0755, true);
            $outputFile = "{$previewDir}/preview_" . time() . ".mp4";

            $seconds = (int) $request->input('seconds', 10);
            if ($seconds < 10) $seconds = 10;
            if ($seconds > 60) $seconds = 60;

            $escapeForDrawtext = function (string $text): string {
                $text = str_replace('\\', '\\\\', $text);
                $text = str_replace("'", "\\'", $text);
                $text = str_replace(':', '\\:', $text);
                $text = str_replace(["\n", "\r"], ' ', $text);
                return $text;
            };

            $escapeForMoviePath = function (string $path): string {
                return str_replace("'", "\\'", $path);
            };

            $parseRes = function (string $res): array {
                $res = trim(strtolower($res));
                if ($res === '') return [0, 0];
                if (!preg_match('/^(\d{2,5})\s*x\s*(\d{2,5})$/', $res, $m)) return [0, 0];
                return [(int) $m[1], (int) $m[2]];
            };

            [$outW, $outH] = $parseRes((string) ($effective->resolution ?? ''));
            if (!$outW || !$outH) {
                $outW = 1920;
                $outH = 1080;
            }

            if ($effective->manual_override_encoding || $effective->manual_encode_enabled) {
                $mw = (int) ($effective->manual_width ?? 0);
                $mh = (int) ($effective->manual_height ?? 0);
                if ($mw > 0 && $mh > 0) {
                    $outW = $mw;
                    $outH = $mh;
                }
            }

            $filter = '';
            $overlayEnabled = (bool) ($effective->overlay_logo_enabled || $effective->overlay_text_enabled || $effective->overlay_timer_enabled);
            if ($overlayEnabled) {
                $filters = [];
                $filters[] = "[0:v]scale={$outW}:{$outH}:force_original_aspect_ratio=decrease:force_divisible_by=2[scaled]";
                $filters[] = "[scaled]pad={$outW}:{$outH}:(ow-iw)/2:(oh-ih)/2[padded]";
                $lastLabel = '[padded]';

                // Logo
                if ($effective->overlay_logo_enabled && !empty($effective->overlay_logo_path)) {
                    $logoAbs = null;
                    $logoRel = (string) $effective->overlay_logo_path;

                    if (str_starts_with($logoRel, '/')) {
                        $logoAbs = $logoRel;
                    } else {
                        try {
                            $logoAbs = \Illuminate\Support\Facades\Storage::disk('local')->path($logoRel);
                        } catch (\Throwable $e) {
                            $logoAbs = null;
                        }

                        if (!$logoAbs || !file_exists($logoAbs)) {
                            $try1 = storage_path('app/' . ltrim($logoRel, '/'));
                            $try2 = storage_path('app/private/' . ltrim($logoRel, '/'));
                            if (file_exists($try1)) $logoAbs = $try1;
                            elseif (file_exists($try2)) $logoAbs = $try2;
                        }
                    }

                    if ($logoAbs && file_exists($logoAbs)) {
                        $logoW = (int) ($effective->overlay_logo_width ?? 150);
                        $logoH = (int) ($effective->overlay_logo_height ?? 100);
                        $logoX = (int) ($effective->overlay_logo_x ?? 20);
                        $logoY = (int) ($effective->overlay_logo_y ?? 20);
                        $safeLogo = $escapeForMoviePath($logoAbs);

                        $filters[] = "movie='{$safeLogo}':s={$logoW}x{$logoH}[logo]";
                        $filters[] = "{$lastLabel}[logo]overlay={$logoX}:{$logoY}[withlogo]";
                        $lastLabel = '[withlogo]';
                    }
                }

                // Text
                if ($effective->overlay_text_enabled) {
                    $mode = strtolower(trim((string) ($effective->overlay_text_content ?? 'channel_name')));
                    $rawText = match ($mode) {
                        'title' => (string) ($video->title ?? ''),
                        'custom' => (string) ($effective->overlay_text_custom ?? $effective->name ?? ''),
                        default => (string) ($effective->name ?? ''),
                    };

                    $x = (int) ($effective->overlay_text_x ?? 20);
                    $y = (int) ($effective->overlay_text_y ?? 120);
                    $fontSize = (int) ($effective->overlay_text_font_size ?? 28);
                    $color = $normalizeFfmpegColor((string) ($effective->overlay_text_color ?? '#FFFFFF'));

                    $safeText = $escapeForDrawtext($rawText);
                    $filters[] = "{$lastLabel}drawtext=text='{$safeText}':x={$x}:y={$y}:fontsize={$fontSize}:fontcolor={$color}[txt]";
                    $lastLabel = '[txt]';
                }

                // Timer
                if ($effective->overlay_timer_enabled) {
                    $timerX = (int) ($effective->overlay_timer_x ?? 1700);
                    $timerY = (int) ($effective->overlay_timer_y ?? 50);
                    $timerFont = (int) ($effective->overlay_timer_font_size ?? 24);
                    $timerColor = $normalizeFfmpegColor((string) ($effective->overlay_timer_color ?? '#FFFFFF'));

                    $mode = strtolower(trim((string) ($effective->overlay_timer_mode ?? 'elapsed')));
                    $fmt = (string) ($effective->overlay_timer_format ?? 'HH:mm');
                    $timeExpr = "%{pts\\:hms}";

                    if ($mode === 'realtime') {
                        $ff = match ($fmt) {
                            'HH:mm:ss' => '%H\\:%M\\:%S',
                            'HH:mm:ss.mmm' => '%H\\:%M\\:%S',
                            default => '%H\\:%M',
                        };
                        $timeExpr = "%{localtime:{$ff}}";
                    }

                    if ($mode === 'countdown') {
                        $dur = (int) ($video->duration_seconds ?? 0);
                        if ($dur > 0) {
                            $D = $dur;
                            $timeExpr = "%{eif\\:mod(max(0\\,{$D}-t)\\,3600)/60\\:d\\:2}\\:%{eif\\:mod(max(0\\,{$D}-t)\\,60)\\:d\\:2}";
                        }
                    }

                    $filters[] = "{$lastLabel}drawtext=text='{$timeExpr}':x={$timerX}:y={$timerY}:fontsize={$timerFont}:fontcolor={$timerColor}[timer]";
                    $lastLabel = '[timer]';
                }

                $filters[] = "{$lastLabel}format=yuv420p[out]";
                $filter = implode(';', $filters);
            }

            // Preview cu overlay (dacă e activat)
            $cmd = [
                'ffmpeg',
                '-i', escapeshellarg($inputFile),
                '-t', (string) $seconds,
            ];

            if (!empty($filter)) {
                // Avoid fragile shell-escaping for complex filter graphs (drawtext/movie).
                // Use filter_complex_script to pass the graph via file.
                $filterFile = $previewDir . '/filter_' . time() . '.txt';
                @file_put_contents($filterFile, $filter);
                $cmd = array_merge($cmd, [
                    '-filter_complex_script', escapeshellarg($filterFile),
                    '-map', '[out]',
                    '-map', '0:a?',
                ]);
            }

            $cmd = array_merge($cmd, [
                '-c:v', 'libx264',
                '-preset', 'ultrafast',
                '-crf', '23',
                '-c:a', 'aac',
                '-b:a', '128k',
                '-y',
                escapeshellarg($outputFile),
            ]);

            exec(implode(' ', $cmd) . ' 2>&1', $output, $code);

            if (isset($filterFile) && is_string($filterFile) && $filterFile !== '') {
                @unlink($filterFile);
            }

            if ($code !== 0 || !file_exists($outputFile)) {
                $tail = implode("\n", array_slice($output ?? [], -50));
                \Log::error('Preview ffmpeg failed', [
                    'channel_id' => $channel->id,
                    'video_id' => $video->id,
                    'exit_code' => $code,
                    'cmd' => implode(' ', $cmd),
                    'tail' => $tail,
                ]);
                return response()->json(['status' => 'error', 'message' => 'Failed to generate preview'], 500);
            }

            return response()->json([
                'status' => 'success',
                'preview_url' => '/storage/previews/' . $channel->id . '/' . basename($outputFile),
                'message' => 'Preview generat (cu overlay dacă e activ).',
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Start a 30s TEST encode (MP4) with overlay.
     * Used for verifying overlay quickly, then user can run full TS encode.
     */
    public function testEncode(Request $request, LiveChannel $channel)
    {
        try {
            $settings = $request->input('settings', []);
            if (is_string($settings)) {
                $decoded = json_decode($settings, true);
                $settings = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($settings)) {
                $settings = [];
            }

            $videoId = (int) $request->input('video_id');
            if ($videoId <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'video_id is required',
                ], 422);
            }

            $video = \App\Models\Video::query()->find($videoId);
            if (!$video) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Video not found',
                ], 404);
            }

            $inputPath = trim((string) ($video->file_path ?? ''));
            if ($inputPath === '' || !file_exists($inputPath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Video file not found',
                ], 400);
            }

            $previewDir = storage_path("app/public/previews/{$channel->id}");
            @mkdir($previewDir, 0755, true);
            $outputPath = $previewDir . "/test_video_{$videoId}.mp4";
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            $jobSettings = array_merge($settings, [
                'output_container' => 'mp4',
                'test_duration_seconds' => 30,
                'job_type' => 'test',
            ]);

            $job = \App\Models\EncodingJob::query()->create([
                'channel_id' => $channel->id,
                'live_channel_id' => $channel->id,
                'video_id' => $videoId,
                'playlist_item_id' => null,
                'input_path' => $inputPath,
                'output_path' => $outputPath,
                'settings' => $jobSettings,
                'status' => 'queued',
                'progress' => 0,
                'started_at' => null,
                'completed_at' => null,
                'finished_at' => null,
                'error_message' => null,
            ]);

            $this->startEncodingProcess($job, $channel);

            return response()->json([
                'status' => 'success',
                'message' => 'Test encode started (30s)',
                'job_id' => $job->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Test encode failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete the current test output for a given video (MP4 preview).
     */
    public function deleteTestOutput(Request $request, LiveChannel $channel)
    {
        try {
            $videoId = (int) $request->input('video_id');
            if ($videoId <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'video_id is required',
                ], 422);
            }

            $previewDir = storage_path("app/public/previews/{$channel->id}");
            $outputPath = $previewDir . "/test_video_{$videoId}.mp4";
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Test deleted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current stream output URLs and status
     */
    public function outputStreams(Request $request, LiveChannel $channel)
    {
        try {
            // Get the streaming URLs based on channel configuration
            $domain = rtrim((string) config('app.streaming_domain', ''), '/');
            if ($domain === '' || str_contains($domain, 'localhost')) {
                $domain = rtrim((string) $request->getSchemeAndHttpHost(), '/');
            }
            
            // Check if channel is currently running
            $engine = new \App\Services\ChannelEngineService($channel);
            $pid = (int) ($channel->encoder_pid ?? 0);
            $isRunning = $pid > 0 ? $engine->isRunning($pid) : false;
            
            // Get output paths
            $tsUrl = "{$domain}/streams/{$channel->id}/stream.ts";
            $hlsUrl = "{$domain}/streams/{$channel->id}/hls/stream.m3u8";
            
            // Check if output files exist
            $outputDir = storage_path("app/streams/{$channel->id}");
            $tsFileExists = file_exists("{$outputDir}/stream.ts");
            $hlsFileExists = file_exists("{$outputDir}/hls/stream.m3u8");
            
            return response()->json([
                'status' => 'success',
                'channel_id' => $channel->id,
                'is_running' => $isRunning,
                'streams' => [
                    [
                        'type' => 'TS (MPEG-TS)',
                        'format' => 'mpegts',
                        'url' => $tsUrl,
                        'file_exists' => $tsFileExists,
                        'use_case' => 'Xtream Codes, Streaming',
                        'protocol' => 'HTTP',
                        'curl_command' => "curl -o output.ts '{$tsUrl}'",
                    ],
                    [
                        'type' => 'HLS (HTTP Live Streaming)',
                        'format' => 'hls',
                        'url' => $hlsUrl,
                        'file_exists' => $hlsFileExists,
                        'use_case' => 'Browsers, VLC, Web Playback',
                        'protocol' => 'HTTP',
                        'curl_command' => "curl -o playlist.m3u8 '{$hlsUrl}'",
                    ],
                ],
                'note' => 'Streams are available only when channel is running. Use the URLs above in your player or Xtream Codes.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function nowPlaying(Request $request, LiveChannel $channel)
    {
        try {
            $engine = new \App\Services\ChannelEngineService($channel);
            $pid = (int) ($channel->encoder_pid ?? 0);
            $isRunning = $pid > 0 ? $engine->isRunning($pid) : false;

            // Only meaningful when looping encoded playlist.
            $outputDir = storage_path("app/streams/{$channel->id}");

            $playlistItems = \App\Models\PlaylistItem::query()
                ->where(function ($q) use ($channel) {
                    $q->where('live_channel_id', $channel->id)
                      ->orWhere('vod_channel_id', $channel->id);
                })
                ->with('video')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $segments = [];
            foreach ($playlistItems as $item) {
                $video = $item->video;
                if (!$video) continue;

                $duration = (int) ($video->duration_seconds ?? 0);
                if ($duration <= 0) continue;

                $primary = $outputDir . '/video_' . (int) $item->id . '.ts';
                $fallback = $outputDir . '/video_' . (int) ($item->video_id ?? 0) . '.ts';
                $tsExists = is_file($primary) || (((int) ($item->video_id ?? 0) > 0) && is_file($fallback));
                if (!$tsExists) continue;

                $title = trim((string) ($video->title ?? ''));
                if ($title === '') $title = 'Video #' . (int) ($video->id ?? 0);

                $segments[] = [
                    'title' => $title,
                    'duration' => $duration,
                ];
            }

            if (!$isRunning || empty($segments) || empty($channel->started_at)) {
                return response()->json([
                    'status' => 'success',
                    'is_running' => (bool) $isRunning,
                    'has_playlist' => !empty($segments),
                    'now' => null,
                    'next' => [],
                ]);
            }

            $total = array_sum(array_map(fn ($s) => (int) $s['duration'], $segments));
            if ($total <= 0) {
                return response()->json([
                    'status' => 'success',
                    'is_running' => true,
                    'has_playlist' => false,
                    'now' => null,
                    'next' => [],
                ]);
            }

            $startedAt = Carbon::parse($channel->started_at);
            $elapsed = max(0, $startedAt->diffInSeconds(Carbon::now()));
            $pos = $elapsed % $total;

            $idx = 0;
            $offset = $pos;
            foreach ($segments as $i => $s) {
                $dur = (int) $s['duration'];
                if ($offset < $dur) {
                    $idx = $i;
                    break;
                }
                $offset -= $dur;
            }

            $cur = $segments[$idx];
            $remaining = max(0, (int) $cur['duration'] - (int) $offset);

            $next = [];
            $n = count($segments);
            for ($k = 1; $k <= 3; $k++) {
                $next[] = [
                    'title' => (string) $segments[($idx + $k) % $n]['title'],
                    'index' => (($idx + $k) % $n) + 1,
                ];
            }

            return response()->json([
                'status' => 'success',
                'is_running' => true,
                'has_playlist' => true,
                'now' => [
                    'title' => (string) $cur['title'],
                    'index' => $idx + 1,
                    'remaining_seconds' => (int) $remaining,
                ],
                'next' => $next,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start channel with 24/7 looping (concat demuxer)
     */
    public function startChannelWithLooping(Request $request, LiveChannel $channel)
    {
        try {
            $engine = new \App\Services\ChannelEngineService($channel);

            // Strong duplicate protection: detect ffmpeg already writing into this channel output dir.
            $detectedPid = $engine->detectRunningFfmpegPid();
            if ($detectedPid) {
                $channel->update(['encoder_pid' => $detectedPid, 'status' => 'live']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Channel already running (detected ffmpeg PID ' . $detectedPid . ')',
                ], 409);
            }

            // Check if already running
            if ($engine->isRunning($channel->encoder_pid)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Channel already running',
                ], 409);
            }

            // Prefer looping from playlist TS-ready items (exactly what was encoded).
            $playlistItems = PlaylistItem::query()
                ->where(function ($q) use ($channel) {
                    $q->where('live_channel_id', $channel->id)
                      ->orWhere('vod_channel_id', $channel->id);
                })
                ->orderBy('sort_order')
                ->get(['id', 'video_id']);

            $outputDir = storage_path("app/streams/{$channel->id}");
            $encodedPaths = [];
            foreach ($playlistItems as $item) {
                $primary = $outputDir . '/video_' . (int) $item->id . '.ts';
                $fallback = $outputDir . '/video_' . (int) ($item->video_id ?? 0) . '.ts';
                if (is_file($primary)) {
                    $encodedPaths[] = $primary;
                } elseif (($item->video_id ?? 0) && is_file($fallback)) {
                    $encodedPaths[] = $fallback;
                }
            }

            if (!empty($encodedPaths)) {
                // FIFO playlist mode: do not stop channel; feeder will append new TS-ready items
                // at the end of each cycle.
                $ffmpegCommand = $engine->generatePlayCommandFromFilesFifo();
            } else {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No encoded TS files found for this channel playlist. Please encode first.',
                    ], 400);
                }

                return back()->with('error', 'No encoded TS files found for this channel playlist. Please encode first.');
            }

            // Start the channel with looping
            $result = $engine->start($ffmpegCommand);

            if ($request->expectsJson()) {
                if ($result['status'] === 'success') {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Channel started with 24/7 looping',
                        'mode' => !empty($encodedPaths) ? '24/7 LOOPING (ENCODED PLAYLIST)' : '24/7 LOOPING (DIRECT)',
                        'encoded_count' => count($encodedPaths),
                        'pid' => $result['pid'],
                        'job_id' => $result['job_id'],
                    ]);
                }
                return response()->json($result, 400);
            }

            if (($result['status'] ?? '') === 'success') {
                $msg = 'Channel started with 24/7 looping';
                if (!empty($encodedPaths)) {
                    $msg .= ' (encoded playlist: ' . count($encodedPaths) . ' files)';
                }
                if (isset($result['pid'])) {
                    $msg .= ' (PID ' . $result['pid'] . ')';
                }
                return back()->with('success', $msg);
            }

            return back()->with('error', $result['message'] ?? 'Failed to start channel');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to start looping channel: {$e->getMessage()}");
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start encoding all playlist videos (offline)
     * Creates EncodingJob for each video in playlist
     */
    public function startEncoding(Request $request, LiveChannel $channel)
    {
        try {
            $settings = $request->input('settings', []);
            if (is_string($settings)) {
                $decoded = json_decode($settings, true);
                $settings = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($settings)) {
                $settings = [];
            }

            $videoIds = $request->input('video_ids', []);
            if (is_string($videoIds)) {
                $videoIds = array_filter(array_map('intval', preg_split('/\s*,\s*/', $videoIds)));
            }
            if (!is_array($videoIds)) {
                $videoIds = [];
            }
            $videoIds = array_values(array_filter(array_map('intval', $videoIds)));

            // UX: dacă userul dă Encode Selected, îl ajutăm: adăugăm automat video-urile lipsă în playlist.
            if (!empty($videoIds)) {
                $existing = \App\Models\PlaylistItem::query()
                    ->where('live_channel_id', $channel->id)
                    ->whereIn('video_id', $videoIds)
                    ->pluck('video_id')
                    ->map(fn ($v) => (int) $v)
                    ->all();

                $missing = array_values(array_diff($videoIds, $existing));
                if (!empty($missing)) {
                    $maxSort = (int) (\App\Models\PlaylistItem::query()
                        ->where('live_channel_id', $channel->id)
                        ->max('sort_order') ?? 0);
                    $nextSort = $maxSort + 1;

                    foreach ($missing as $vid) {
                        // dacă video-ul nu există, îl sărim
                        if (!\App\Models\Video::query()->whereKey($vid)->exists()) {
                            continue;
                        }

                        \App\Models\PlaylistItem::query()->create([
                            'live_channel_id' => $channel->id,
                            'vod_channel_id'  => $channel->id,
                            'video_id' => (int) $vid,
                            'sort_order' => $nextSort++, 
                        ]);
                    }
                }
            }

            $playlistQuery = $channel->playlistItems()
                ->with('video')
                ->orderBy('sort_order');

            if (!empty($videoIds)) {
                $playlistQuery->whereIn('video_id', $videoIds);
            }

            $playlistItems = $playlistQuery->get();

            if ($playlistItems->isEmpty()) {
                $payload = [
                    'status' => 'error',
                    'message' => empty($videoIds)
                        ? 'Channel has no videos in playlist'
                        : 'None of the selected videos are in the playlist',
                ];

                if ($request->expectsJson()) {
                    return response()->json($payload, 400);
                }

                return back()->with('error', $payload['message']);
            }

            $outputDir = storage_path("app/streams/{$channel->id}");
            @mkdir($outputDir, 0755, true);

            $createdJobs = 0;
            
            foreach ($playlistItems as $item) {
                $video = $item->video;

                if (!$video) {
                    continue;
                }
                
                $inputPath = trim((string) ($video->file_path ?? ''));
                if ($inputPath === '' || !file_exists($inputPath)) {
                    continue;
                }

                // Cleanup: remove test preview (30s MP4) for this video if it exists
                $testPath = storage_path("app/public/previews/{$channel->id}/test_video_{$video->id}.mp4");
                if (file_exists($testPath)) {
                    @unlink($testPath);
                }

                $outputPath = "{$outputDir}/video_{$item->id}.ts";
                $fallbackExisting = "{$outputDir}/video_{$video->id}.ts";
                
                // Check if job already exists and is not done
                $existingJob = \App\Models\EncodingJob::query()
                    ->where(function ($q) use ($channel) {
                        $q->where('live_channel_id', $channel->id)
                          ->orWhere('channel_id', $channel->id);
                    })
                    ->where('playlist_item_id', $item->id)
                    ->first();

                // If TS already exists (either current playlist_item_id-based name or legacy video_id-based name),
                // do not queue a new encode job.
                if (file_exists($outputPath) || file_exists($fallbackExisting)) {
                    if ($existingJob && $existingJob->status !== 'running') {
                        $existingJob->update([
                            'status' => 'done',
                            'progress' => 100,
                            'completed_at' => now(),
                            'finished_at' => now(),
                            'error_message' => null,
                        ]);
                    }
                    continue;
                }
                
                // If job exists and is done, don't recreate it
                if ($existingJob && $existingJob->status === 'done') {
                    continue;
                }

                // If job exists and is currently running, do not touch it.
                if ($existingJob && $existingJob->status === 'running') {
                    continue;
                }
                
                // Create or update job (but only reset if not already running/done)
                $job = \App\Models\EncodingJob::updateOrCreate(
                    [
                        'live_channel_id' => $channel->id,
                        'playlist_item_id' => $item->id,
                    ],
                    [
                        'channel_id' => $channel->id,
                        'live_channel_id' => $channel->id,
                        'video_id' => $video->id,
                        'input_path' => $inputPath,
                        'output_path' => $outputPath,
                        'settings' => $settings,
                        'status' => 'queued',
                        'started_at' => null,
                        'completed_at' => null,
                        'progress' => 0,
                    ]
                );

                // If source is already TS, just copy it into the channel streams folder
                $ext = strtolower(pathinfo((string)$video->file_path, PATHINFO_EXTENSION));
                $isTs = ($ext === 'ts') || (strtolower((string)($video->format ?? '')) === 'ts');

                if ($isTs) {
                    if (!file_exists($outputPath)) {
                        @copy($inputPath, $outputPath);
                    }

                    $job->update([
                        'status' => file_exists($outputPath) ? 'done' : 'failed',
                        'progress' => file_exists($outputPath) ? 100 : 0,
                        'completed_at' => now(),
                        'finished_at' => now(),
                        'error_message' => file_exists($outputPath) ? null : 'Failed to copy TS input',
                    ]);

                    continue;
                }

                // Queue job (do NOT start immediately here; we run sequentially).
                if ($job->status !== 'done') {
                    $createdJobs++;
                }
            }

            // Start only one job at a time per channel.
            $started = $this->startNextQueuedEncodingJobIfIdle($channel);

            $payload = [
                'status' => 'success',
                'message' => "Queued {$createdJobs} encoding jobs",
                'total_jobs' => $createdJobs,
                'started_job_id' => $started?->id,
            ];

            if ($request->expectsJson()) {
                return response()->json($payload);
            }

            return back()->with('success', $payload['message']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to start encoding: {$e->getMessage()}");
            $payload = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];

            if ($request->expectsJson()) {
                return response()->json($payload, 500);
            }

            return back()->with('error', $payload['message']);
        }
    }

    /**
     * Start encoding for a specific job in background
     */
    protected function startEncodingProcess(\App\Models\EncodingJob $job, LiveChannel $channel)
    {
        try {
            $encoding = new \App\Services\EncodingService($job, $channel);
            $encoding->startAsync();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to start encoding process for job {$job->id}: {$e->getMessage()}");
        }
    }

    /**
     * Sequential encoding runner: starts the next queued job only if none is running for this channel.
     */
    protected function startNextQueuedEncodingJobIfIdle(LiveChannel $channel): ?\App\Models\EncodingJob
    {
        $outputDir = storage_path("app/streams/{$channel->id}");
        $outputRelLike = 'streams/' . $channel->id . '/video_%';

        // NOTE: Do not rely solely on cache locks (can be "array" driver in prod).
        // Use a DB transaction + row locks to guarantee only one job becomes running.
        $claimed = null;

        $scope = function ($q) use ($channel, $outputDir, $outputRelLike) {
            return $q->where(function ($qq) use ($channel) {
                // Qualify columns because some queries join playlist_items (which also has live_channel_id)
                $qq->where('encoding_jobs.live_channel_id', $channel->id)
                   ->orWhere('encoding_jobs.channel_id', $channel->id);
            })
            // Only offline encoding jobs (exclude engine "streaming" jobs)
            ->where('encoding_jobs.video_id', '>', 0)
                        ->where(function ($qq) use ($outputDir, $outputRelLike) {
                $qq->where(function ($q2) {
                    $q2->whereNotNull('encoding_jobs.playlist_item_id')
                       ->where('encoding_jobs.playlist_item_id', '>', 0);
                })
                  ->orWhere(function ($q2) use ($outputDir, $outputRelLike) {
                    $q2->whereNotNull('encoding_jobs.output_path')
                      ->where(function ($q3) use ($outputDir, $outputRelLike) {
                          $q3->where('encoding_jobs.output_path', 'like', $outputDir . '/video_%')
                          ->orWhere('encoding_jobs.output_path', 'like', $outputRelLike);
                      });
                });
            });
        };

        \DB::transaction(function () use ($channel, $scope, &$claimed) {
            $hasRunning = $scope(\App\Models\EncodingJob::query())
                ->where('encoding_jobs.status', 'running')
                ->lockForUpdate()
                ->exists();

            if ($hasRunning) {
                $claimed = null;
                return;
            }

            $next = $scope(\App\Models\EncodingJob::query())
                ->where('encoding_jobs.status', 'queued')
                ->whereNotNull('encoding_jobs.playlist_item_id')
                ->where('encoding_jobs.playlist_item_id', '>', 0)
                ->leftJoin('playlist_items', 'encoding_jobs.playlist_item_id', '=', 'playlist_items.id')
                ->orderByRaw('COALESCE(playlist_items.sort_order, 2147483647) asc')
                ->orderBy('encoding_jobs.created_at')
                ->select('encoding_jobs.*')
                ->lockForUpdate()
                ->first();

            if (!$next) {
                $next = $scope(\App\Models\EncodingJob::query())
                    ->where('encoding_jobs.status', 'queued')
                    ->orderBy('encoding_jobs.created_at')
                    ->lockForUpdate()
                    ->first();
            }

            if (!$next) {
                $claimed = null;
                return;
            }

            // Claim the job so concurrent requests can't start multiple.
            $next->update([
                'status' => 'running',
                'started_at' => now(),
                'pid' => null,
                'progress' => (int) ($next->progress ?? 0),
                'error_message' => null,
            ]);

            $claimed = $next;
        });

        if ($claimed) {
            $this->startEncodingProcess($claimed, $channel);
        }

        return $claimed;
    }

    /**
     * Get encoding job progress
     */
    public function getEncodingJobs(Request $request, LiveChannel $channel)
    {
        try {
            $outputDir = storage_path("app/streams/{$channel->id}");
            $outputRelLike = 'streams/' . $channel->id . '/video_%';
            $jobs = \App\Models\EncodingJob::query()
                ->where(function ($q) use ($channel) {
                    $q->where('live_channel_id', $channel->id)
                      ->orWhere('channel_id', $channel->id);
                })
                // Only offline encoding jobs (exclude engine streaming jobs)
                ->where('video_id', '>', 0)
                ->where(function ($q) use ($outputDir, $outputRelLike) {
                    $q->where(function ($q2) {
                        $q2->whereNotNull('playlist_item_id')
                           ->where('playlist_item_id', '>', 0);
                    })
                    ->orWhere(function ($q2) use ($outputDir, $outputRelLike) {
                        $q2->whereNotNull('output_path')
                           ->where(function ($q3) use ($outputDir, $outputRelLike) {
                               $q3->where('output_path', 'like', $outputDir . '/video_%')
                                  ->orWhere('output_path', 'like', $outputRelLike);
                           });
                    });
                })
                ->with(['playlistItem.video', 'video'])
                ->orderBy('created_at', 'desc')
                ->get();

            $parseProgressFile = function (?string $path): array {
                $out = [
                    'out_time_ms' => null,
                    'speed' => null,
                    'progress' => null,
                ];
                $p = trim((string) ($path ?? ''));
                if ($p === '' || !is_file($p)) return $out;

                // Read a small tail; progress file is small, but keep it bounded.
                $data = @file_get_contents($p);
                if (!is_string($data) || $data === '') return $out;

                if (preg_match_all('/^out_time_ms=(\d+)$/m', $data, $m) && !empty($m[1])) {
                    $out['out_time_ms'] = (int) end($m[1]);
                }
                if (preg_match_all('/^speed=([^\r\n]+)$/m', $data, $m) && !empty($m[1])) {
                    $out['speed'] = (string) end($m[1]);
                }
                if (preg_match_all('/^progress=([^\r\n]+)$/m', $data, $m) && !empty($m[1])) {
                    $out['progress'] = (string) end($m[1]);
                }

                return $out;
            };

            $publicUrlForOutput = function (?string $outputPath) use ($channel): ?string {
                $p = (string) ($outputPath ?? '');
                if ($p === '') return null;

                // We only expose URLs for files under storage/app/public/previews/{channel}
                $base = storage_path('app/public/previews/' . $channel->id) . DIRECTORY_SEPARATOR;
                $norm = str_replace(['\\'], ['/',], $p);
                $baseNorm = str_replace(['\\'], ['/',], $base);
                if (str_starts_with($norm, $baseNorm)) {
                    $file = basename($p);
                    return '/storage/previews/' . $channel->id . '/' . $file;
                }
                return null;
            };

            $computed = [];
            foreach ($jobs as $job) {
                $settings = $job->settings;
                if (!is_array($settings)) $settings = [];
                $progressFile = $settings['_progress_file'] ?? null;

                $isPidAlive = function ($pid): bool {
                    $p = (int) ($pid ?? 0);
                    if ($p <= 0) return false;
                    if (function_exists('posix_kill')) {
                        return @posix_kill($p, 0);
                    }
                    return is_dir('/proc/' . $p);
                };

                $tailOfEncodingLog = function (int $jobId): ?string {
                    $path = storage_path('logs/encoding_job_' . $jobId . '.log');
                    if (!is_file($path)) return null;
                    $data = @file_get_contents($path);
                    if (!is_string($data) || $data === '') return null;
                    // Keep it small
                    if (strlen($data) > 6000) {
                        $data = substr($data, -6000);
                    }
                    $lines = preg_split('/\r\n|\r|\n/', $data) ?: [];
                    $lines = array_values(array_filter($lines, fn ($l) => trim((string) $l) !== ''));
                    $tail = array_slice($lines, -25);
                    $txt = trim(implode("\n", $tail));
                    return $txt !== '' ? $txt : null;
                };

                $progressMeta = $parseProgressFile(is_string($progressFile) ? $progressFile : null);

                $durationSeconds = (int) ($settings['test_duration_seconds'] ?? 0);
                if ($durationSeconds <= 0) {
                    $durationSeconds = (int) ($job->video?->duration_seconds ?? $job->playlistItem?->video?->duration_seconds ?? 0);
                }

                $pct = (int) ($job->progress ?? 0);
                if (is_int($progressMeta['out_time_ms']) && $durationSeconds > 0) {
                    $pct = (int) floor(min(99, max(0, ($progressMeta['out_time_ms'] / ($durationSeconds * 1000000)) * 100)));
                }

                if ($job->status === 'done') {
                    $pct = 100;
                }

                $eta = null;
                if ($job->status === 'running' && $durationSeconds > 0 && is_int($progressMeta['out_time_ms'])) {
                    $outSeconds = (int) floor(max(0, $progressMeta['out_time_ms']) / 1000000);
                    $remain = max(0, $durationSeconds - $outSeconds);
                    $eta = gmdate('H:i:s', (int) $remain);
                }

                // If ffmpeg reported end and output exists, mark as done
                if ($job->status === 'running' && ($progressMeta['progress'] === 'end')) {
                    if ($job->output_path && file_exists($job->output_path)) {
                        $job->update([
                            'status' => 'done',
                            'progress' => 100,
                            'completed_at' => now(),
                            'finished_at' => now(),
                        ]);
                        $pct = 100;
                    }
                }

                // If process is no longer running and output is missing, mark as failed (avoid stuck "running" forever).
                if ($job->status === 'running') {
                    $pid = (int) ($job->pid ?? 0);
                    $alive = $isPidAlive($pid);
                    $hasOutput = ($job->output_path && file_exists($job->output_path));

                    // If job is marked running but has no PID for a while, fail it so the queue can move on.
                    if ($pid <= 0) {
                        $startedAt = $job->started_at ? \Carbon\Carbon::parse($job->started_at) : null;
                        if ($startedAt && $startedAt->diffInSeconds(now()) > 60) {
                            $job->update([
                                'status' => 'failed',
                                'error_message' => 'Encoding did not start (missing PID)',
                                'finished_at' => now(),
                                'completed_at' => now(),
                            ]);
                        }
                    }

                    if ($pid > 0 && !$alive) {
                        $err = $tailOfEncodingLog((int) $job->id);
                        $hasErr = is_string($err) && $err !== '' && (
                            str_contains($err, 'Error') ||
                            str_contains($err, 'Failed') ||
                            str_contains($err, 'Permission denied') ||
                            str_contains($err, 'Filter not found') ||
                            str_contains($err, 'No such filter')
                        );

                        if (!$hasOutput) {
                            $job->update([
                                'status' => 'failed',
                                'error_message' => $err ?: 'FFmpeg exited without producing output',
                                'finished_at' => now(),
                                'completed_at' => now(),
                            ]);
                        } elseif ($hasErr) {
                            // Output exists (maybe overwritten by later run), but this job clearly errored.
                            $job->update([
                                'status' => 'failed',
                                'error_message' => $err,
                                'finished_at' => now(),
                                'completed_at' => now(),
                            ]);
                        } else {
                            // PID dead + output exists + no obvious errors -> treat as completed.
                            $job->update([
                                'status' => 'done',
                                'progress' => 100,
                                'completed_at' => now(),
                                'finished_at' => now(),
                            ]);
                        }
                    }
                }

                // Keep DB progress in sync (best-effort)
                if ($job->status === 'running' && $pct > (int) ($job->progress ?? 0)) {
                    $job->update(['progress' => $pct]);
                }

                $computed[$job->id] = [
                    'pct' => $pct,
                    'speed' => is_string($progressMeta['speed']) ? $progressMeta['speed'] : null,
                    'out_time' => is_int($progressMeta['out_time_ms'])
                        ? gmdate('H:i:s', (int) floor(max(0, $progressMeta['out_time_ms']) / 1000000))
                        : null,
                    'eta' => $eta,
                    'output_url' => $publicUrlForOutput($job->output_path),
                ];
            }

            // Queue position for queued jobs (matches sequential runner ordering).
            $queuedOrdered = $jobs
                ->filter(fn ($j) => strtolower((string) ($j->status ?? '')) === 'queued')
                ->sort(function ($a, $b) {
                    $sa = (int) ($a->playlistItem?->sort_order ?? PHP_INT_MAX);
                    $sb = (int) ($b->playlistItem?->sort_order ?? PHP_INT_MAX);
                    if ($sa !== $sb) return $sa <=> $sb;
                    return ((string) ($a->created_at ?? '')) <=> ((string) ($b->created_at ?? ''));
                })
                ->values();

            $queuedPosById = [];
            foreach ($queuedOrdered as $i => $j) {
                $queuedPosById[(int) $j->id] = $i + 1;
            }

            $totalJobs = $jobs->count();
            $completedJobs = $jobs->where('status', 'done')->count();
            $runningJobs = $jobs->where('status', 'running')->count();

            // Auto-run queue: if nothing is running, start the next queued job.
            $startedNext = null;
            if ($runningJobs === 0) {
                $startedNext = $this->startNextQueuedEncodingJobIfIdle($channel);
            }

            return response()->json([
                'status' => 'success',
                'total_jobs' => $totalJobs,
                'completed_jobs' => $completedJobs,
                'running_jobs' => $runningJobs,
                'started_next_job_id' => $startedNext?->id,
                'jobs' => $jobs->map(fn($job) => [
                    'id' => $job->id,
                    'video_id' => $job->video_id,
                    'playlist_item_id' => (int) ($job->playlist_item_id ?? 0),
                    'video_title' => $job->playlistItem?->video?->title ?? $job->video?->title ?? 'Unknown',
                    'status' => $job->status,
                    'progress' => $computed[$job->id]['pct'] ?? ($job->progress ?? 0),
                    'speed' => $computed[$job->id]['speed'] ?? null,
                    'out_time' => $computed[$job->id]['out_time'] ?? null,
                    'eta' => $computed[$job->id]['eta'] ?? null,
                    'queued_position' => $queuedPosById[(int) $job->id] ?? null,
                    'output_path' => $job->output_path,
                    'output_url' => $computed[$job->id]['output_url'] ?? null,
                ])->values(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an offline TS encoding job for this channel.
     * - If queued: remove it from queue (delete row).
     * - If running: stop ffmpeg (best-effort) and mark job as failed (cancelled).
     * We do NOT delete any already-encoded TS files.
     */
    public function cancelEncodingJob(Request $request, LiveChannel $channel, \App\Models\EncodingJob $job)
    {
        try {
            $belongs = ((int) ($job->live_channel_id ?? 0) === (int) $channel->id)
                || ((int) ($job->channel_id ?? 0) === (int) $channel->id);

            if (!$belongs) {
                abort(404);
            }

            $outputDir = storage_path("app/streams/{$channel->id}");
            $outputPath = (string) ($job->output_path ?? '');
            $normOut = str_replace(['\\'], ['/',], $outputPath);
            $normPrefix = str_replace(['\\'], ['/',], $outputDir . '/video_');

            $isOffline = ((int) ($job->video_id ?? 0) > 0) && (
                ((int) ($job->playlist_item_id ?? 0) > 0) ||
                ($outputPath !== '' && str_starts_with($normOut, $normPrefix))
            );

            if (!$isOffline) {
                abort(404);
            }

            $status = strtolower((string) ($job->status ?? ''));
            if (!in_array($status, ['queued', 'running'], true)) {
                $payload = [
                    'status' => 'error',
                    'message' => 'Job is not queued/running',
                ];
                if ($request->expectsJson()) {
                    return response()->json($payload, 422);
                }
                return back()->with('error', $payload['message']);
            }

            $message = null;

            if ($status === 'queued') {
                $job->delete();
                $message = 'Removed from encoding queue';
            } else {
                $pid = (int) ($job->pid ?? 0);
                $killed = false;

                if ($pid > 0) {
                    if (function_exists('posix_kill')) {
                        $killed = @posix_kill($pid, 15);
                    } else {
                        @shell_exec('kill -TERM ' . (int) $pid . ' 2>/dev/null');
                        $killed = !is_dir('/proc/' . $pid);
                    }
                }

                $job->update([
                    'status' => 'failed',
                    'error_message' => $killed
                        ? 'Cancelled by user (stopped ffmpeg)'
                        : 'Cancelled by user',
                    'finished_at' => now(),
                    'completed_at' => now(),
                ]);

                $message = 'Cancelled running encoding job';
            }

            // If we just removed/stopped a job, try to continue the queue.
            $this->startNextQueuedEncodingJobIfIdle($channel);

            $payload = [
                'status' => 'success',
                'message' => $message,
            ];

            if ($request->expectsJson()) {
                return response()->json($payload);
            }

            return back()->with('success', $payload['message']);

        } catch (\Exception $e) {
            $payload = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            if ($request->expectsJson()) {
                return response()->json($payload, 500);
            }
            return back()->with('error', $payload['message']);
        }
    }

    /**
     * Remove an item from the offline TS encoding queue for this channel.
     * This is keyed by PlaylistItem so it works even when no EncodingJob exists yet.
     * Behavior:
     * - If a related job is queued: delete it.
     * - If a related job is running: stop ffmpeg (best-effort) and delete the job.
     * - Remove the playlist item so the video returns to the category selection list.
     */
    public function removeEncodingQueueItem(Request $request, LiveChannel $channel, \App\Models\PlaylistItem $item)
    {
        $belongs = ((int) ($item->live_channel_id ?? 0) === (int) $channel->id)
            || ((int) ($item->vod_channel_id ?? 0) === (int) $channel->id);

        if (!$belongs) {
            abort(404);
        }

        $result = $this->removeEncodingQueueItemInternal($channel, $item);

        $payload = [
            'status' => 'success',
            'message' => !empty($result['stopped_any_running']) ? 'Stopped job and removed from queue' : 'Removed from queue',
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return back()->with('success', $payload['message']);
    }

    protected function removeEncodingQueueItemInternal(LiveChannel $channel, \App\Models\PlaylistItem $item): array
    {
        // Find jobs tied to this playlist item for this channel.
        $jobs = \App\Models\EncodingJob::query()
            ->where('live_channel_id', (int) $channel->id)
            ->where('playlist_item_id', (int) $item->id)
            ->orderByDesc('id')
            ->get();

        $stoppedAnyRunning = false;
        $deletedJobs = 0;

        foreach ($jobs as $job) {
            $status = strtolower((string) ($job->status ?? ''));
            if ($status === 'running') {
                $pid = (int) ($job->pid ?? 0);
                if ($pid > 0) {
                    if (function_exists('posix_kill')) {
                        @posix_kill($pid, 15);
                    } else {
                        @shell_exec('kill -TERM ' . (int) $pid . ' 2>/dev/null');
                    }
                }
                $stoppedAnyRunning = true;
            }

            $job->delete();
            $deletedJobs++;
        }

        // Remove playlist item so it becomes available again in category selection.
        $item->delete();

        // Continue the queue if we removed/stopped something.
        if ($stoppedAnyRunning || $deletedJobs > 0) {
            $this->startNextQueuedEncodingJobIfIdle($channel);
        }

        return [
            'stopped_any_running' => $stoppedAnyRunning,
            'deleted_jobs' => $deletedJobs,
        ];
    }

    /**
     * Bulk remove encoding queue items by playlist item ids.
     */
    public function removeEncodingQueueItemsBulk(Request $request, LiveChannel $channel)
    {
        $data = $request->validate([
            'playlist_item_ids' => ['required', 'array', 'min:1'],
            'playlist_item_ids.*' => ['integer', 'min:1'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['playlist_item_ids'])));
        $removed = 0;

        foreach ($ids as $id) {
            $item = \App\Models\PlaylistItem::query()->find($id);
            if (!$item) continue;

            $belongs = ((int) ($item->live_channel_id ?? 0) === (int) $channel->id)
                || ((int) ($item->vod_channel_id ?? 0) === (int) $channel->id);
            if (!$belongs) continue;

            $this->removeEncodingQueueItemInternal($channel, $item);
            $removed++;
        }

        $payload = [
            'status' => 'success',
            'message' => 'Removed ' . $removed . ' item(s) from queue',
            'removed' => $removed,
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return back()->with('success', $payload['message']);
    }

    /**
     * Check if encoded TS files exist for channel
     */
    public function checkEncodedFiles(LiveChannel $channel)
    {
        try {
            $outputDir = storage_path("app/streams/{$channel->id}");
            $encodedFiles = [];

            if (is_dir($outputDir)) {
                $encodedFiles = glob("{$outputDir}/video_*.ts") ?? [];
            }

            $hasEncoded = count($encodedFiles) > 0;

            return response()->json([
                'status' => 'success',
                'has_encoded' => $hasEncoded,
                'encoded_count' => count($encodedFiles),
                'files' => array_map('basename', $encodedFiles),
                'message' => $hasEncoded 
                    ? count($encodedFiles) . ' encoded TS files ready' 
                    : 'No encoded files. Click "Encode All to TS" first.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'has_encoded' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync playlist from category
     * Replaces current playlist with all videos from category
     */
    public function syncPlaylistFromCategory(LiveChannel $channel)
    {
        try {
            if (!$channel->video_category_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Channel has no category selected',
                ], 400);
            }

            // Get all videos from category
            $videos = Video::where('video_category_id', $channel->video_category_id)
                ->orderBy('title')
                ->get();

            if ($videos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category has no videos',
                ], 400);
            }

            // IMPORTANT: Do NOT wipe and recreate playlist items.
            // Recreating items changes IDs and breaks mapping to already-encoded TS files.
            // Instead: keep existing items (by video_id), update sort order, add missing, and remove extras.

            $existing = PlaylistItem::query()
                ->where(function ($q) use ($channel) {
                    $q->where('live_channel_id', $channel->id)
                      ->orWhere('vod_channel_id', $channel->id);
                })
                ->get();

            $existingByVideoId = $existing
                ->filter(fn ($it) => (int) ($it->video_id ?? 0) > 0)
                ->keyBy(fn ($it) => (int) $it->video_id);

            $keptItemIds = [];
            foreach ($videos as $index => $video) {
                $order = $index + 1;
                $videoId = (int) $video->id;

                $item = $existingByVideoId->get($videoId);
                if ($item) {
                    $item->sort_order = $order;
                    // Keep both columns in sync for compatibility.
                    $item->live_channel_id = $channel->id;
                    $item->vod_channel_id = $channel->id;
                    $item->save();
                    $keptItemIds[] = (int) $item->id;
                } else {
                    $new = PlaylistItem::create([
                        'live_channel_id' => $channel->id,
                        'vod_channel_id'  => $channel->id,
                        'video_id'        => $videoId,
                        'sort_order'      => $order,
                    ]);
                    $keptItemIds[] = (int) $new->id;
                }
            }

            // Remove playlist items that are no longer in the category.
            PlaylistItem::query()
                ->where(function ($q) use ($channel) {
                    $q->where('live_channel_id', $channel->id)
                      ->orWhere('vod_channel_id', $channel->id);
                })
                ->whereNotIn('id', $keptItemIds)
                ->delete();

            $payload = [
                'success' => true,
                'message' => count($videos) . ' videos synced from category',
                'count' => count($videos),
            ];

            if (request()->expectsJson()) {
                return response()->json($payload);
            }

            return redirect()
                ->route('vod-channels.playlist', $channel)
                ->with('success', $payload['message']);
        } catch (\Exception $e) {
            \Log::error('Sync playlist failed: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // CREATE VIDEO PAGE + API METHODS (NEW VARIANTA 2)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Show the "Create Video" page with categories + videos list
     */
    public function createVideoPage()
    {
        $categories = VideoCategory::orderBy('name')->get();
        $videos = [];
        $channel = null;

        return view('admin.vod_channels.create-video', [
            'categories' => $categories,
            'videos' => $videos,
            'channel' => $channel,
        ]);
    }

    /**
     * Show the "Create Video" page for a specific channel
     */
    public function createVideoPageForChannel(LiveChannel $channel)
    {
        $categories = VideoCategory::orderBy('name')->get();
        $videos = [];

        return view('admin.vod_channels.create-video', [
            'categories' => $categories,
            'videos' => $videos,
            'channel' => $channel,
        ]);
    }

    /**
     * API: Get videos by category (AJAX)
     */
    public function apiVideosByCategory(Request $request)
    {
        $categoryId = $request->get('category_id');

        if (!$categoryId) {
            return response()->json(['videos' => []]);
        }

        $videos = Video::where('video_category_id', $categoryId)
            ->orderBy('title')
            ->get()
            ->map(function($video) {
                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'file_path' => $video->file_path,
                    'file_size' => $video->size_bytes ?? 0,
                    'duration' => $video->duration_seconds ? gmdate('H:i:s', $video->duration_seconds) : null,
                    'format' => $video->format ?? 'mp4',
                    'resolution' => $video->resolution ?? '1920x1080',
                ];
            });

        return response()->json(['videos' => $videos]);
    }

    /**
     * API: Delete a video
     */
    public function apiDeleteVideo(Video $video)
    {
        try {
            $video->delete();

            return response()->json([
                'success' => true,
                'message' => 'Video deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Create encoding job from the create-video form
     */
    public function apiCreateEncodingJob(Request $request)
    {
        try {
            $payload = $request->all();

            // Get the video
            $video = Video::findOrFail($payload['video_id']);

            // Get the category
            $category = VideoCategory::findOrFail($payload['category_id']);

            // Build encoding settings
            $settings = $payload['settings'] ?? [];

            // Create a default encode profile if needed
            $profile = EncodeProfile::first();
            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'No encode profile configured',
                ], 400);
            }

            // Ensure output directory exists
            $outputDir = storage_path("app/streams/category-{$category->id}");
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Create encoding job with CORRECT column names
            $jobId = DB::table('encoding_jobs')->insertGetId([
                'video_id' => $video->id,
                'live_channel_id' => $payload['channel_id'] ?? null,
                'channel_id' => $payload['channel_id'] ?? null,
                'input_path' => $video->file_path,
                'output_path' => $outputDir . '/stream.ts',
                'status' => 'pending',
                'settings' => json_encode($settings),
                // Logo overlay fields
                'overlay_logo_enabled' => $settings['logo']['enabled'] ?? false,
                'overlay_logo_position' => $settings['logo']['pos'] ?? 'tl',
                'overlay_logo_x' => (int)($settings['logo']['x'] ?? 20),
                'overlay_logo_y' => (int)($settings['logo']['y'] ?? 20),
                'overlay_logo_width' => (int)($settings['logo']['w'] ?? 180),
                'overlay_logo_height' => (int)($settings['logo']['h'] ?? 56),
                'overlay_logo_opacity' => (float)($settings['logo']['opacity'] ?? 0.8),
                // Text overlay fields
                'overlay_text_enabled' => $settings['text']['enabled'] ?? false,
                'overlay_text_content' => $settings['text']['value'] ?? '',
                'overlay_text_font_family' => $settings['text']['font'] ?? 'Ubuntu',
                'overlay_text_font_size' => (int)($settings['text']['size'] ?? 15),
                'overlay_text_color' => $settings['text']['color'] ?? 'white',
                'overlay_text_position' => $settings['text']['pos'] ?? 'br',
                'overlay_text_x' => (int)($settings['text']['x'] ?? 30),
                'overlay_text_y' => (int)($settings['text']['y'] ?? 30),
                'overlay_text_opacity' => (float)($settings['text']['opacity'] ?? 1.0),
                'overlay_text_bg_color' => $settings['text']['box']['color'] ?? 'black',
                'overlay_text_bg_opacity' => ($settings['text']['box']['enabled'] ?? false) ? 0.5 : 0,
                'overlay_text_padding' => (int)($settings['text']['box']['padding'] ?? 5),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // START ENCODING IMMEDIATELY
            $this->startEncodingForJob($jobId, $video, $profile, $settings);

            return response()->json([
                'success' => true,
                'message' => 'Encoding job created and started',
                'job_id' => $jobId,
            ]);
        } catch (\Exception $e) {
            \Log::error('Create encoding job failed: ' . $e->getMessage() . '\n' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start encoding for a job with settings from form
     */
    private function startEncodingForJob($jobId, Video $video, EncodeProfile $profile, array $settings)
    {
        try {
            // Get the job record
            $job = DB::table('encoding_jobs')->where('id', $jobId)->first();
            if (!$job) {
                throw new \Exception("Job {$jobId} not found");
            }

            // Ensure output directory exists
            if (!is_dir($job->output_dir)) {
                mkdir($job->output_dir, 0755, true);
            }

            // Build ffmpeg command with overlay
            $inputFile = escapeshellarg($job->input_file);
            $outputFile = escapeshellarg($job->output_dir . '/stream.ts');

            // Start with basic ffmpeg command
            $cmd = "ffmpeg -i {$inputFile}";

            // Add video codec settings
            $vcodec = $settings['vcodec'] ?? 'h264';
            $vbitrate = (int)($settings['vbitrate'] ?? 1500);
            $preset = $settings['preset'] ?? 'medium';
            $fps = $settings['fps'] ?? 'original';

            $cmd .= " -c:v libx264";
            if ($preset && $preset !== 'disabled') {
                $cmd .= " -preset {$preset}";
            }

            // CRF or bitrate
            if ($settings['crf_mode'] === 'enabled' && isset($settings['crf'])) {
                $cmd .= " -crf {$settings['crf']}";
            } else {
                $cmd .= " -b:v {$vbitrate}k";
            }

            // FPS
            if ($fps && $fps !== 'original') {
                $cmd .= " -r {$fps}";
            }

            // Audio
            $abitrate = (int)($settings['abitrate'] ?? 128);
            $cmd .= " -c:a aac -b:a {$abitrate}k";

            // Build filter complex for overlays
            $filterComplex = $this->buildFilterComplexForJob($settings);
            if ($filterComplex) {
                $cmd .= " -filter_complex \"{$filterComplex}\"";
            }

            // Output format (MPEGTS for TS)
            $cmd .= " -f mpegts {$outputFile}";
            $cmd .= " 2>&1 > " . escapeshellarg($job->output_dir . '/encoding.log');
            $cmd .= " &"; // Run in background

            // Update job status to "running"
            DB::table('encoding_jobs')->where('id', $jobId)->update([
                'status' => 'running',
                'updated_at' => now(),
            ]);

            // Execute encoding in background
            shell_exec($cmd);

            \Log::info("Encoding job {$jobId} started: {$cmd}");
        } catch (\Exception $e) {
            // Update job status to failed
            DB::table('encoding_jobs')->where('id', $jobId)->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'updated_at' => now(),
            ]);

            \Log::error("Encoding job {$jobId} failed to start: " . $e->getMessage());
        }
    }

    /**
     * Build filter_complex string for overlay (logo + text + timer)
     */
    private function buildFilterComplexForJob(array $settings): string
    {
        $filters = [];

        // Logo overlay
        if ($settings['logo']['enabled'] ?? false) {
            $logo_path = storage_path('app/uploads/logo.png'); // Assume logo exists
            if (file_exists($logo_path)) {
                $logo_path = escapeshellarg($logo_path);
                $x = (int)($settings['logo']['x'] ?? 20);
                $y = (int)($settings['logo']['y'] ?? 20);
                $w = (int)($settings['logo']['w'] ?? 180);
                $h = (int)($settings['logo']['h'] ?? 56);
                $op = (float)($settings['logo']['opacity'] ?? 0.8);

                // Scale logo to size and set opacity
                $filters[] = "[0:v][1:v]scale={$w}:{$h}[scaled];[scaled]format=rgba,colorchannelmixer=aa={$op}[logo];[0:v][logo]overlay={$x}:{$y}[v1]";
            }
        }

        // Text overlay
        if ($settings['text']['enabled'] ?? false) {
            $text = str_replace(["'", '"', '%'], ['', '', ''], $settings['text']['value'] ?? '');
            if ($text) {
                $font = $settings['text']['font'] ?? 'Ubuntu';
                $size = (int)($settings['text']['size'] ?? 15);
                $color = $settings['text']['color'] ?? 'white';
                $x = (int)($settings['text']['x'] ?? 30);
                $y = (int)($settings['text']['y'] ?? 30);

                // Text box background
                $boxText = $text;
                if ($settings['text']['box']['enabled'] ?? false) {
                    $boxColor = $settings['text']['box']['color'] ?? 'black';
                    $boxPad = (int)($settings['text']['box']['padding'] ?? 5);
                    $boxText = "{$text}";
                    // Note: drawtext box is complex, using simple approach
                }

                $filters[] = "drawtext=fontfile=/usr/share/fonts/truetype/ubuntu/Ubuntu-R.ttf:text='{$boxText}':fontsize={$size}:fontcolor={$color}:x={$x}:y={$y}";
            }
        }

        if (empty($filters)) {
            return '';
        }

        return implode(',', $filters);
    }

    /**
     * Show Create VOD Channel form (FOX 1:1 design)
     */
    public function createChannel()
    {
        return view('admin.vod_channels.create-vod-channel');
    }
}

