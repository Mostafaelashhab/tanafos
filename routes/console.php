<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Shared hosting has no queue daemon: drain the queue once a minute via the
// scheduler, so the single `schedule:run` cron entry covers jobs + scheduling.
// (Matching, notifications, etc. on the `database` queue.)
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// Pull fresh buyer demand from configured external sources (commission-exempt).
if (config('banha.scrape.enabled')) {
    Schedule::command('demand:import')
        ->hourly()
        ->withoutOverlapping();
}
