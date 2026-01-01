<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LiveChannelApiController extends Controller
{
    /**
     * Save channel default settings
     * POST /api/live-channels/{channel}/settings
     */
    public function saveSettings(LiveChannel $channel, Request $request)
    {
        $data = $request->validate([
            // Basic output defaults
            'video_bitrate' => ['nullable', 'integer', 'min:300', 'max:20000'],
            'audio_bitrate' => ['nullable', 'integer', 'min:32', 'max:512'],
            'fps' => ['nullable'],
            'resolution' => ['nullable', 'string'],

            // Overlay (canonical live_channels columns)
            'overlay_safe_margin' => ['nullable', 'integer', 'min:0', 'max:200'],

            'overlay_logo_enabled' => ['nullable', 'boolean'],
            'overlay_logo_path' => ['nullable', 'string'],
            'overlay_logo_position' => ['nullable', 'string'],
            'overlay_logo_x' => ['nullable', 'integer', 'min:0'],
            'overlay_logo_y' => ['nullable', 'integer', 'min:0'],
            'overlay_logo_width' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'overlay_logo_height' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'overlay_logo_opacity' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'overlay_text_enabled' => ['nullable', 'boolean'],
            'overlay_text_content' => ['nullable', 'string'],
            'overlay_text_custom' => ['nullable', 'string'],
            'overlay_text_font_family' => ['nullable', 'string'],
            'overlay_text_font_size' => ['nullable', 'integer', 'min:8', 'max:200'],
            'overlay_text_color' => ['nullable', 'string'],
            'overlay_text_position' => ['nullable', 'string'],
            'overlay_text_x' => ['nullable', 'integer', 'min:0'],
            'overlay_text_y' => ['nullable', 'integer', 'min:0'],
            'overlay_text_opacity' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'overlay_text_bg_opacity' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'overlay_text_bg_color' => ['nullable', 'string'],
            'overlay_text_padding' => ['nullable', 'integer', 'min:0', 'max:200'],

            'overlay_timer_enabled' => ['nullable', 'boolean'],
            'overlay_timer_mode' => ['nullable', 'string'],
            'overlay_timer_format' => ['nullable', 'string'],
            'overlay_timer_position' => ['nullable', 'string'],
            'overlay_timer_x' => ['nullable', 'integer', 'min:0'],
            'overlay_timer_y' => ['nullable', 'integer', 'min:0'],
            'overlay_timer_font_size' => ['nullable', 'integer', 'min:8', 'max:200'],
            'overlay_timer_color' => ['nullable', 'string'],
            'overlay_timer_opacity' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        // Accept legacy keys from older UIs
        if ($request->has('overlay_logo_pos') && !$request->has('overlay_logo_position')) {
            $data['overlay_logo_position'] = $request->input('overlay_logo_pos');
        }
        if ($request->has('overlay_logo_w') && !$request->has('overlay_logo_width')) {
            $data['overlay_logo_width'] = $request->input('overlay_logo_w');
        }
        if ($request->has('overlay_logo_h') && !$request->has('overlay_logo_height')) {
            $data['overlay_logo_height'] = $request->input('overlay_logo_h');
        }

        if ($request->has('overlay_text_value') && !$request->has('overlay_text_custom')) {
            $data['overlay_text_custom'] = $request->input('overlay_text_value');
            if (!$request->has('overlay_text_content')) {
                $data['overlay_text_content'] = 'custom';
            }
        }
        if ($request->has('overlay_text_font') && !$request->has('overlay_text_font_family')) {
            $data['overlay_text_font_family'] = $request->input('overlay_text_font');
        }
        if ($request->has('overlay_text_size') && !$request->has('overlay_text_font_size')) {
            $data['overlay_text_font_size'] = $request->input('overlay_text_size');
        }
        if ($request->has('overlay_text_pos') && !$request->has('overlay_text_position')) {
            $data['overlay_text_position'] = $request->input('overlay_text_pos');
        }

        // Normalize fps (accept string or number)
        if (array_key_exists('fps', $data) && $data['fps'] !== null) {
            $fps = $data['fps'];
            if (is_string($fps)) {
                $fps = (int) $fps;
            }
            $data['fps'] = (int) $fps;
        }

        // Normalize opacity: accept 0..1 or 0..100, store as 0..100
        foreach (['overlay_logo_opacity', 'overlay_text_opacity', 'overlay_text_bg_opacity', 'overlay_timer_opacity'] as $k) {
            if (array_key_exists($k, $data) && $data[$k] !== null) {
                $v = $data[$k];
                $v = is_numeric($v) ? (float) $v : null;
                if ($v === null) continue;
                if ($v <= 1.0) {
                    $v = $v * 100.0;
                }
                if ($v < 0) $v = 0;
                if ($v > 100) $v = 100;
                $data[$k] = $v;
            }
        }

        // Uppercase corner positions where relevant
        foreach (['overlay_logo_position', 'overlay_text_position', 'overlay_timer_position'] as $k) {
            if (array_key_exists($k, $data) && is_string($data[$k])) {
                $p = strtoupper(trim($data[$k]));
                if ($p !== '') $data[$k] = $p;
            }
        }

        // Update channel fields
        $channel->fill(array_filter($data, fn($v) => $v !== null));

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

        // Create channel first (we need the ID to store the logo in a per-channel folder)
        $channel = LiveChannel::create([
            'name' => $data['channel_name'],
            'slug' => \Str::slug($data['channel_name']) . '-' . uniqid(),
            'logo_path' => null,
            'enabled' => true,
            'status' => 'idle',
            // auth()->id() returns username (string) in this project; DB expects numeric user id.
            'created_by' => auth()->user()?->id,
            'resolution' => $data['video_size'] === '720p' ? '1280x720' : '1920x1080',
            'video_bitrate' => 2500,
            'audio_bitrate' => 128,
            'fps' => 25,
            'audio_codec' => 'aac',
        ]);

        // Store logo if provided (canonical path used everywhere in the panel)
        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');

            // local disk root is storage/app/private in this project, so don't prefix with "private/"
            $dir = 'logos/channels/' . $channel->id;
            $name = 'channel_logo_' . date('Ymd_His') . '.' . $logoFile->getClientOriginalExtension();
            $relative = Storage::disk('local')->putFileAs($dir, $logoFile, $name);

            $channel->update([
                'logo_path' => $relative,
                // keep overlay logo in sync (single logo per channel)
                'overlay_logo_path' => $relative,
            ]);
        }

        return response()->json([
            'id' => $channel->id,
            'name' => $channel->name,
            'message' => 'Channel created successfully',
        ], 201);
    }
}
