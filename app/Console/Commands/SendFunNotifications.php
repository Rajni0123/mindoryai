<?php

namespace App\Console\Commands;

use App\Models\PushToken;
use App\Models\User;
use App\Services\ExpoPushService;
use App\Services\FunNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendFunNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:send-fun
                            {--type=smart : Type of notification (smart, study_reminder, streak, etc.)}
                            {--user= : Send to specific user ID}
                            {--all : Send to all users with push tokens}
                            {--test : Test mode - only logs, doesn\'t send}';

    /**
     * The console command description.
     */
    protected $description = 'Send fun Zomato-style study reminder notifications';

    protected ExpoPushService $pushService;
    protected FunNotificationService $funService;

    public function __construct(ExpoPushService $pushService, FunNotificationService $funService)
    {
        parent::__construct();
        $this->pushService = $pushService;
        $this->funService = $funService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $userId = $this->option('user');
        $sendToAll = $this->option('all');
        $testMode = $this->option('test');

        $this->info("Starting fun notification dispatch...");
        $this->info("Type: {$type}, Test Mode: " . ($testMode ? 'Yes' : 'No'));

        // Get current hour for quiet hours check
        $currentHour = (int) now()->format('H');

        // Skip during quiet hours (11 PM - 7 AM)
        if ($currentHour >= 23 || $currentHour < 7) {
            $this->info("Skipping - quiet hours (11 PM - 7 AM)");
            return 0;
        }

        // Get users to notify
        if ($userId) {
            $users = User::where('id', $userId)->get();
        } elseif ($sendToAll) {
            // Get all users with active push tokens
            $users = User::whereHas('pushTokens', function ($q) {
                $q->where('is_active', true);
            })->get();
        } else {
            // Smart selection: users who haven't been active today
            $users = User::whereHas('pushTokens', function ($q) {
                    $q->where('is_active', true);
                })
                ->where(function ($q) {
                    $q->whereNull('last_active_at')
                      ->orWhere('last_active_at', '<', now()->subHours(4));
                })
                ->inRandomOrder()
                ->limit(100) // Limit to avoid overwhelming the system
                ->get();
        }

        $this->info("Found {$users->count()} users to notify");

        $sentCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            // Check user's notification preferences
            $preferences = $user->notification_preferences ?? [];
            if (isset($preferences['fun_notifications']) && !$preferences['fun_notifications']) {
                $this->line("Skipping user {$user->id} - notifications disabled");
                continue;
            }

            // Check user's quiet hours
            $quietStart = $preferences['quiet_hours_start'] ?? 22;
            $quietEnd = $preferences['quiet_hours_end'] ?? 7;
            if ($currentHour >= $quietStart || $currentHour < $quietEnd) {
                $this->line("Skipping user {$user->id} - quiet hours");
                continue;
            }

            // Get personalized notification
            if ($type === 'smart') {
                $notification = $this->funService->getSmartNotification($user);
            } else {
                $notification = $this->funService->getNotification($user, $type);
            }

            if ($testMode) {
                $this->info("Would send to {$user->name}: {$notification['title']} - {$notification['body']}");
                $sentCount++;
                continue;
            }

            // Send notification
            $result = $this->pushService->sendToUser(
                $user,
                $notification['title'],
                $notification['body'],
                ['type' => 'fun_study_reminder']
            );

            if ($result['success']) {
                $sentCount++;
                $this->line("Sent to {$user->name}");
            } else {
                $failedCount++;
                $this->error("Failed for {$user->name}: " . ($result['message'] ?? 'Unknown error'));
            }

            // Small delay to avoid rate limiting
            usleep(100000); // 100ms
        }

        $this->info("Completed! Sent: {$sentCount}, Failed: {$failedCount}");

        Log::info('Fun notifications sent', [
            'sent' => $sentCount,
            'failed' => $failedCount,
            'type' => $type,
        ]);

        return 0;
    }
}
