<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\RunEncodingJobs;
use App\Console\Commands\MonitorEncodingJobs;
use App\Console\Commands\PurgeChannelTs;
use App\Console\Commands\DedupeEncodingJobs;
use App\Console\Commands\AutostartChannels;
use App\Console\Commands\BackupFull;
use App\Console\Commands\GitAutoBackup;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        RunEncodingJobs::class,
        MonitorEncodingJobs::class,
        PurgeChannelTs::class,
        DedupeEncodingJobs::class,
        AutostartChannels::class,
        BackupFull::class,
        GitAutoBackup::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Monitor encoding jobs every minute
        $schedule->command('encoding:monitor')->everyMinute();

        // Watchdog: auto-start/restart channels that should be live (e.g. after reboot or crash).
        // Requires a system cron running `php artisan schedule:run`.
        $schedule->command('channels:autostart --only-missing')
            ->everyMinute()
            ->withoutOverlapping(1);
        
        // Auto backup to GitHub (commit + push) twice daily.
        // Requires a system cron running `php artisan schedule:run`.
        $schedule->command('git:autobackup --push-branch=backup/auto')
            ->cron('0 1,13 * * *')
            ->description('GitHub auto-backup (commit + push)')
            ->withoutOverlapping(10);
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
