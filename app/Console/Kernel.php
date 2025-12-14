<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\RunEncodingJobs;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        RunEncodingJobs::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // future cron
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
