<?php

namespace App\Console\Commands;

use App\Services\EmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire {--grace-days=3 : Days of grace period before downgrade}';
    protected $description = 'Downgrade users whose paid plan has expired (with grace period) to free plan';

    public function handle(): int
    {
        $graceDays = (int) $this->option('grace-days');
        $graceDate = now()->subDays($graceDays);

        $this->info("Checking for expired subscriptions (grace period: {$graceDays} days)...");

        // Step 1: Find users in grace period (expired but within grace window) - send warning
        $graceUsers = DB::table('users')
            ->whereNotNull('plan_id')
            ->whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '<', now())
            ->where('plan_expires_at', '>=', $graceDate)
            ->where('role', '!=', 'admin')
            ->get(['id', 'name', 'email', 'mobile', 'plan_id', 'plan_expires_at']);

        foreach ($graceUsers as $user) {
            $daysLeft = now()->diffInDays($user->plan_expires_at, false);
            $daysInGrace = abs($daysLeft);
            $daysUntilDowngrade = $graceDays - $daysInGrace;

            $this->line("  Grace period: {$user->name} (ID: {$user->id}) - {$daysUntilDowngrade} days until downgrade");

            // Log grace period
            $this->logSubscriptionEvent($user->id, 'grace_period', [
                'plan_id' => $user->plan_id,
                'expired_at' => $user->plan_expires_at,
                'days_until_downgrade' => $daysUntilDowngrade,
            ]);
        }

        // Step 2: Find users past grace period - actually downgrade
        $expiredUsers = DB::table('users')
            ->whereNotNull('plan_id')
            ->whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '<', $graceDate)
            ->where('role', '!=', 'admin')
            ->get(['id', 'name', 'email', 'mobile', 'plan_id', 'plan_expires_at']);

        if ($expiredUsers->isEmpty()) {
            $this->info('No subscriptions past grace period.');
            return 0;
        }

        $count = 0;
        foreach ($expiredUsers as $user) {
            $oldPlanId = $user->plan_id;

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'plan_id' => null,
                    'plan_expires_at' => null,
                    'updated_at' => now(),
                ]);

            $count++;
            $this->line("  Downgraded: {$user->name} (ID: {$user->id}) - plan was #{$oldPlanId}, expired {$user->plan_expires_at}");

            // Log the downgrade
            $this->logSubscriptionEvent($user->id, 'expired_downgrade', [
                'old_plan_id' => $oldPlanId,
                'expired_at' => $user->plan_expires_at,
                'grace_days' => $graceDays,
            ]);

            // Send expiry notification email
            $this->sendExpiryNotification($user);
        }

        $this->info("Downgraded {$count} expired users to free plan.");
        Log::info("Subscription expiry: {$count} users downgraded to free plan.", [
            'grace_days' => $graceDays,
            'users_in_grace' => $graceUsers->count(),
        ]);

        return 0;
    }

    /**
     * Log subscription event for audit trail
     */
    private function logSubscriptionEvent(int $userId, string $event, array $data): void
    {
        try {
            DB::table('subscription_logs')->insert([
                'user_id' => $userId,
                'event' => $event,
                'data' => json_encode($data),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Table might not exist yet, just log to file
            Log::info("Subscription event: {$event}", array_merge(['user_id' => $userId], $data));
        }
    }

    /**
     * Send plan expiry notification to user
     */
    private function sendExpiryNotification($user): void
    {
        try {
            // Get plan name for email
            $planName = 'Premium';
            if ($user->plan_id) {
                $plan = DB::table('user_plans')->find($user->plan_id);
                $planName = $plan->name ?? 'Premium';
            }

            // Create user object for email service
            $userObj = new \stdClass();
            $userObj->name = $user->name;
            $userObj->email = $user->email;

            if ($user->email) {
                EmailService::sendPlanExpired($userObj, $planName);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send expiry notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
