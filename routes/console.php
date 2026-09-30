<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('notifications:prune')->daily();

Schedule::command('reminders:send')->everyMinute();

// Safety net: re-sync stock used by tracked orders (only records differences).
Schedule::command('inventory:reconcile')->dailyAt('02:00')->withoutOverlapping();