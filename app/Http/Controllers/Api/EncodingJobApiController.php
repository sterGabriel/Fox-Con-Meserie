<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EncodingJob;
use App\Models\LiveChannel;
use App\Models\Video;
use Illuminate\Http\Request;

class EncodingJobApiController extends Controller
{
    /**
     * Create encoding job from video
     * POST /api/encoding-jobs
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'live_channel_id' => ['required', 'integer', 'min:1'],
            'video_id'        => ['required', 'integer', 'min:1'],

            // settings (minimal structure)
            'settings'                  => ['required', 'array'],
            'settings.vcodec'           => ['nullable', 'string'],
            'settings.preset'           => ['nullable', 'string'],
            'settings.crf'              => ['nullable', 'integer'],
            'settings.vbitrate_kbps'    => ['nullable', 'integer'],
            'settings.abitrate_kbps'    => ['nullable', 'integer'],
            'settings.fps'              => ['nullable'],
            'settings.logo'             => ['nullable', 'array'],
            'settings.text'             => ['nullable', 'array'],
        ]);

        $channel = LiveChannel::findOrFail($data['live_channel_id']);
        $video   = Video::findOrFail($data['video_id']);

        // Create job in "pending" status
        $job = new EncodingJob();
        $job->live_channel_id = $channel->id;
        $job->video_id = $video->id;
        $job->input_path = $video->file_path;
        $job->status = 'pending';
        $job->settings = $data['settings']; // auto-cast to JSON via model
        $job->progress = 0;
        $job->output_path = null; // Set by worker later
        $job->save();

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

        $jobs = EncodingJob::query()
            ->where('live_channel_id', (int)$request->live_channel_id)
            ->orderByDesc('id')
            ->limit(50)
            ->with(['video'])
            ->get([
                'id', 'video_id', 'status', 'progress', 'settings', 'created_at', 'started_at', 'finished_at'
            ]);

        $jobs = $jobs->map(function ($job) {
            return [
                'id' => $job->id,
                'video_id' => $job->video_id,
                'video_title' => $job->video?->title ?? 'Unknown',
                'status' => $job->status,
                'progress' => $job->progress ?? 0,
                'codec' => $job->settings['vcodec'] ?? 'h264',
                'bitrate' => ($job->settings['vbitrate_kbps'] ?? 0) . ' kbps',
                'created_at' => $job->created_at->format('Y-m-d H:i:s'),
                'text_overlay' => $job->settings['text']['value'] ?? 'N/A',
            ];
        });

        return response()->json($jobs);
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

        $channel = LiveChannel::findOrFail($data['live_channel_id']);
        $created = [];

        foreach ($data['video_ids'] as $video_id) {
            $video = Video::find($video_id);
            if (!$video) continue;

            $job = new EncodingJob();
            $job->live_channel_id = $channel->id;
            $job->video_id = $video->id;
            $job->input_path = $video->file_path;
            $job->status = 'pending';
            $job->settings = $data['settings'];
            $job->progress = 0;
            $job->save();

            $created[] = $job->id;
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
            'test_duration' => ['nullable', 'integer', 'min:5', 'max:300'], // 5-300 sec
        ]);

        $testDuration = $request->input('test_duration', 60); // default 60 sec

        // Creez job test pe video-ul din job-ul original
        $testJob = new EncodingJob();
        $testJob->live_channel_id = $job->live_channel_id;
        $testJob->video_id = $job->video_id;
        $testJob->input_path = $job->input_path;
        $testJob->status = 'test_running';
        $testJob->settings = array_merge(
            $job->settings,
            ['test_duration_seconds' => $testDuration]
        );
        $testJob->progress = 0;
        $testJob->save();

        return response()->json([
            'ok' => true,
            'test_job_id' => $testJob->id,
            'status' => 'test_running',
            'duration' => $testDuration,
        ], 201);
    }

    /**
     * Delete encoding job
     * DELETE /api/encoding-jobs/{job}
     */
    public function destroy(EncodingJob $job)
    {
        $job->delete();
        return response()->json(['ok' => true, 'message' => 'Job deleted']);
    }
}
