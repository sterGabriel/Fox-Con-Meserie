<?php

namespace App\Console\Commands;

use App\Models\EncodingJob;
use Illuminate\Console\Command;

class RunEncodingJobs extends Command
{
    protected $signature = 'encoding:run {--once : Process only one pending job and exit}';

    protected $description = 'Process pending encoding jobs (one movie after another).';

    public function handle(): int
    {
        $this->info('Starting encoding worker...');

        $loop = true;

        do {
            $job = EncodingJob::where('status', 'pending')
                ->orderBy('created_at')
                ->first();

            if (! $job) {
                $this->info('No pending jobs. Done.');
                return Command::SUCCESS;
            }

            $this->processJob($job);

            if ($this->option('once')) {
                $loop = false;
            }
        } while ($loop);

        return Command::SUCCESS;
    }

    protected function processJob(EncodingJob $job): void
    {
        $channel = $job->channel;
        $video   = $job->video;

        if (! $channel || ! $video) {
            $job->status        = 'failed';
            $job->error_message = 'Missing channel or video.';
            $job->finished_at   = now();
            $job->save();

            $this->error("Job #{$job->id} failed: missing channel or video.");
            return;
        }

        $this->info("Encoding job #{$job->id} | Channel: {$channel->name} | Video: {$video->title}");

        $job->status      = 'running';
        $job->started_at  = now();
        $job->progress    = 0;
        $job->error_message = null;
        $job->save();

        $input = $video->file_path;

        $resolution   = $channel->resolution ?: '1920x1080';
        $videoBitrate = ($channel->video_bitrate_kbps ?: 4500) . 'k';
        $audioBitrate = ($channel->audio_bitrate_kbps ?: 128) . 'k';
        $fps          = $channel->fps ?: 25;
        $audioCodec   = $channel->audio_codec ?: 'aac';

        $defaultEncodedDir = storage_path("app/encoded/channel-{$channel->id}");
        $baseOutputPath = rtrim($channel->encoded_output_path ?: $defaultEncodedDir, '/');
        if (! is_dir($baseOutputPath)) {
            mkdir($baseOutputPath, 0775, true);
        }

        $output = $baseOutputPath . "/video-{$video->id}.ts";

        $logoPath = $channel->logo_path ?: env('IPTV_DEFAULT_LOGO_PATH', public_path('logo/SKYLINE.png'));
        if ($logoPath && ! str_starts_with($logoPath, '/')) {
            $logoPath = base_path($logoPath);
        }

        $title = $video->title ?? 'Movie';
        $titleEscaped = str_replace("'", "\\'", $title);

        $filterComplex = "[0:v]scale={$resolution},fps={$fps}[base];";

        if (is_file($logoPath)) {
            $filterComplex .= "movie='{$logoPath}'[logo];"
                . "[base][logo]overlay=10:10[withlogo];";
            $inputLabel = 'withlogo';
        } else {
            $inputLabel = 'base';
        }

        $filterComplex .= "[{$inputLabel}]"
            . "drawtext=text='{$titleEscaped}':fontcolor=white:fontsize=26:"
            . "x=w-tw-20:y=h-th-60:box=1:boxcolor=black@0.4:boxborderw=10,"
            . "drawtext=text='%{pts\\:hms}':fontcolor=white:fontsize=20:"
            . "x=w-tw-20:y=h-th-25:box=1:boxcolor=black@0.4:boxborderw=8"
            . "[vout]";

        $cmd = sprintf(
            'ffmpeg -y -i %s -filter_complex %s -map "[vout]" -map 0:a? ' .
            '-c:v libx264 -b:v %s -preset veryfast -c:a %s -b:a %s %s 2>&1',
            escapeshellarg($input),
            escapeshellarg($filterComplex),
            $videoBitrate,
            escapeshellarg($audioCodec),
            $audioBitrate,
            escapeshellarg($output)
        );

        $this->info("Running ffmpeg...");
        $this->line($cmd);

        $outputLines = [];
        $exitCode    = 0;
        exec($cmd, $outputLines, $exitCode);

        if ($exitCode !== 0) {
            $job->status        = 'failed';
            $job->error_message = 'ffmpeg failed with exit code ' . $exitCode;
            $job->finished_at   = now();
            $job->progress      = 0;
            $job->save();

            $this->error("Job #{$job->id} failed. Exit code: {$exitCode}");
            return;
        }

        $job->status      = 'finished';
        $job->finished_at = now();
        $job->progress    = 100;
        $job->save();

        $this->info("Job #{$job->id} finished. Output: {$output}");
    }
}
