<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requires a single cron entry on the server running `schedule:run` every
// minute (see the Deployment Checklist) — Laravel decides from here which
// of the schedules below are actually due to run.
Schedule::command('backup:database --keep=30')
    ->dailyAt('02:00')
    ->onOneServer()
    ->emailOutputOnFailure(env('BACKUP_NOTIFY_EMAIL', config('mail.from.address')));

// Clears expired rows from the `sessions` table (SESSION_DRIVER=database) —
// Laravel's session garbage collection only fires probabilistically on
// requests, so on a low-traffic admin app it can go a long time without
// running; do it deterministically instead.
Schedule::call(fn () => DB::table('sessions')
    ->where('last_activity', '<', now()->subMinutes((int) config('session.lifetime'))->getTimestamp())
    ->delete())
    ->daily()
    ->name('prune-expired-sessions')
    ->onOneServer();
