<?php

namespace App\Http\Controllers\Admin;

use App\Models\EncodeProfile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EncodeProfileController extends Controller
{
    /**
     * Display a listing of profiles
     */
    public function index()
    {
        $profiles = EncodeProfile::orderBy('name')->paginate(12);
        return view('admin.encode_profiles.index', compact('profiles'));
    }

    /**
     * Show the form for creating a new profile
     */
    public function create()
    {
        return view('admin.encode_profiles.create');
    }

    /**
     * Store a newly created profile
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:encode_profiles,name',
            'width' => 'required|integer|min:480|max:3840',
            'height' => 'required|integer|min:270|max:2160',
            'fps' => 'required|integer|min:15|max:60',
            'video_codec' => 'required|in:libx264,libx265',
            'video_bitrate_k' => 'required|integer|min:500|max:10000',
            'preset' => 'required|in:ultrafast,superfast,veryfast,faster,fast,medium,slow,slower,veryslow',
            'gop' => 'required|integer|min:25|max:300',
            'maxrate_k' => 'required|integer|min:500|max:10000',
            'bufsize_k' => 'required|integer|min:500|max:20000',
            'audio_codec' => 'required|in:aac,libmp3lame,libopus',
            'audio_bitrate_k' => 'required|integer|min:64|max:320',
            'audio_channels' => 'required|integer|in:1,2',
            'audio_sample_rate' => 'required|integer|in:22050,44100,48000',
            'mode' => 'required|in:LIVE,VOD',
        ]);

        EncodeProfile::create([
            'name' => $validated['name'],
            'type' => 'custom',
            'width' => $validated['width'],
            'height' => $validated['height'],
            'fps' => $validated['fps'],
            'fps_mode' => 'CFR',
            'video_bitrate_k' => $validated['video_bitrate_k'],
            'preset' => $validated['preset'],
            'gop' => $validated['gop'],
            'maxrate_k' => $validated['maxrate_k'],
            'bufsize_k' => $validated['bufsize_k'],
            'audio_codec' => $validated['audio_codec'],
            'audio_bitrate_k' => $validated['audio_bitrate_k'],
            'audio_channels' => $validated['audio_channels'],
            'container' => $validated['mode'] === 'LIVE' ? 'mpegts' : 'mp4',
            'pix_fmt' => 'yuv420p',
            'is_system' => false,
            'extra_ffmpeg' => $validated['mode'] === 'LIVE' 
                ? '-pcr_period 0.02 -pat_period 0.1 -pmt_period 0.1 -mpegts_flags +resend_headers'
                : '',
        ]);

        return redirect()->route('encode-profiles.index')
            ->with('success', 'Profile created successfully!');
    }

    /**
     * Show the form for editing a profile
     */
    public function edit(EncodeProfile $profile)
    {
        return view('admin.encode_profiles.edit', compact('profile'));
    }

    /**
     * Update the profile
     */
    public function update(Request $request, EncodeProfile $profile)
    {
        if ($profile->is_system) {
            return back()->with('error', 'Cannot modify system profiles!');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:encode_profiles,name,' . $profile->id,
            'width' => 'required|integer|min:480|max:3840',
            'height' => 'required|integer|min:270|max:2160',
            'fps' => 'required|integer|min:15|max:60',
            'video_codec' => 'required|in:libx264,libx265',
            'video_bitrate_k' => 'required|integer|min:500|max:10000',
            'preset' => 'required|in:ultrafast,superfast,veryfast,faster,fast,medium,slow,slower,veryslow',
            'gop' => 'required|integer|min:25|max:300',
            'maxrate_k' => 'required|integer|min:500|max:10000',
            'bufsize_k' => 'required|integer|min:500|max:20000',
            'audio_codec' => 'required|in:aac,libmp3lame,libopus',
            'audio_bitrate_k' => 'required|integer|min:64|max:320',
            'audio_channels' => 'required|integer|in:1,2',
            'audio_sample_rate' => 'required|integer|in:22050,44100,48000',
            'mode' => 'required|in:LIVE,VOD',
        ]);

        $profile->update([
            'name' => $validated['name'],
            'width' => $validated['width'],
            'height' => $validated['height'],
            'fps' => $validated['fps'],
            'video_bitrate_k' => $validated['video_bitrate_k'],
            'preset' => $validated['preset'],
            'gop' => $validated['gop'],
            'maxrate_k' => $validated['maxrate_k'],
            'bufsize_k' => $validated['bufsize_k'],
            'audio_codec' => $validated['audio_codec'],
            'audio_bitrate_k' => $validated['audio_bitrate_k'],
            'audio_channels' => $validated['audio_channels'],
            'container' => $validated['mode'] === 'LIVE' ? 'mpegts' : 'mp4',
            'extra_ffmpeg' => $validated['mode'] === 'LIVE' 
                ? '-pcr_period 0.02 -pat_period 0.1 -pmt_period 0.1 -mpegts_flags +resend_headers'
                : '',
        ]);

        return redirect()->route('encode-profiles.index')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Duplicate a profile
     */
    public function duplicate(EncodeProfile $profile)
    {
        $newName = $profile->name . ' (Copy)';
        $counter = 1;
        while (EncodeProfile::where('name', $newName)->exists()) {
            $counter++;
            $newName = $profile->name . ' (Copy ' . $counter . ')';
        }

        $copy = $profile->replicate();
        $copy->name = $newName;
        $copy->is_system = false;
        $copy->save();

        return redirect()->route('encode-profiles.index')
            ->with('success', 'Profile duplicated successfully!');
    }

    /**
     * Delete a profile
     */
    public function destroy(EncodeProfile $profile)
    {
        if ($profile->is_system) {
            return back()->with('error', 'Cannot delete system profiles!');
        }

        if ($profile->channels()->count() > 0) {
            return back()->with('error', 'Cannot delete profile in use by channels!');
        }

        $profile->delete();
        return redirect()->route('encode-profiles.index')
            ->with('success', 'Profile deleted successfully!');
    }
}
