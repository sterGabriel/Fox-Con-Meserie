<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EncodingJob;
use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use App\Models\Video;
use App\Services\EncodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EncodingJobApiController extends Controller
{
    protected function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    protected function streamsOutputPathFor(LiveChannel $channel, int $playlistItemId, string $ext = 'ts'): string
    {
        $dir = storage_path("app/streams/{$channel->id}");
        $this->ensureDir($dir);

        $safeExt = strtolower(trim($ext));
        if ($safeExt === '') $safeExt = 'ts';

        return $dir . '/video_' . (int) $playlistItemId . '.' . $safeExt;
    }

    protected function ensurePlaylistItem(LiveChannel $channel, int $videoId): PlaylistItem
    {
        $existing = PlaylistItem::query()
            ->where(function ($q) use ($channel) {
                $q->where('live_channel_id', $channel->id)
                  ->orWhere('vod_channel_id', $channel->id);
            })
            ->where('video_id', $videoId)
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $maxOrder = (int) DB::table('playlist_items')
            ->where(function ($q) use ($channel) {
                $q->where('live_channel_id', $channel->id)
                  ->orWhere('vod_channel_id', $channel->id);
            })
            ->max('sort_order');

        $item = new PlaylistItem();
        $item->live_channel_id = $channel->id;
        $item->vod_channel_id = $channel->id;
        $item->video_id = $videoId;
        $item->sort_order = $maxOrder > 0 ? ($maxOrder + 1) : 1;
        $item->save();

        return $item;
    }

    protected function publicPreviewOutputPathFor(LiveChannel $channel, int $jobId, int $videoId): string
    {
        $dir = storage_path("app/public/previews/{$channel->id}/job_{$jobId}_video_{$videoId}");
        $this->ensureDir($dir);
        return $dir . "/index.m3u8";
    }

    protected function outputUrlForPath(?string $outputPath): ?string
    {
        $p = trim((string) ($outputPath ?? ''));
        if ($p === '') return null;

        // Determine how /public/storage is linked.
        // Some installs link to storage/app (non-standard), others to storage/app/public (Laravel default).
        $link = public_path('storage');
        $target = null;
        if (is_link($link)) {
            $t = @readlink($link);
            if (is_string($t) && $t !== '') {
                $target = $t;
            }
        }

        // If public/storage points to storage/app, then URLs must include the "public/" prefix.
        $appRoot = rtrim(storage_path('app'), '/') . '/';
        if ($target && str_contains($target, '/storage/app')) {
            if (str_starts_with($p, $appRoot)) {
                $rel = ltrim(substr($p, strlen($appRoot)), '/');
                if ($rel === '') return null;
                return url('storage/' . $rel);
            }
        }

        // Standard Laravel case: public/storage -> storage/app/public
        $publicRoot = rtrim(storage_path('app/public'), '/') . '/';
        if (str_starts_with($p, $publicRoot)) {
            $rel = ltrim(substr($p, strlen($publicRoot)), '/');
            if ($rel === '') return null;
            return url('storage/' . $rel);
        }

        // Fallback: if file is under storage/app, try to map it anyway.
        if (str_starts_with($p, $appRoot)) {
            $rel = ltrim(substr($p, strlen($appRoot)), '/');
            if ($rel === '') return null;
            return url('storage/' . $rel);
        }

        return null;
    }

    /**
     * Create encoding job from video
     * POST /api/encoding-jobs
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'live_channel_id' => ['required', 'integer', 'min:1'],
            'video_id'        => ['required', 'integer', 'min:1'],
            'settings'        => ['required', 'array'],
        ]);

        $channel = LiveChannel::findOrFail($data['live_channel_id']);
        $video   = Video::findOrFail($data['video_id']);

        $settings = $request->input('settings', []);
        if (!is_array($settings)) $settings = [];

        // Production encodes are always TS (24/7 looping channels)
        $settings['output_container'] = 'ts';

        // Ensure this video is in the channel playlist so it shows up in /vod-channels/{id}/playlist.
        $playlistItem = $this->ensurePlaylistItem($channel, (int) $video->id);
        $outputPath = $this->streamsOutputPathFor($channel, (int) $playlistItem->id, 'ts');

        // Create job in "queued" status (picked up by encoding:monitor)
        $job = new EncodingJob();
        $job->channel_id = $channel->id;
        $job->live_channel_id = $channel->id;
        $job->video_id = $video->id;
        $job->playlist_item_id = (int) $playlistItem->id;
        $job->input_path = $video->file_path;
        $job->output_path = $outputPath;
        $job->status = 'queued';
        $job->settings = $settings; // auto-cast to JSON via model
        $job->progress = 0;
        $job->save();

        // Start immediately if channel is idle (keeps UX snappy; scheduler will handle the rest).
        $hasRunning = EncodingJob::query()
            ->where('live_channel_id', $channel->id)
            ->where('status', 'running')
            ->exists();

        if (!$hasRunning) {
            $job->update([
                'status' => 'running',
                'started_at' => now(),
                'pid' => null,
                'error_message' => null,
            ]);
            (new EncodingService($job, $channel))->startAsync();
        }

        return response()->json([
            'ok' => true,
            'job_id' => $job->id,
            'status' => $job->status,
        ], 201);
    }

    /**
     * Get encoding jobs for a channel
     * GET /api/encoding-jobs?live_channel_id=X
     */
    public function index(Request $request)
    {
        $request->validate([
            'live_channel_id' => ['required', 'integer', 'min:1'],
        ]);

        $channelId = (int) $request->live_channel_id;
        $hideDoneInPlaylist = (string) $request->query('hide_done_in_playlist', '0');
        $hideDoneInPlaylist = in_array(strtolower($hideDoneInPlaylist), ['1', 'true', 'yes', 'on'], true);

        $inPlaylistByVideoId = [];
        if ($hideDoneInPlaylist && $channelId > 0) {
            $ids = PlaylistItem::query()
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

            foreach ($ids as $vid) {
                $inPlaylistByVideoId[(int) $vid] = true;
            }
        }

        $jobs = EncodingJob::query()
            ->where('live_channel_id', $channelId)
            ->orderByDesc('id')
            ->limit(50)
            ->with(['video'])
            ->get([
                'id', 'video_id', 'status', 'progress', 'settings', 'output_path', 'created_at', 'started_at', 'finished_at'
            ]);

        $jobs = $jobs->map(function ($job) {
            $settings = is_array($job->settings) ? $job->settings : [];

            $parseProgressFile = function (?string $path): array {
                $out = [
                    'out_time_ms' => null,
                    'speed' => null,
                    'progress' => null,
                ];
                $p = trim((string) ($path ?? ''));
                if ($p === '' || !is_file($p)) return $out;

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

            $progressFile = $settings['_progress_file'] ?? null;
            $progressMeta = $parseProgressFile(is_string($progressFile) ? $progressFile : null);

            $durationSeconds = (int) ($settings['test_duration_seconds'] ?? 0);
            if ($durationSeconds <= 0) {
                $durationSeconds = (int) ($job->video?->duration_seconds ?? 0);
            }

            $statusOut = (string) ($job->status ?? 'pending');
            $pct = (int) ($job->progress ?? 0);
            if (is_int($progressMeta['out_time_ms']) && $durationSeconds > 0) {
                $pct = (int) floor(min(99, max(0, ($progressMeta['out_time_ms'] / ($durationSeconds * 1000000)) * 100)));
            }
            $outputExists = $job->output_path && is_file((string) $job->output_path);

            $isHlsOutput = false;
            $hlsEnded = false;
            if ($outputExists) {
                $outPath = (string) $job->output_path;
                $isHlsOutput = str_ends_with(strtolower($outPath), '.m3u8') || strtolower((string) ($settings['output_container'] ?? '')) === 'hls';
                if ($isHlsOutput) {
                    $contents = @file_get_contents($outPath);
                    if (is_string($contents) && str_contains($contents, '#EXT-X-ENDLIST')) {
                        $hlsEnded = true;
                    }
                }
            }

            // If ffmpeg progress is moving but status wasn't updated yet, show as running.
            if (in_array(strtolower($statusOut), ['queued', 'pending', 'test_running', 'processing'], true)
                && is_int($progressMeta['out_time_ms'])
                && $progressMeta['out_time_ms'] > 0) {
                $statusOut = 'running';
            }

            // If ffmpeg ended and output exists, show as done.
            if ((string) ($progressMeta['progress'] ?? '') === 'end' && $outputExists) {
                $statusOut = 'done';
                $pct = 100;
            }

            // HLS-specific: if playlist is finalized, treat as done even if progress=end wasn't captured.
            if ($hlsEnded) {
                $statusOut = 'done';
                $pct = 100;
            }

            if (strtolower((string) ($job->status ?? '')) === 'done') {
                $pct = 100;
                $statusOut = 'done';
            }

            $eta = null;
            if ((string) ($job->status ?? '') === 'running' && $durationSeconds > 0 && is_int($progressMeta['out_time_ms'])) {
                $outSeconds = (int) floor(max(0, $progressMeta['out_time_ms']) / 1000000);
                $remain = max(0, $durationSeconds - $outSeconds);
                $eta = gmdate('H:i:s', (int) $remain);
            }

            $codec = $settings['encoder'] ?? $settings['vcodec'] ?? 'libx264';
            $bitrateK = $settings['video_bitrate'] ?? $settings['vbitrate_kbps'] ?? 0;
            $bitrateK = is_numeric($bitrateK) ? (int) $bitrateK : 0;

            $textOverlay = 'N/A';
            $textEnabled = $settings['overlay_text_enabled'] ?? null;
            $textEnabled = filter_var($textEnabled, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($textEnabled === null) {
                $textEnabled = (bool) ($settings['text']['enabled'] ?? false);
            }

            if ($textEnabled) {
                $mode = (string) ($settings['overlay_text_content'] ?? '');
                if ($mode === 'title') {
                    $textOverlay = $job->video?->title ?? 'Unknown';
                } elseif ($mode === 'custom') {
                    $textOverlay = (string) ($settings['overlay_text_custom'] ?? '');
                    if (trim($textOverlay) === '') $textOverlay = 'custom';
                } elseif ($mode === 'channel_name') {
                    $textOverlay = 'channel_name';
                } else {
                    $textOverlay = $mode !== '' ? $mode : 'enabled';
                }
            }

            return [
                'id' => $job->id,
                'video_id' => $job->video_id,
                'video_title' => $job->video?->title ?? 'Unknown',
                'status' => $statusOut,
                'is_test' => (string) ($settings['job_type'] ?? '') === 'test',
                'progress' => $pct,
                'speed' => is_string($progressMeta['speed']) ? $progressMeta['speed'] : null,
                'out_time' => is_int($progressMeta['out_time_ms'])
                    ? gmdate('H:i:s', (int) floor(max(0, $progressMeta['out_time_ms']) / 1000000))
                    : null,
                'eta' => $eta,
                'codec' => $codec,
                'bitrate' => $bitrateK . ' kbps',
                'created_at' => $job->created_at->format('Y-m-d H:i:s'),
                'text_overlay' => $textOverlay,
                'output_url' => $outputExists ? $this->outputUrlForPath((string) $job->output_path) : null,
            ];
        });

        if ($hideDoneInPlaylist && !empty($inPlaylistByVideoId)) {
            $jobs = $jobs->filter(function ($j) use ($inPlaylistByVideoId) {
                $videoId = (int) ($j['video_id'] ?? 0);
                if ($videoId <= 0) return true;

                // Hide any jobs (TEST or PROD) for videos that are currently in the playlist.
                // The goal is: once a video is in the channel playlist, it should not keep showing as selectable/encodable here.
                return empty($inPlaylistByVideoId[$videoId]);
            })->values();
        }

        return response()->json($jobs)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Create multiple encoding jobs (bulk)
     * POST /api/encoding-jobs/bulk
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'live_channel_id' => ['required', 'integer', 'min:1'],
            'video_ids'       => ['required', 'array', 'min:1'],
            'video_ids.*'     => ['integer', 'min:1'],
            'settings'        => ['required', 'array'],
        ]);

        $settings = $request->input('settings', []);
        if (!is_array($settings)) $settings = [];

        // Production encodes are always TS (24/7 looping channels)
        $settings['output_container'] = 'ts';
        $ext = 'ts';

        $channel = LiveChannel::findOrFail($data['live_channel_id']);
        $created = [];

        $firstCreatedJob = null;

        foreach ($data['video_ids'] as $video_id) {
            $video = Video::find($video_id);
            if (!$video) continue;

            $playlistItem = $this->ensurePlaylistItem($channel, (int) $video->id);
            $outputPath = $this->streamsOutputPathFor($channel, (int) $playlistItem->id, $ext);

            // Do not duplicate: if output already exists, treat it as already encoded.
            if ($outputPath !== '' && is_file($outputPath)) {
                continue;
            }

            // Do not duplicate: if a job already exists for this playlist item (queued/running/done/etc),
            // skip creating another one. Allow re-queue only if all previous attempts failed.
            $alreadyExists = EncodingJob::query()
                ->where('live_channel_id', $channel->id)
                ->where('playlist_item_id', (int) $playlistItem->id)
                ->whereNotIn('status', ['failed'])
                ->exists();
            if ($alreadyExists) {
                continue;
            }

            $job = new EncodingJob();
            $job->channel_id = $channel->id;
            $job->live_channel_id = $channel->id;
            $job->video_id = $video->id;
            $job->playlist_item_id = (int) $playlistItem->id;
            $job->input_path = $video->file_path;
            $job->output_path = $outputPath;
            $job->status = 'queued';
            $job->settings = $settings;
            $job->progress = 0;
            $job->save();

            if ($firstCreatedJob === null) {
                $firstCreatedJob = $job;
            }

            $created[] = $job->id;
        }

        // Start immediately if idle.
        if ($firstCreatedJob) {
            $hasRunning = EncodingJob::query()
                ->where('live_channel_id', $channel->id)
                ->where('status', 'running')
                ->exists();

            if (!$hasRunning) {
                $firstCreatedJob->update([
                    'status' => 'running',
                    'started_at' => now(),
                    'pid' => null,
                    'error_message' => null,
                ]);
                (new EncodingService($firstCreatedJob, $channel))->startAsync();
            }
        }

        return response()->json([
            'ok' => true,
            'count' => count($created),
            'job_ids' => $created,
        ], 201);
    }

    /**
     * Create test job (limited duration)
     * POST /api/encoding-jobs/{job}/test
     */
    public function test(EncodingJob $job, Request $request)
    {
        $request->validate([
            'test_duration' => ['nullable', 'integer', 'min:5', 'max:60'],
            'test_start' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'settings' => ['nullable', 'array'],
        ]);

        $testDuration = (int) $request->input('test_duration', 60);
        if ($testDuration < 5) $testDuration = 5;
        if ($testDuration > 60) $testDuration = 60;

        $testStart = (int) $request->input('test_start', 0);
        if ($testStart < 0) $testStart = 0;

        $channelId = (int) ($job->live_channel_id ?? $job->channel_id ?? 0);
        if ($channelId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Missing channel id',
            ], 400);
        }

        $channel = LiveChannel::find($channelId);
        if (!$channel) {
            return response()->json([
                'ok' => false,
                'message' => 'Channel not found',
            ], 404);
        }

        $video = Video::find((int) $job->video_id);
        if (!$video) {
            return response()->json([
                'ok' => false,
                'message' => 'Video not found',
            ], 404);
        }

        $settingsOverride = $request->input('settings', null);
        if (!is_array($settingsOverride)) {
            $settingsOverride = null;
        }

        // Build a signature so we only reuse tests with the same effective settings.
        $baseSettingsForSignature = is_array($job->settings) ? $job->settings : [];
        if ($settingsOverride) {
            $baseSettingsForSignature = array_merge($baseSettingsForSignature, $settingsOverride);
        }
        $sigKeys = [
            'encoder', 'preset', 'video_bitrate', 'audio_bitrate', 'frame_rate', 'crf_mode', 'crf_value',
            'overlay_logo_enabled', 'overlay_logo_position', 'overlay_logo_x', 'overlay_logo_y', 'overlay_logo_width', 'overlay_logo_height', 'overlay_logo_opacity',
            'overlay_text_enabled', 'overlay_text_content', 'overlay_text_custom', 'overlay_text_font_family', 'overlay_text_font_size', 'overlay_text_color', 'overlay_text_position', 'overlay_text_x', 'overlay_text_y', 'overlay_text_opacity', 'overlay_text_bg_color', 'overlay_text_bg_opacity', 'overlay_text_padding',
            'overlay_timer_enabled', 'overlay_timer_mode', 'overlay_timer_format', 'overlay_timer_position', 'overlay_timer_x', 'overlay_timer_y', 'overlay_timer_font_size', 'overlay_timer_color',
            'test_duration_seconds', 'test_start_seconds', 'hls_time',
        ];
        $sigPayload = [];
        foreach ($sigKeys as $k) {
            if (array_key_exists($k, $baseSettingsForSignature)) {
                $sigPayload[$k] = $baseSettingsForSignature[$k];
            }
        }
        $testSignature = md5(json_encode($sigPayload));

        // If a finished HLS test already exists for this signature, reuse it for playback (do NOT re-encode).
        $finished = EncodingJob::query()
            ->where('live_channel_id', $channel->id)
            ->where('video_id', $video->id)
            ->where('settings->job_type', 'test')
            ->where('settings->test_signature', $testSignature)
            ->where(function ($q) {
                $q->where('settings->output_container', 'hls')
                  ->orWhere('output_path', 'like', '%.m3u8');
            })
            ->orderByDesc('id')
            ->first();

        if ($finished) {
            $s = is_array($finished->settings) ? $finished->settings : [];
            $isHls = strtolower((string) ($s['output_container'] ?? '')) === 'hls'
                || str_ends_with(strtolower((string) ($finished->output_path ?? '')), '.m3u8');
            $outPath = (string) ($finished->output_path ?? '');
            $outExists = $isHls && $outPath !== '' && is_file($outPath);

            if (strtolower((string) ($finished->status ?? '')) === 'done' && $outExists) {
                return response()->json([
                    'ok' => true,
                    'test_job_id' => $finished->id,
                    'status' => 'done',
                    'duration' => (int) (($s['test_duration_seconds'] ?? null) ?? $testDuration),
                    'output_url' => $this->outputUrlForPath($outPath),
                    'reused' => true,
                ], 200);
            }
        }

        // Prevent multiple test sessions for the same channel+video
        $existing = EncodingJob::query()
            ->where('live_channel_id', $channel->id)
            ->where('video_id', $video->id)
            ->where('settings->job_type', 'test')
            ->where('settings->test_signature', $testSignature)
            ->where(function ($q) {
                $q->where('settings->output_container', 'hls')
                  ->orWhere('output_path', 'like', '%.m3u8');
            })
            ->whereIn('status', ['queued', 'running'])
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return response()->json([
                'ok' => true,
                'test_job_id' => $existing->id,
                'status' => $existing->status,
                'duration' => (int) (($existing->settings['test_duration_seconds'] ?? null) ?? $testDuration),
                'output_url' => ($existing->output_path && is_file((string) $existing->output_path))
                    ? $this->outputUrlForPath((string) $existing->output_path)
                    : $this->outputUrlForPath((string) ($existing->output_path ?? '')),
                'reused' => true,
            ], 200);
        }

        $inputPath = (string) ($video->file_path ?? $job->input_path ?? '');
        if (trim($inputPath) === '' || !file_exists($inputPath)) {
            return response()->json([
                'ok' => false,
                'message' => 'Video file not found on disk',
            ], 400);
        }

        // Create test job as HLS (TS segments) for browser playback
        $testJob = new EncodingJob();
        $testJob->channel_id = $channel->id;
        $testJob->live_channel_id = $job->live_channel_id;
        $testJob->video_id = $job->video_id;
        $testJob->playlist_item_id = null;
        $testJob->input_path = $inputPath;
        // IMPORTANT: Tests must be responsive. We start them immediately even if a production encode
        // is already running on the same channel.
        $testJob->status = 'running';
        $testJob->started_at = now();

        $baseSettings = is_array($job->settings) ? $job->settings : [];
        if ($settingsOverride) {
            $baseSettings = array_merge($baseSettings, $settingsOverride);
        }

        $testJob->settings = array_merge($baseSettings, [
            'job_type' => 'test',
            'output_container' => 'hls',
            'test_duration_seconds' => $testDuration,
            'test_start_seconds' => $testStart,
            'hls_time' => 2,
            'test_signature' => $testSignature,
        ]);
        $testJob->progress = 0;
        $testJob->save();

        $outPath = $this->publicPreviewOutputPathFor($channel, (int) $testJob->id, (int) $video->id);
        if (is_file($outPath)) {
            @unlink($outPath);
        }
        $testJob->output_path = $outPath;
        $testJob->save();

        // Start immediately for responsive UX.
        (new EncodingService($testJob, $channel))->startAsync();

        return response()->json([
            'ok' => true,
            'test_job_id' => $testJob->id,
            'status' => $testJob->status,
            'duration' => $testDuration,
            'output_url' => $this->outputUrlForPath($outPath),
        ], 201);
    }

    /**
     * Create or reuse a test job directly from a video id.
     * POST /api/encoding-jobs/test-from-video
     */
    public function testFromVideo(Request $request)
    {
        $data = $request->validate([
            'live_channel_id' => ['required', 'integer', 'min:1'],
            'video_id' => ['required', 'integer', 'min:1'],
            'test_duration' => ['nullable', 'integer', 'min:5', 'max:60'],
            'test_start' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'settings' => ['nullable', 'array'],
        ]);

        $channel = LiveChannel::findOrFail((int) $data['live_channel_id']);
        $video = Video::findOrFail((int) $data['video_id']);

        $testDuration = (int) ($data['test_duration'] ?? 60);
        if ($testDuration < 5) $testDuration = 5;
        if ($testDuration > 60) $testDuration = 60;

        $testStart = (int) ($data['test_start'] ?? 0);
        if ($testStart < 0) $testStart = 0;

        $settingsOverride = $request->input('settings', null);
        if (!is_array($settingsOverride)) {
            $settingsOverride = [];
        }

        // Build a signature so we only reuse tests with the same effective settings.
        $sigKeys = [
            'encoder', 'preset', 'tune', 'video_bitrate', 'audio_bitrate', 'frame_rate', 'crf_mode', 'crf_value',
            'overlay_logo_enabled', 'overlay_logo_position', 'overlay_logo_x', 'overlay_logo_y', 'overlay_logo_width', 'overlay_logo_height', 'overlay_logo_opacity',
            'overlay_text_enabled', 'overlay_text_content', 'overlay_text_custom', 'overlay_text_font_family', 'overlay_text_font_size', 'overlay_text_color', 'overlay_text_position', 'overlay_text_x', 'overlay_text_y', 'overlay_text_opacity', 'overlay_text_bg_color', 'overlay_text_bg_opacity', 'overlay_text_padding',
            'overlay_timer_enabled', 'overlay_timer_mode', 'overlay_timer_format', 'overlay_timer_position', 'overlay_timer_x', 'overlay_timer_y', 'overlay_timer_font_size', 'overlay_timer_color',
            'subtitle_mode', 'subtitle_language', 'subtitle_auto', 'subtitle_path',
            'test_duration_seconds', 'test_start_seconds', 'hls_time',
        ];
        $sigPayload = [];
        foreach ($sigKeys as $k) {
            if (array_key_exists($k, $settingsOverride)) {
                $sigPayload[$k] = $settingsOverride[$k];
            }
        }
        // Include requested duration/start even if UI doesn't send the *_seconds keys.
        $sigPayload['test_duration_seconds'] = $testDuration;
        $sigPayload['test_start_seconds'] = $testStart;
        $sigPayload['output_container'] = 'hls';
        $testSignature = md5(json_encode($sigPayload));

        // Reuse finished test if present.
        $finished = EncodingJob::query()
            ->where('live_channel_id', $channel->id)
            ->where('video_id', $video->id)
            ->where('settings->job_type', 'test')
            ->where('settings->test_signature', $testSignature)
            ->where(function ($q) {
                $q->where('settings->output_container', 'hls')
                  ->orWhere('output_path', 'like', '%.m3u8');
            })
            ->orderByDesc('id')
            ->first();

        if ($finished) {
            $s = is_array($finished->settings) ? $finished->settings : [];
            $outPath = (string) ($finished->output_path ?? '');
            $isDone = strtolower((string) ($finished->status ?? '')) === 'done';
            $outExists = $outPath !== '' && is_file($outPath);
            if ($isDone && $outExists) {
                return response()->json([
                    'ok' => true,
                    'test_job_id' => $finished->id,
                    'status' => 'done',
                    'duration' => (int) (($s['test_duration_seconds'] ?? null) ?? $testDuration),
                    'output_url' => $this->outputUrlForPath($outPath),
                    'reused' => true,
                ], 200);
            }
        }

        // If a matching test is already queued/running, reuse it.
        $existing = EncodingJob::query()
            ->where('live_channel_id', $channel->id)
            ->where('video_id', $video->id)
            ->where('settings->job_type', 'test')
            ->where('settings->test_signature', $testSignature)
            ->where(function ($q) {
                $q->where('settings->output_container', 'hls')
                  ->orWhere('output_path', 'like', '%.m3u8');
            })
            ->whereIn('status', ['queued', 'running'])
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            $s = is_array($existing->settings) ? $existing->settings : [];
            $outPath = (string) ($existing->output_path ?? '');
            return response()->json([
                'ok' => true,
                'test_job_id' => $existing->id,
                'status' => $existing->status,
                'duration' => (int) (($s['test_duration_seconds'] ?? null) ?? $testDuration),
                'output_url' => ($outPath !== '' && is_file($outPath))
                    ? $this->outputUrlForPath($outPath)
                    : $this->outputUrlForPath($outPath),
                'reused' => true,
            ], 200);
        }

        $inputPath = (string) ($video->file_path ?? '');
        if (trim($inputPath) === '' || !file_exists($inputPath)) {
            return response()->json([
                'ok' => false,
                'message' => 'Video file not found on disk',
            ], 400);
        }

        $testJob = new EncodingJob();
        $testJob->channel_id = $channel->id;
        $testJob->live_channel_id = $channel->id;
        $testJob->video_id = $video->id;
        $testJob->playlist_item_id = null;
        $testJob->input_path = $inputPath;
        $testJob->status = 'running';
        $testJob->started_at = now();

        $baseSettings = $settingsOverride;
        // Ensure test is browser-playable.
        $testJob->settings = array_merge($baseSettings, [
            'job_type' => 'test',
            'output_container' => 'hls',
            'test_duration_seconds' => $testDuration,
            'test_start_seconds' => $testStart,
            'hls_time' => 2,
            'test_signature' => $testSignature,
        ]);
        $testJob->progress = 0;
        $testJob->save();

        $outPath = $this->publicPreviewOutputPathFor($channel, (int) $testJob->id, (int) $video->id);
        if (is_file($outPath)) {
            @unlink($outPath);
        }
        $testJob->output_path = $outPath;
        $testJob->save();

        // Start immediately for responsive UX.
        (new EncodingService($testJob, $channel))->startAsync();

        return response()->json([
            'ok' => true,
            'test_job_id' => $testJob->id,
            'status' => $testJob->status,
            'duration' => $testDuration,
            'output_url' => $this->outputUrlForPath($outPath),
        ], 201);
    }

    /**
     * Delete encoding job
     * DELETE /api/encoding-jobs/{job}
     */
    public function destroy(EncodingJob $job)
    {
        $settings = is_array($job->settings) ? $job->settings : [];
        $isTest = (string) ($settings['job_type'] ?? '') === 'test';

        $jobsToDelete = collect([$job]);

        // If the user deletes the PRODUCTION job, also delete any TEST jobs for the same video.
        if (!$isTest && $job->live_channel_id && $job->video_id) {
            $testJobs = EncodingJob::query()
                ->where('live_channel_id', $job->live_channel_id)
                ->where('video_id', $job->video_id)
                ->where('settings->job_type', 'test')
                ->get();
            $jobsToDelete = $jobsToDelete->merge($testJobs);
        }

        $jobsToDelete = $jobsToDelete->unique('id');

        foreach ($jobsToDelete as $j) {
            try {
                $this->cancelIfRunning($j);
                $this->deleteJobArtifacts($j);
            } catch (\Throwable $e) {
                Log::warning('Failed to fully delete encoding job artifacts', [
                    'job_id' => $j->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $j->delete();
            } catch (\Throwable $e) {
                Log::warning('Failed to delete encoding job row', [
                    'job_id' => $j->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['ok' => true, 'message' => 'Job(s) deleted']);
    }

    protected function cancelIfRunning(EncodingJob $job): void
    {
        $status = strtolower((string) ($job->status ?? ''));
        $pid = (int) ($job->pid ?? 0);
        if ($pid <= 0) return;

        if (!in_array($status, ['running', 'queued', 'processing', 'test_running', 'pending'], true)) {
            return;
        }

        // Best-effort: stop ffmpeg.
        // PID is stored from `nohup ... & echo $!` in EncodingService.
        @exec('kill -TERM ' . (int) $pid . ' 2>/dev/null');
        usleep(250000);
        @exec('kill -KILL ' . (int) $pid . ' 2>/dev/null');

        try {
            $job->update([
                'status' => 'canceled',
                'finished_at' => now(),
                'error_message' => 'Canceled by user delete',
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    protected function deleteJobArtifacts(EncodingJob $job): void
    {
        $settings = is_array($job->settings) ? $job->settings : [];

        // Delete output (TS file or HLS directory)
        $outPath = (string) ($job->output_path ?? '');
        if ($outPath !== '') {
            $this->deleteOutputPath($outPath, $settings);
        }

        // Delete ffmpeg progress file
        $progressFile = $settings['_progress_file'] ?? null;
        if (is_string($progressFile) && $progressFile !== '') {
            $this->deleteFileIfSafe($progressFile);
        }

        // Delete log file
        $logPath = (string) ($job->log_path ?? '');
        if ($logPath !== '') {
            $this->deleteFileIfSafe($logPath);
        }
    }

    protected function deleteOutputPath(string $outPath, array $settings): void
    {
        $p = trim($outPath);
        if ($p === '') return;

        $container = strtolower((string) ($settings['output_container'] ?? ''));
        $isHls = $container === 'hls' || str_ends_with(strtolower($p), '.m3u8');

        if ($isHls) {
            $dir = dirname($p);
            $this->deleteDirIfSafe($dir);
            return;
        }

        $this->deleteFileIfSafe($p);
    }

    protected function deleteFileIfSafe(string $path): void
    {
        $p = trim($path);
        if ($p === '') return;

        // Support both absolute paths and storage-relative paths.
        if (!str_starts_with($p, '/')) {
            $p = storage_path(ltrim($p, '/'));
        }

        if (!$this->isSafeStoragePath($p)) return;
        if (!is_file($p)) return;
        @unlink($p);
    }

    protected function deleteDirIfSafe(string $dir): void
    {
        $d = trim($dir);
        if ($d === '') return;
        if (!str_starts_with($d, '/')) {
            $d = storage_path(ltrim($d, '/'));
        }
        if (!$this->isSafeStoragePath($d)) return;
        if (!is_dir($d)) return;

        $this->rmTree($d);
    }

    protected function rmTree(string $dir): void
    {
        $items = @scandir($dir);
        if (!is_array($items)) return;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->rmTree($path);
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    protected function isSafeStoragePath(string $path): bool
    {
        $p = str_replace('\\', '/', $path);
        $storageRoot = str_replace('\\', '/', rtrim(storage_path(), '/')) . '/';

        // realpath may fail if file was already deleted; fall back to string prefix checks.
        $rp = @realpath($p);
        if (is_string($rp) && $rp !== '') {
            $rp = str_replace('\\', '/', $rp);
            return str_starts_with($rp . (is_dir($rp) ? '/' : ''), $storageRoot);
        }

        return str_starts_with($p, $storageRoot);
    }
}
