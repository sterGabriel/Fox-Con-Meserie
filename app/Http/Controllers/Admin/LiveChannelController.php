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
        $liveProfiles = EncodeProfile::where('mode', 'live')->orderBy('name')->get();

        return view('admin.vod_channels.settings', [
            'channel'      => $channel,
            'categories'   => $categories,
            'liveProfiles' => $liveProfiles,
        ]);
    }

    public function updateSettings(Request $request, LiveChannel $channel)
    {
        $data = $request->validate([
            'video_category'        => ['nullable', 'integer', 'exists:video_categories,id'],
            'resolution'            => ['required', 'string', 'max:50'],
            'video_bitrate'         => ['required', 'integer', 'min:200', 'max:50000'],
            'audio_bitrate'         => ['required', 'integer', 'min:32', 'max:1024'],
            'fps'                   => ['required', 'integer', 'min:10', 'max:120'],
            'audio_codec'           => ['required', 'string', 'max:50'],

            'encode_profile_id'     => ['nullable', 'integer', 'exists:encode_profiles,id'],
            'manual_encode_enabled' => ['nullable', 'boolean'],
            'manual_bitrate'        => ['nullable', 'integer', 'min:500', 'max:20000'],
            'manual_preset'         => ['nullable', 'string', 'max:50'],

            'logo_upload'           => ['nullable', 'file', 'mimes:png', 'max:5120'],

            'overlay_title'         => ['nullable', 'boolean'],
            'overlay_timer'         => ['nullable', 'boolean'],

            'encoded_output_path'   => ['nullable', 'string', 'max:1024'],
            'hls_output_path'       => ['nullable', 'string', 'max:1024'],
        ]);

        // Upload PNG -> storage/app/private/logos/vod_channels/{id}/...
        // în DB salvăm path RELATIV: logos/... (relativ la disk root care e storage/app/private)
        \Log::info('UpdateSettings: hasFile check', [
            'hasFile' => $request->hasFile('logo_upload'),
            'allFiles' => array_keys($request->allFiles()),
            'hasLogoUpload' => isset($_FILES['logo_upload']) ?? false,
        ]);
        
        if ($request->hasFile('logo_upload')) {
            $file = $request->file('logo_upload');

            $dir  = 'private/logos/vod_channels/' . $channel->id;
            $name = 'logo_' . date('Ymd_His') . '_' . uniqid() . '.png';

            // Store under storage/app/private/logos/...
            // Save the relative path (including "private/") into DB
            $relative = Storage::disk('local')->putFileAs($dir, $file, $name);

            $data['logo_path'] = $relative;
        }

        $channel->update([
            'video_category'        => $data['video_category'] ?? null,
            'resolution'            => $data['resolution'],
            'video_bitrate'         => $data['video_bitrate'],
            'audio_bitrate'         => $data['audio_bitrate'],
            'fps'                   => $data['fps'],
            'audio_codec'           => $data['audio_codec'],

            'encode_profile_id'     => $data['encode_profile_id'] ?? null,
            'manual_encode_enabled' => $request->boolean('manual_encode_enabled'),

            'logo_path'             => $data['logo_path'] ?? $channel->logo_path,

            'overlay_title'         => $request->boolean('overlay_title'),
            'overlay_timer'         => $request->boolean('overlay_timer'),

            'encoded_output_path' => $data['encoded_output_path'] ?? null,
            'hls_output_path'     => $data['hls_output_path'] ?? null,
        ]);

        return redirect()
            ->route('vod-channels.settings', $channel)
            ->with('success', 'Settings saved.');
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
}
