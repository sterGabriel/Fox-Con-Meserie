<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EncodingJob;
use App\Models\LiveChannel;
use App\Models\EncodeProfile;
use App\Services\EncodingProfileBuilder;
use Illuminate\Http\Request;

class EncodingJobController extends Controller
{
    /**
     * Listă joburi de encodare.
     */
    public function index()
    {
        $jobs = EncodingJob::with(['channel', 'video'])
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.encoding_jobs.index', [
            'jobs' => $jobs,
        ]);
    }

    /**
     * Creează joburi de encodare pentru TOT playlist-ul unui canal.
     */
    public function queueChannel(LiveChannel $channel)
    {
        // luăm playlist-ul canalului
        $playlistItems = $channel->playlistItems()
            ->with('video')
            ->orderBy('sort_order')
            ->get();

        if ($playlistItems->isEmpty()) {
            return redirect()
                ->route('vod-channels.playlist', $channel)
                ->with('error', 'Playlist is empty. Nothing to encode.');
        }

        // Get encoding profile (use selected or default)
        $profile = null;
        if ($channel->encode_profile_id) {
            $profile = EncodeProfile::find($channel->encode_profile_id);
        }
        
        if (!$profile) {
            // Default to LIVE 720p profile
            $profile = EncodeProfile::where('mode', 'live')
                ->where('height', 720)
                ->first();
        }

        $builder = new EncodingProfileBuilder();
        $created = 0;

        foreach ($playlistItems as $item) {
            if (! $item->video) {
                continue;
            }

            // NU dublăm joburile pentru același canal + video
            $exists = EncodingJob::where('live_channel_id', $channel->id)
                ->where('video_id', $item->video_id)
                ->whereIn('status', ['pending', 'processing'])
                ->exists();

            if ($exists) {
                continue;
            }

            // Generate ffmpeg command for this job
            $inputPath = $item->video->file_path ?? '/path/to/video.mp4';
            $outputUrl = 'rtmp://localhost/live/' . $channel->slug;
            
            try {
                $ffmpegCommand = $profile ? $builder->buildCommand($channel, $inputPath, $outputUrl) : '';
            } catch (\Exception $e) {
                $ffmpegCommand = '';
            }

            EncodingJob::create([
                'live_channel_id' => $channel->id,
                'video_id'        => $item->video_id,
                'status'          => 'pending',
                'progress'        => 0,
                'error_message'   => null,
                'ffmpeg_command'  => $ffmpegCommand,
            ]);

            $created++;
        }

        if ($created === 0) {
            $msg = 'No new jobs were queued (maybe everything is already pending/processing).';
        } else {
            $msg = "Queued {$created} encoding job" . ($created > 1 ? 's' : '') . ' for this channel.';
        }

        return redirect()
            ->route('vod-channels.playlist', $channel)
            ->with('success', $msg);
    }
}
