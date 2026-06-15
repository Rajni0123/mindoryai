<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ExpoPushService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPlanExpiryNotifications extends Command
{
    protected $signature = 'notifications:plan-expiry';
    protected $description = 'Send notifications for expiring plans';

    public function handle()
    {
        $pushService = app(ExpoPushService::class);

        // 1. Plans expiring today
        $expiringToday = User::whereNotNull('plan_expires_at')
            ->whereDate('plan_expires_at', Carbon::today())
            ->where('plan_id', '>', 1) // Not free plan
            ->get();

        foreach ($expiringToday as $user) {
            $pushService->sendToUser(
                $user,
                'Plan Expiring Today! ⏰',
                'Your premium plan expires today. Renew now to keep enjoying unlimited features!',
                ['type' => 'plan_expiry', 'action' => 'renew']
            );
            $this->info("Sent expiry notification to: {$user->name}");
        }

        // 2. Plans expiring in 3 days
        $expiringIn3Days = User::whereNotNull('plan_expires_at')
            ->whereDate('plan_expires_at', Carbon::today()->addDays(3))
            ->where('plan_id', '>', 1)
            ->get();

        foreach ($expiringIn3Days as $user) {
            $pushService->sendToUser(
                $user,
                'Plan Expiring Soon 📅',
                'Your premium plan expires in 3 days. Renew now to avoid interruption!',
                ['type' => 'plan_expiry_reminder', 'days' => 3]
            );
            $this->info("Sent 3-day reminder to: {$user->name}");
        }

        // 3. Plans expired yesterday (downgrade reminder)
        $expiredYesterday = User::whereNotNull('plan_expires_at')
            ->whereDate('plan_expires_at', Carbon::yesterday())
            ->where('plan_id', '>', 1)
            ->get();

        foreach ($expiredYesterday as $user) {
            $pushService->sendToUser(
                $user,
                'Your Plan Has Expired 😢',
                'You\'ve been moved to the free plan. Upgrade now to get back your premium features!',
                ['type' => 'plan_expired', 'action' => 'upgrade']
            );
            $this->info("Sent expired notification to: {$user->name}");
        }

        $total = $expiringToday->count() + $expiringIn3Days->count() + $expiredYesterday->count();
        $this->info("Total notifications sent: {$total}");

        Log::info('Plan expiry notifications sent', ['count' => $total]);

        return 0;
    }
}
