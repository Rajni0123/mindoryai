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

// Send plan expiry reminder emails - runs daily at 9 AM IST (3:30 AM UTC)
Schedule::command('plans:send-expiry-reminders')
    ->dailyAt('03:30')
    ->description('Send reminder emails for plans expiring in 7, 3, 1, 0 days')
    ->onSuccess(function () {
        \Log::info('Plan expiry reminders sent successfully');
    })
    ->onFailure(function () {
        \Log::error('Plan expiry reminders failed');
    });
