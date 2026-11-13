<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly database backup at 3:30 AM IST (after late-night service closes).
Schedule::command('bizbite:backup')->dailyAt('03:30')->withoutOverlapping()->onOneServer();
