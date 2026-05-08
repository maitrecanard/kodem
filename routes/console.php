<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('monitoring:run')
    ->weeklyOn(1, '03:00') // lundi 03 h 00
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('audits:send-followup')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();
