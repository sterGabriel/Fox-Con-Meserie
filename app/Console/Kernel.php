<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\RunEncodingJobs;
use App\Console\Commands\MonitorEncodingJobs;
use App\Console\Commands\PurgeChannelTs;
use App\Console\Commands\DedupeEncodingJobs;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        RunEncodingJobs::class,
        MonitorEncodingJobs::class,
        PurgeChannelTs::class,
        DedupeEncodingJobs::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Monitor encoding jobs every minute
        $schedule->command('encoding:monitor')->everyMinute();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
