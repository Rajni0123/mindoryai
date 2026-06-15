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

// Clean up old feature usage records at midnight IST
Schedule::call(function () {
    app(\App\Services\FeatureLimitService::class)->resetDailyUsage();
})->dailyAt('00:00')
    ->timezone('Asia/Kolkata')
    ->description('Clean up old feature usage records (keeps 90 days)');

// Expire old subscriptions daily at 1 AM IST
Schedule::call(function () {
    app(\App\Services\SubscriptionService::class)->expireSubscriptions();
})->dailyAt('01:00')
    ->timezone('Asia/Kolkata')
    ->description('Expire old subscriptions and mark them as expired');

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

// Cleanup expired and abandoned Study Battle rooms - runs every minute
Schedule::command('study-battle:cleanup')
    ->everyMinute()
    ->description('Cleanup expired study battle rooms and disconnect inactive players')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::debug('Study Battle cleanup completed');
    });

// Send fun study reminder notifications (Zomato-style)
// Runs multiple times a day at strategic times
Schedule::command('notifications:send-fun --type=smart')
    ->dailyAt('08:00') // Morning motivation
    ->timezone('Asia/Kolkata')
    ->description('Send morning study reminder notifications')
    ->withoutOverlapping();

Schedule::command('notifications:send-fun --type=smart')
    ->dailyAt('14:00') // Afternoon reminder
    ->timezone('Asia/Kolkata')
    ->description('Send afternoon study reminder notifications')
    ->withoutOverlapping();

Schedule::command('notifications:send-fun --type=smart')
    ->dailyAt('18:00') // Evening motivation
    ->timezone('Asia/Kolkata')
    ->description('Send evening study reminder notifications')
    ->withoutOverlapping();

Schedule::command('notifications:send-fun --type=smart')
    ->dailyAt('20:30') // Night study time
    ->timezone('Asia/Kolkata')
    ->description('Send night study reminder notifications')
    ->withoutOverlapping();

// Send plan expiry push notifications - runs daily at 9 AM IST
Schedule::command('notifications:plan-expiry')
    ->dailyAt('09:00')
    ->timezone('Asia/Kolkata')
    ->description('Send push notifications for expiring plans')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('Plan expiry push notifications sent');
    });

// Recalculate Topper Rankings - runs every hour
// Auto-promotes top 10 users to topper status based on performance
Schedule::command('toppers:recalculate')
    ->hourly()
    ->timezone('Asia/Kolkata')
    ->description('Recalculate topper rankings - auto-promote/demote based on score')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('[TOPPER RANKING] Hourly recalculation completed');
    })
    ->onFailure(function () {
        \Log::error('[TOPPER RANKING] Hourly recalculation failed');
    });
