<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        // Get channels with pagination
        $query = LiveChannel::with(['playlistItems.video'])
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
            $avgBitrate = $videos->isNotEmpty() ? round($videos->avg('bitrate_kbps') ?? 0) : 0;

            $hours = intdiv($totalDuration, 3600);
            $minutes = intdiv($totalDuration % 3600, 60);
            $seconds = $totalDuration % 60;

            $daysActive = max(1, now()->diffInDays($channel->updated_at));

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
                'bitrate' => $avgBitrate . 'k',
                'uptime' => $daysActive . 'd ' . ($hours > 0 ? $hours . 'h ' : '') . $minutes . 'm ' . $seconds . 's',
                'statusOk' => $channel->enabled,
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
        $channels = LiveChannel::with(['playlistItems.video'])
            ->orderBy('id', 'desc')
            ->paginate($perPage);

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
        $data['created_by']      = auth()->id();

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

    public function playlist(LiveChannel $channel)
    {
        $playlistItems = PlaylistItem::where('vod_channel_id', $channel->id)
            ->with('video')
            ->orderBy('sort_order')
            ->get();

        $videos = Video::orderBy('title')->get();

        return view('admin.vod_channels.playlist', [
            'channel'       => $channel,
            'playlistItems' => $playlistItems,
            'videos'        => $videos,
        ]);
    }

    public function addToPlaylist(Request $request, LiveChannel $channel)
    {
        $data = $request->validate([
            'video_id' => ['required', 'exists:videos,id'],
        ]);

        $exists = PlaylistItem::where('vod_channel_id', $channel->id)
            ->where('video_id', $data['video_id'])
            ->exists();

        if ($exists) {
            return redirect()
                ->route('vod-channels.playlist', $channel)
                ->with('error', 'Video already in playlist for this channel.');
        }

        $maxOrder = PlaylistItem::where('vod_channel_id', $channel->id)->max('sort_order');

        PlaylistItem::create([
            'vod_channel_id' => $channel->id,
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
            ->where('vod_channel_id', $channel->id)
            ->max('sort_order');

        foreach ($ids as $i => $videoId) {
            // Avoid duplicates
            $exists = DB::table('playlist_items')
                ->where('vod_channel_id', $channel->id)
                ->where('video_id', (int)$videoId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('playlist_items')->insert([
                'vod_channel_id' => $channel->id,
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
        if ($item->vod_channel_id !== $channel->id) {
            abort(404);
        }

        $item->delete();

        return redirect()
            ->route('vod-channels.playlist', $channel)
            ->with('success', 'Item removed from playlist.');
    }

    public function moveUp(LiveChannel $channel, PlaylistItem $item)
    {
        if ($item->vod_channel_id !== $channel->id) {
            abort(404);
        }

        $previous = PlaylistItem::where('vod_channel_id', $channel->id)
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
        if ($item->vod_channel_id !== $channel->id) {
            abort(404);
        }

        $next = PlaylistItem::where('vod_channel_id', $channel->id)
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
                    ->where('vod_channel_id', $channel->id)
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

        // Enforce private storage scope
        if (!str_starts_with($path, 'private/logos/')) {
            abort(404);
        }

        $abs = storage_path('app/' . $path);

        if (!is_file($abs)) {
            abort(404);
        }

        return response()->file($abs);
    }

    public function settings(LiveChannel $channel)
    {
        $categories = VideoCategory::orderBy('name')->get();
        $profiles = EncodeProfile::orderBy('name')->get();
        $liveProfiles = EncodeProfile::where('container', 'mpegts')->orderBy('name')->get();
        $allVideos = Video::orderBy('title')->get();

        // Load category videos for preview (if category selected)
        $categoryVideos = collect();
        $categoryStats = [
            'total_videos' => 0,
            'total_duration' => 0,
            'avg_bitrate' => 0,
            'dominant_resolution' => 'N/A',
        ];

        if ($channel->video_category_id) {
            $categoryVideos = Video::where('video_category_id', $channel->video_category_id)
                ->orderBy('title')
                ->take(20)
                ->get();

            $categoryStats['total_videos'] = Video::where('video_category_id', $channel->video_category_id)->count();
            $categoryStats['total_duration'] = Video::where('video_category_id', $channel->video_category_id)->sum('duration_seconds');
        }

        return view('admin.vod_channels.settings_new', [
            'channel'           => $channel,
            'categories'        => $categories,
            'profiles'          => $profiles,
            'liveProfiles'      => $liveProfiles,
            'allVideos'         => $allVideos,
            'categoryVideos'    => $categoryVideos,
            'categoryStats'     => $categoryStats,
        ]);
    }

    public function updateSettings(Request $request, LiveChannel $channel)
    {
        $data = $request->validate([
            'video_category'                => ['nullable', 'integer', 'exists:video_categories,id'],
            'is_24_7_channel'               => ['nullable', 'boolean'],
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
            'overlay_text_font_family'      => ['nullable', 'string', 'in:Arial,Helvetica,Courier,Times'],
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
            'overlay_timer_mode'            => ['nullable', 'string', 'in:realtime,elapsed'],
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
        ]);

        // Handle logo upload
        if ($request->hasFile('overlay_logo_file')) {
            $file = $request->file('overlay_logo_file');
            $dir = 'private/logos/channels/' . $channel->id;
            $name = 'logo_' . date('Ymd_His') . '.' . $file->getClientOriginalExtension();
            $relative = \Illuminate\Support\Facades\Storage::disk('local')->putFileAs($dir, $file, $name);
            $data['overlay_logo_path'] = $relative;
        }

        $channel->update([
            'video_category'                => $data['video_category'] ?? null,
            'is_24_7_channel'               => $request->boolean('is_24_7_channel'),
            'description'                   => $data['description'] ?? null,
            
            'encode_profile_id'             => $data['encode_profile_id'] ?? null,
            'manual_override_encoding'      => $request->boolean('manual_override_encoding'),
            'manual_width'                  => $data['manual_width'] ?? null,
            'manual_height'                 => $data['manual_height'] ?? null,
            'manual_fps'                    => $data['manual_fps'] ?? null,
            'manual_codec'                  => $data['manual_codec'] ?? null,
            'manual_preset'                 => $data['manual_preset'] ?? null,
            'manual_bitrate'                => $data['manual_bitrate'] ?? null,
            'manual_audio_bitrate'          => $data['manual_audio_bitrate'] ?? null,
            'manual_audio_codec'            => $data['manual_audio_codec'] ?? null,

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
        ]);

        return redirect()
            ->route('vod-channels.settings', $channel)
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

            // Check if already running
            if ($engine->isRunning($channel->encoder_pid)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Channel is already running'
                ], 400);
            }

            // Check if there are encoded TS files
            $outputDir = storage_path("app/streams/{$channel->id}");
            $encodedFiles = glob("{$outputDir}/video_*.ts") ?? [];

            // Determine which command to use
            if (!empty($encodedFiles)) {
                // PLAY MODE: Use pre-encoded TS files
                $ffmpegCommand = $engine->generatePlayCommand(loop: false);  // Not looping for single start
                $mode = 'PLAY (from ' . count($encodedFiles) . ' encoded TS files)';
            } else {
                // FALLBACK MODE: Encode on-the-fly from original videos
                $ffmpegCommand = $engine->generateCommand(includeOverlay: true);
                $mode = 'DIRECT (real-time encode)';
            }

            // Start the channel
            $result = $engine->start($ffmpegCommand);
            
            if ($result['status'] === 'success') {
                $result['mode'] = $mode;
                $result['encoded_count'] = count($encodedFiles ?? []);
            }

            return response()->json($result);

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

            return response()->json($result);

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

            // Simple preview without overlay - just 10 seconds
            $cmd = [
                'ffmpeg',
                '-i', escapeshellarg($inputFile),
                '-t', '10',
                '-c:v', 'libx264',
                '-preset', 'ultrafast',
                '-crf', '23',
                '-c:a', 'aac',
                '-b:a', '128k',
                '-y',
                escapeshellarg($outputFile),
            ];

            exec(implode(' ', $cmd) . ' 2>&1', $output, $code);

            if ($code !== 0 || !file_exists($outputFile)) {
                return response()->json(['status' => 'error', 'message' => 'Failed to generate preview'], 500);
            }

            return response()->json([
                'status' => 'success',
                'preview_url' => '/storage/previews/' . $channel->id . '/' . basename($outputFile),
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get current stream output URLs and status
     */
    public function outputStreams(Request $request, LiveChannel $channel)
    {
        try {
            // Get the streaming URLs based on channel configuration
            $domain = config('app.streaming_domain', 'http://46.4.20.56:2082');
            
            // Check if channel is currently running
            $engine = new \App\Services\ChannelEngineService($channel);
            $isRunning = $engine->isRunning($channel->encoder_pid);
            
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

    /**
     * Start channel with 24/7 looping (concat demuxer)
     */
    public function startChannelWithLooping(Request $request, LiveChannel $channel)
    {
        try {
            $engine = new \App\Services\ChannelEngineService($channel);

            // Check if already running
            if ($engine->isRunning($channel->encoder_pid)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Channel already running',
                ], 409);
            }

            // Generate looping command (uses concat demuxer)
            $ffmpegCommand = $engine->generateLoopingCommand(includeOverlay: true);

            // Start the channel with looping
            $result = $engine->start($ffmpegCommand);

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Channel started with 24/7 looping',
                    'mode' => '24/7 LOOPING',
                    'pid' => $result['pid'],
                    'job_id' => $result['job_id'],
                ]);
            } else {
                return response()->json($result, 400);
            }

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
            $playlistItems = $channel->playlistItems()
                ->orderBy('sort_order')
                ->get();

            if ($playlistItems->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Channel has no videos in playlist'
                ], 400);
            }

            $outputDir = storage_path("app/streams/{$channel->id}");
            @mkdir($outputDir, 0755, true);

            $createdJobs = 0;
            
            foreach ($playlistItems as $item) {
                $video = $item->video;
                
                if (!file_exists($video->file_path)) {
                    continue;
                }

                $outputPath = "{$outputDir}/video_{$item->id}.ts";
                
                // Check if job already exists and is not done
                $existingJob = \App\Models\EncodingJob::where('channel_id', $channel->id)
                    ->where('playlist_item_id', $item->id)
                    ->first();
                
                // If job exists and is done, don't recreate it
                if ($existingJob && $existingJob->status === 'done') {
                    continue;
                }
                
                // Create or update job (but only reset if not already running/done)
                $job = \App\Models\EncodingJob::updateOrCreate(
                    [
                        'channel_id' => $channel->id,
                        'playlist_item_id' => $item->id,
                    ],
                    [
                        'live_channel_id' => $channel->id,
                        'video_id' => $video->id,
                        'input_path' => $video->file_path,
                        'output_path' => $outputPath,
                        'status' => 'queued',
                        'started_at' => null,
                        'completed_at' => null,
                        'progress' => 0,
                    ]
                );
                
                // Only process if not already done
                if ($job->status !== 'done') {
                    $createdJobs++;
                    
                    // Kick off encoding in background for this job
                    $this->startEncodingProcess($job, $channel);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Created {$createdJobs} encoding jobs",
                'total_jobs' => $createdJobs,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to start encoding: {$e->getMessage()}");
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
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
     * Get encoding job progress
     */
    public function getEncodingJobs(Request $request, LiveChannel $channel)
    {
        try {
            $jobs = \App\Models\EncodingJob::where('channel_id', $channel->id)
                ->with('playlistItem.video')
                ->orderBy('created_at', 'desc')
                ->get();

            $totalJobs = $jobs->count();
            $completedJobs = $jobs->where('status', 'done')->count();
            $runningJobs = $jobs->where('status', 'running')->count();

            return response()->json([
                'status' => 'success',
                'total_jobs' => $totalJobs,
                'completed_jobs' => $completedJobs,
                'running_jobs' => $runningJobs,
                'jobs' => $jobs->map(fn($job) => [
                    'id' => $job->id,
                    'video_title' => $job->playlistItem?->video?->name ?? 'Unknown',
                    'status' => $job->status,
                    'progress' => $job->progress ?? 0,
                    'output_path' => $job->output_path,
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

            // Clear existing playlist
            PlaylistItem::where('vod_channel_id', $channel->id)->delete();

            // Insert videos from category in order
            foreach ($videos as $index => $video) {
                PlaylistItem::create([
                    'vod_channel_id' => $channel->id,
                    'video_id' => $video->id,
                    'sort_order' => $index + 1,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => count($videos) . ' videos synced from category',
                'count' => count($videos),
            ]);
        } catch (\Exception $e) {
            \Log::error('Sync playlist failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
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

