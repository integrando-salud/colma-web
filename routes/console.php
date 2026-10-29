<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Intervalos anclados al 01/01/1970 UTC, independientes de reinicios y caché.
\Illuminate\Support\Facades\Schedule::command('mobile-api-logs:prune')
    ->dailyAt('03:00')
    ->timezone('UTC')
    ->when(fn () => config('mobile_api_logs.auto_cleanup') &&
        ((int) floor(now('UTC')->timestamp / 86400) % config('mobile_api_logs.cleanup_every_days') === 0));
