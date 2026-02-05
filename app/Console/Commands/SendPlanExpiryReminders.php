<?php

namespace App\Console\Commands;

use App\Mail\PlanExpiryReminder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPlanExpiryReminders extends Command
{
    protected $signature = 'plans:send-expiry-reminders';

    protected $description = 'Send reminder emails to users whose plans are about to expire (7, 3, 1, 0 days before)';

    public function handle(): int
    {
        $reminderDays = [7, 3, 1, 0];
        $totalSent = 0;

        foreach ($reminderDays as $days) {
            $targetDate = now()->addDays($days)->startOfDay();

            $users = User::whereNotNull('plan_id')
                ->whereNotNull('plan_expires_at')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->whereDate('plan_expires_at', $targetDate->toDateString())
                ->with('userPlan')
                ->get();

            foreach ($users as $user) {
                if (!$user->userPlan) {
                    continue;
                }

                // Skip free plan users
                if ($user->userPlan->price <= 0) {
                    continue;
                }

                try {
                    $planName = $user->userPlan->name;
                    $expiryDate = $user->plan_expires_at->format('d M Y');

                    Mail::to($user->email)
                        ->send(new PlanExpiryReminder(
                            user: $user,
                            daysLeft: $days,
                            planName: $planName,
                            expiryDate: $expiryDate,
                        ));

                    $totalSent++;

                    Log::info("Plan expiry reminder sent", [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'plan' => $planName,
                        'days_left' => $days,
                        'expires_at' => $expiryDate,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to send plan expiry reminder", [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);

                    $this->error("Failed for {$user->email}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Plan expiry reminders sent: {$totalSent}");

        return self::SUCCESS;
    }
}
