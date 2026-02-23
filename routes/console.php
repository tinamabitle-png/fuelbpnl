<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('settlements:run-due-weekly-cycles')
    ->dailyAt('06:10')
    ->timezone('Africa/Johannesburg')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('settlements-weekly-cycles');

Schedule::command('repayments:run-daily-autopay --limit=250')
    ->hourly()
    ->timezone('Africa/Johannesburg')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('repayments-daily-autopay');
