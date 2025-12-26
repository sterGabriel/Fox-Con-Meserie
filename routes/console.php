<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automatic metadata sync (keeps playlist fields up to date)
Schedule::command('videos:sync-metadata')->hourly();

// Keep encoding queue progressing automatically (marks completed jobs and starts next queued per channel).
Schedule::command('encoding:monitor')->everyMinute();
