<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EncodingJob;
use Carbon\Carbon;

class MonitorEncodingJobs extends Command
{
    protected $signature = 'encoding:monitor';
    protected $description = 'Monitor running encoding jobs and update status when complete';

    public function handle()
    {
        // Get all running jobs
        $jobs = EncodingJob::where('status', 'running')->get();

        foreach ($jobs as $job) {
            // Check if output file exists and has content
            if (file_exists($job->output_path)) {
                $fileSize = filesize($job->output_path);
                
                // If file is > 1MB, likely encoding completed
                if ($fileSize > 1048576) {
                    // Mark as done
                    $job->update([
                        'status' => 'done',
                        'completed_at' => Carbon::now(),
                        'progress' => 100,
                    ]);
                    
                    $this->info("✅ Job {$job->id} completed - {$fileSize} bytes");
                }
            } else {
                // File doesn't exist - check if process crashed
                // Mark as failed if no progress in 5 minutes
                if ($job->started_at && $job->started_at->diffInMinutes(Carbon::now()) > 5) {
                    $job->update([
                        'status' => 'failed',
                        'error_message' => 'Output file not created within 5 minutes',
                    ]);
                    
                    $this->error("❌ Job {$job->id} timed out");
                }
            }
        }
    }
}
