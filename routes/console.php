<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule daily cleanup of old usage records (keeps last 90 days)
Schedule::command('credits:cleanup-usage --days=90 --force')
    ->daily()
    ->at('02:00') // Run at 2 AM when traffic is low
    ->description('Clean up old daily usage records')
    ->onSuccess(function () {
        \Log::info('Scheduled usage records cleanup completed successfully');
    })
    ->onFailure(function () {
        \Log::error('Scheduled usage records cleanup failed');
    });

// Expire subscriptions - runs every hour to catch expired plans promptly
Schedule::command('subscriptions:expire')
    ->hourly()
    ->description('Downgrade expired paid plans to free')
    ->onSuccess(function () {
        \Log::info('Subscription expiry check completed');
    });
