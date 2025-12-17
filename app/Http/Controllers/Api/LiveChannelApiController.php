<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveChannel;
use Illuminate\Http\Request;

class LiveChannelApiController extends Controller
{
    /**
     * Save channel default settings
     * POST /api/live-channels/{channel}/settings
     */
    public function saveSettings(LiveChannel $channel, Request $request)
    {
        $data = $request->validate([
            'video_bitrate' => ['nullable', 'integer', 'min:500', 'max:10000'],
            'audio_bitrate' => ['nullable', 'integer', 'min:32', 'max:320'],
            'fps' => ['nullable', 'string'],
            'resolution' => ['nullable', 'string'],
            'overlay_logo_enabled' => ['nullable', 'boolean'],
            'overlay_logo_path' => ['nullable', 'string'],
            'overlay_logo_pos' => ['nullable', 'string'],
            'overlay_logo_x' => ['nullable', 'integer'],
            'overlay_logo_y' => ['nullable', 'integer'],
            'overlay_logo_w' => ['nullable', 'integer'],
            'overlay_logo_h' => ['nullable', 'integer'],
            'overlay_logo_opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'overlay_text_enabled' => ['nullable', 'boolean'],
            'overlay_text_value' => ['nullable', 'string'],
            'overlay_text_font' => ['nullable', 'string'],
            'overlay_text_size' => ['nullable', 'integer'],
            'overlay_text_color' => ['nullable', 'string'],
            'overlay_text_pos' => ['nullable', 'string'],
            'overlay_text_x' => ['nullable', 'integer'],
            'overlay_text_y' => ['nullable', 'integer'],
            'overlay_text_opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'overlay_timer_enabled' => ['nullable', 'boolean'],
            'overlay_timer_format' => ['nullable', 'string'],
        ]);

        // Update channel fields
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $channel->{$key} = $value;
            }
        }

        $channel->save();

        return response()->json([
            'ok' => true,
            'message' => 'Channel settings saved',
            'channel_id' => $channel->id,
        ]);
    }

    /**
     * Create new VOD Channel with logo upload
     * POST /api/live-channels
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'channel_name' => ['required', 'string', 'max:255'],
            'server' => ['required', 'string'],
            'channel_type' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'output' => ['nullable', 'string'],
            'video_size' => ['nullable', 'string'],
            'epg' => ['nullable', 'string'],
            'icon_url' => ['nullable', 'url'],
            'logo_type' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:5120'],
        ]);

        // Store logo if provided
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('channel-logos');
        }

        // Create channel with only existing database columns
        $channel = LiveChannel::create([
            'name' => $data['channel_name'],
            'slug' => \Str::slug($data['channel_name']) . '-' . uniqid(),
            'logo_path' => $logoPath,
            'enabled' => true,
            'status' => 'idle',
            'created_by' => auth()->id(),
            'resolution' => $data['video_size'] === '720p' ? '1280x720' : '1920x1080',
            'video_bitrate' => 2500,
            'audio_bitrate' => 128,
            'fps' => 25,
            'audio_codec' => 'aac',
        ]);

        return response()->json([
            'id' => $channel->id,
            'name' => $channel->name,
            'message' => 'Channel created successfully',
        ], 201);
    }
}
