<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('crm:followups --limit=200')
    ->everyFifteenMinutes()
    ->withoutOverlapping(20)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crm-followups.log'));

Schedule::command('crm:followups --limit=1000')
    ->dailyAt('02:00')
    ->withoutOverlapping(60)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crm-followups-daily.log'));
