<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use App\Models\Video;
use App\Models\VideoCategory;
use App\Models\EncodeProfile;
use App\Services\EncodingProfileBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LiveChannelController extends Controller
{
    public function index()
    {
        $channels = LiveChannel::orderBy('id', 'desc')->paginate(20);

        return view('admin.vod_channels.index', [
            'channels' => $channels,
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

        return view('admin.vod_channels.settings_new', [
            'channel'      => $channel,
            'categories'   => $categories,
            'profiles'     => $profiles,
            'liveProfiles' => $liveProfiles,
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
            if ($engine->isRunning()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Channel is already running'
                ], 400);
            }

            // Generate FFmpeg command
            $ffmpegCommand = $engine->generateCommand(includeOverlay: true);

            // Start the channel
            $result = $engine->start($ffmpegCommand);

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
            // Get first video from playlist
            $firstVideo = $channel->playlistItems()
                ->orderBy('sort_order')
                ->first();

            if (!$firstVideo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No videos in playlist'
                ], 400);
            }

            $video = $firstVideo->video;
            $inputFile = $video->file_path;

            if (!file_exists($inputFile)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Video file not found'
                ], 400);
            }

            // Create preview output path
            $previewDir = storage_path("app/previews/{$channel->id}");
            @mkdir($previewDir, 0755, true);
            $outputFile = "{$previewDir}/preview_" . time() . ".mp4";

            // Generate FFmpeg command for 10s preview with overlay
            $engine = new \App\Services\ChannelEngineService($channel);
            $filterComplex = $engine->buildFilterComplex(includeOverlay: true);

            // Build command: 10 seconds only, with overlay
            $cmd = [
                'ffmpeg',
                '-i', escapeshellarg($inputFile),
                '-t', '10',  // Only 10 seconds
            ];

            if (!empty($filterComplex)) {
                $cmd = array_merge($cmd, [
                    '-filter_complex', escapeshellarg($filterComplex),
                    '-map', '[out]',  // Use filter output
                ]);
            } else {
                $cmd = array_merge($cmd, ['-map', '0:v']);
            }

            // Add audio and encoding settings
            $cmd = array_merge($cmd, [
                '-c:a', 'aac',
                '-b:a', '128k',
                '-c:v', 'libx264',
                '-preset', 'medium',
                '-b:v', '1500k',
                '-y', // Overwrite output
                escapeshellarg($outputFile),
            ]);

            $shellCmd = implode(' ', $cmd);

            // Execute synchronously (not in background)
            $process = new \Symfony\Component\Process\Process(
                explode(' ', str_replace("'", '', $shellCmd))
            );
            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'FFmpeg error: ' . $process->getErrorOutput()
                ], 500);
            }

            if (!file_exists($outputFile)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Preview file not created'
                ], 500);
            }

            // Return preview URL
            return response()->json([
                'status' => 'success',
                'preview_url' => "/storage/previews/{$channel->id}/" . basename($outputFile),
                'preview_file' => basename($outputFile),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
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
            $domain = config('app.streaming_domain', 'http://46.4.20.56:2082');
            
            // Check if channel is currently running
            $engine = new \App\Services\ChannelEngineService($channel);
            $isRunning = $engine->isRunning($channel->encoder_pid);
            
            // Get output paths
            $tsUrl = "{$domain}/streams/{$channel->id}.ts";
            $hlsUrl = "{$domain}/streams/{$channel->id}/index.m3u8";
            
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
                
                // Create or update job
                $job = \App\Models\EncodingJob::updateOrCreate(
                    [
                        'channel_id' => $channel->id,
                        'playlist_item_id' => $item->id,
                    ],
                    [
                        'input_path' => $video->file_path,
                        'output_path' => $outputPath,
                        'status' => 'queued',
                        'started_at' => null,
                        'completed_at' => null,
                        'progress' => 0,
                    ]
                );
                
                $createdJobs++;

                // Kick off encoding in background for this job
                // Use PHP to execute encoding asynchronously
                $this->startEncodingProcess($job, $channel);
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
        // Create a simple PHP script to run encoding in background
        $scriptPath = storage_path("app/encode_job_{$job->id}.php");
        
        $script = <<<'PHP'
<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->boot();

$job = \App\Models\EncodingJob::find(JOBID);
if (!$job) exit(1);

$channel = \App\Models\LiveChannel::find(CHANNELID);
if (!$channel) exit(1);

$encoding = new \App\Services\EncodingService($job, $channel);
$result = $encoding->encode();
exit($result['status'] === 'success' ? 0 : 1);
PHP;

        $script = str_replace('JOBID', $job->id, $script);
        $script = str_replace('CHANNELID', $channel->id, $script);
        
        file_put_contents($scriptPath, $script);
        
        // Execute in background (nohup + disown)
        $logFile = storage_path("logs/encode_bg_{$job->id}.log");
        shell_exec("nohup php {$scriptPath} > {$logFile} 2>&1 &");
        
        // Clean up script after 10 seconds
        @unlink($scriptPath);
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
}



