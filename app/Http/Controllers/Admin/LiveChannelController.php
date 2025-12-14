<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Http\Request;
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

    public function logoPreview(LiveChannel $channel)
    {
        $path = $channel->logo_path;

        if (!$path) {
            abort(404);
        }

        // acceptă și path absolut (legacy)
        if (Str::startsWith($path, '/')) {
            if (!file_exists($path)) abort(404);
            return response()->file($path);
        }

        // path relativ la storage/app
        $abs = storage_path('app/' . ltrim($path, '/'));
        if (!file_exists($abs)) abort(404);

        return response()->file($abs);
    }

    public function settings(LiveChannel $channel)
    {
        $categories = VideoCategory::orderBy('name')->get();

        return view('admin.vod_channels.settings', [
            'channel'    => $channel,
            'categories' => $categories,
        ]);
    }

    public function updateSettings(Request $request, LiveChannel $channel)
    {
        $data = $request->validate([
            'video_category'      => ['nullable', 'integer', 'exists:video_categories,id'],
            'resolution'          => ['required', 'string', 'max:50'],
            'video_bitrate'       => ['required', 'integer', 'min:200', 'max:50000'],
            'audio_bitrate'       => ['required', 'integer', 'min:32', 'max:1024'],
            'fps'                 => ['required', 'integer', 'min:10', 'max:120'],
            'audio_codec'         => ['required', 'string', 'max:50'],

            'logo_path'           => ['nullable', 'string', 'max:1024'],
            'logo_upload'         => ['nullable', 'file', 'mimes:png', 'max:5120'],

            'overlay_title'       => ['nullable', 'boolean'],
            'overlay_timer'       => ['nullable', 'boolean'],

            'encoded_output_path' => ['nullable', 'string', 'max:1024'],
            'hls_output_path'     => ['nullable', 'string', 'max:1024'],
        ]);

        // Upload PNG -> storage/app/private/logos/vod_channels/{id}/...
        // în DB salvăm path RELATIV: private/logos/...
        if ($request->hasFile('logo_upload')) {
            $file = $request->file('logo_upload');

            $dir  = 'private/logos/vod_channels/' . $channel->id;
            $name = 'logo_' . date('Ymd_His') . '_' . uniqid() . '.png';

            $relative = Storage::disk('local')->putFileAs($dir, $file, $name);

            $data['logo_path'] = $relative; // RELATIV, nu absolut
        }

        $channel->update([
            'video_category'      => $data['video_category'] ?? null,
            'resolution'          => $data['resolution'],
            'video_bitrate'       => $data['video_bitrate'],
            'audio_bitrate'       => $data['audio_bitrate'],
            'fps'                 => $data['fps'],
            'audio_codec'         => $data['audio_codec'],

            'logo_path'           => $data['logo_path'] ?? null,

            'overlay_title'       => $request->boolean('overlay_title'),
            'overlay_timer'       => $request->boolean('overlay_timer'),

            'encoded_output_path' => $data['encoded_output_path'] ?? null,
            'hls_output_path'     => $data['hls_output_path'] ?? null,
        ]);

        return redirect()
            ->route('vod-channels.settings', $channel)
            ->with('success', 'Settings saved.');
    }
}
