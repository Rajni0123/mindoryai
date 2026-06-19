<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Centralized Credit Service
 *
 * ALL credit operations MUST go through this service
 * This ensures:
 * - No duplicate credit checks
 * - Consistent rate limiting
 * - Proper transaction logging
 * - Feature flag enforcement
 * - Daily limit tracking
 */
class CreditService
{
    /**
     * Check if credit system is enabled
     */
    public function isEnabled(): bool
    {
        return (bool) $this->getFeatureFlag('credits_enabled', true);
    }

    /**
     * Check if user has unlimited mode active
     */
    public function hasUnlimitedMode(User $user): bool
    {
        // Check if user has a premium plan with unlimited credits
        if ($user->plan_id) {
            $plan = DB::table('user_plans')->where('id', $user->plan_id)->first();
            if ($plan && $plan->unlimited_credits) {
                return true;
            }
        }

        $credits = DB::table('user_credits')
            ->where('user_id', $user->id)
            ->first();

        if (!$credits || !$credits->unlimited_mode) {
            return false;
        }

        // Check if unlimited mode has expired
        if ($credits->unlimited_until && now()->isAfter($credits->unlimited_until)) {
            // Expired - disable unlimited mode
            DB::table('user_credits')
                ->where('user_id', $user->id)
                ->update([
                    'unlimited_mode' => false,
                    'unlimited_until' => null,
                    'updated_at' => now(),
                ]);
            return false;
        }

        return true;
    }

    /**
     * Get user's current credit balance
     */
    public function getBalance(User $user): int
    {
        try {
            // If unlimited mode, return large number
            if ($this->hasUnlimitedMode($user)) {
                return 999999;
            }

            // Initialize credits if not exists
            $this->initializeUserCredits($user);

            $credits = DB::table('user_credits')
                ->where('user_id', $user->id)
                ->value('available_credits');

            // Ensure we return an integer
            $balance = is_numeric($credits) ? (int) $credits : 0;

            return $balance;
        } catch (\Exception $e) {
            Log::error('Error getting credit balance', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Check if user has enough credits for an action
     *
     * @param User $user
     * @param string $action - e.g., 'chat_message', 'image_generation'
     * @param int $customCost - Override default cost
     * @return array ['has_credits' => bool, 'cost' => int, 'balance' => int, 'reason' => string]
     */
    public function canPerformAction(User $user, string $action, int $customCost = null): array
    {
        // If credit system disabled, allow everything
        if (!$this->isEnabled()) {
            return [
                'has_credits' => true,
                'cost' => 0,
                'balance' => 0,
                'reason' => 'Credit system disabled',
            ];
        }

        // If unlimited mode, allow everything
        if ($this->hasUnlimitedMode($user)) {
            return [
                'has_credits' => true,
                'cost' => 0,
                'balance' => 999999,
                'reason' => 'Unlimited mode active',
            ];
        }

        // Check daily limits first (if enabled)
        $dailyLimitCheck = $this->checkDailyLimit($user, $action);
        if (!$dailyLimitCheck['allowed']) {
            return [
                'has_credits' => false,
                'cost' => 0,
                'balance' => $this->getBalance($user),
                'reason' => $dailyLimitCheck['reason'],
            ];
        }

        // Get cost for this action
        $cost = $customCost ?? $this->getActionCost($action);

        // Get current balance
        $balance = $this->getBalance($user);

        // Check if user has enough credits
        $hasCredits = $balance >= $cost;

        return [
            'has_credits' => $hasCredits,
            'cost' => $cost,
            'balance' => $balance,
            'reason' => $hasCredits ? 'Sufficient credits' : 'Insufficient credits',
        ];
    }

    /**
     * Deduct credits for an action
     *
     * @param User $user
     * @param string $action
     * @param int $customCost
     * @param string $description
     * @param string|null $referenceType - Model class name
     * @param int|null $referenceId - Model ID
     * @return array ['success' => bool, 'message' => string, 'new_balance' => int, 'transaction_id' => int]
     */
    public function deductCredits(
        User $user,
        string $action,
        int $customCost = null,
        string $description = null,
        string $referenceType = null,
        int $referenceId = null
    ): array {
        // If credit system disabled or unlimited mode, skip deduction
        if (!$this->isEnabled() || $this->hasUnlimitedMode($user)) {
            return [
                'success' => true,
                'message' => 'No credits deducted (unlimited mode or disabled)',
                'new_balance' => $this->getBalance($user),
                'transaction_id' => null,
            ];
        }

        // Get cost for this action
        $cost = $customCost ?? $this->getActionCost($action);
        $description = $description ?? "Credit deduction for {$action}";

        // Use database transaction with row locking for atomicity
        DB::beginTransaction();
        try {
            // LOCK ROW FOR UPDATE to prevent race conditions
            // This ensures only one transaction can modify the balance at a time
            $currentCredits = DB::table('user_credits')
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            // Check if credits exist
            if (!$currentCredits) {
                // Initialize if not exists
                $this->initializeUserCredits($user);
                $currentCredits = DB::table('user_credits')
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();
            }

            // Check if user has enough credits (ATOMIC CHECK)
            if ($currentCredits->available_credits < $cost) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => "Insufficient credits: have {$currentCredits->available_credits}, need {$cost}",
                    'new_balance' => $currentCredits->available_credits,
                    'transaction_id' => null,
                ];
            }

            // Check daily limits (ATOMIC CHECK)
            $dailyLimitCheck = $this->checkDailyLimit($user, $action);
            if (!$dailyLimitCheck['allowed']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => "Daily limit exceeded: {$dailyLimitCheck['reason']}",
                    'new_balance' => $currentCredits->available_credits,
                    'transaction_id' => null,
                ];
            }

            // ATOMIC UPDATE: Calculate new balance and update both fields
            $newBalance = $currentCredits->available_credits - $cost;

            // Use explicit integer casting for defense-in-depth against SQL injection
            $safeCost = (int) $cost;
            DB::table('user_credits')
                ->where('user_id', $user->id)
                ->update([
                    'available_credits' => $newBalance,
                    'total_spent' => DB::raw("total_spent + {$safeCost}"),
                    'updated_at' => now(),
                ]);

            // Record transaction
            $transactionId = DB::table('credit_transactions')->insertGetId([
                'user_id' => $user->id,
                'type' => 'spend',
                'amount' => -$cost,
                'balance_after' => $newBalance,
                'reason' => $action,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'ip_address' => request()->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update daily usage
            $this->recordDailyUsage($user, $action, $cost);

            DB::commit();

            Log::info('Credits deducted', [
                'user_id' => $user->id,
                'action' => $action,
                'cost' => $cost,
                'new_balance' => $newBalance,
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => true,
                'message' => "Successfully deducted {$cost} credits",
                'new_balance' => $newBalance,
                'transaction_id' => $transactionId,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to deduct credits', [
                'user_id' => $user->id,
                'action' => $action,
                'cost' => $cost,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to deduct credits: ' . $e->getMessage(),
                'new_balance' => $this->getBalance($user),
                'transaction_id' => null,
            ];
        }
    }

    /**
     * Add credits to user account
     *
     * @param User $user
     * @param int $amount
     * @param string $reason - e.g., 'signup_bonus', 'purchase', 'admin_adjustment'
     * @param string $description
     * @return array
     */
    public function addCredits(
        User $user,
        int $amount,
        string $reason,
        string $description = null
    ): array {
        DB::beginTransaction();
        try {
            // Initialize if needed
            $this->initializeUserCredits($user);

            // Add to user_credits
            DB::table('user_credits')
                ->where('user_id', $user->id)
                ->increment('available_credits', $amount);

            DB::table('user_credits')
                ->where('user_id', $user->id)
                ->increment('total_earned', $amount);

            // Get new balance
            $newBalance = DB::table('user_credits')
                ->where('user_id', $user->id)
                ->value('available_credits');

            // Record transaction
            $transactionId = DB::table('credit_transactions')->insertGetId([
                'user_id' => $user->id,
                'type' => 'earn',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reason' => $reason,
                'description' => $description,
                'ip_address' => request()->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            Log::info('Credits added', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reason' => $reason,
                'new_balance' => $newBalance,
            ]);

            return [
                'success' => true,
                'message' => "Successfully added {$amount} credits",
                'new_balance' => $newBalance,
                'transaction_id' => $transactionId,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to add credits', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to add credits: ' . $e->getMessage(),
                'new_balance' => $this->getBalance($user),
                'transaction_id' => null,
            ];
        }
    }

    /**
     * Initialize user credits (called on first login/signup)
     */
    public function initializeUserCredits(User $user): void
    {
        try {
            $exists = DB::table('user_credits')
                ->where('user_id', $user->id)
                ->exists();

            if (!$exists) {
                // Give signup bonus
                $signupBonus = $this->getFeatureConfig('credits_enabled', 'signup_bonus', 50);

                DB::table('user_credits')->insert([
                    'user_id' => $user->id,
                    'available_credits' => $signupBonus,
                    'total_earned' => $signupBonus,
                    'total_spent' => 0,
                    'unlimited_mode' => false,
                    'unlimited_until' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Record signup bonus transaction
                if ($signupBonus > 0) {
                    DB::table('credit_transactions')->insert([
                        'user_id' => $user->id,
                        'type' => 'earn',
                        'amount' => $signupBonus,
                        'balance_after' => $signupBonus,
                        'reason' => 'signup_bonus',
                        'description' => 'Welcome bonus for new user',
                        'ip_address' => request() ? request()->ip() : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Mark initial credits as given
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['initial_credits_given' => true]);

                    Log::info('Initial credits given', [
                        'user_id' => $user->id,
                        'amount' => $signupBonus,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error initializing user credits', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Check daily usage limits (when unlimited mode is OFF)
     */
    private function checkDailyLimit(User $user, string $action): array
    {
        // Check if daily limits are enabled
        if (!$this->getFeatureFlag('daily_limits_enabled', false)) {
            return ['allowed' => true, 'reason' => 'Daily limits disabled'];
        }

        $today = now()->toDateString();

        // Get or create today's usage record
        $usage = DB::table('daily_usage_limits')
            ->where('user_id', $user->id)
            ->where('usage_date', $today)
            ->first();

        if (!$usage) {
            return ['allowed' => true, 'reason' => 'First action today'];
        }

        $config = $this->getFeatureConfig('daily_limits_enabled');

        // Check specific limits based on action
        switch ($action) {
            case 'chat_message':
            case 'chat_message_with_image':
                $maxMessages = $config['max_messages_per_day'] ?? 100;
                if ($usage->messages_sent >= $maxMessages) {
                    return [
                        'allowed' => false,
                        'reason' => "Daily message limit reached ({$maxMessages} messages)",
                    ];
                }
                break;

            case 'image_generation':
                $maxImages = $config['max_images_per_day'] ?? 10;
                if ($usage->images_generated >= $maxImages) {
                    return [
                        'allowed' => false,
                        'reason' => "Daily image generation limit reached ({$maxImages} images)",
                    ];
                }
                break;
        }

        // Check total daily credit spending
        $maxCreditsPerDay = $config['max_credits_per_day'] ?? 200;
        if ($usage->credits_spent >= $maxCreditsPerDay) {
            return [
                'allowed' => false,
                'reason' => "Daily credit limit reached ({$maxCreditsPerDay} credits)",
            ];
        }

        return ['allowed' => true, 'reason' => 'Within daily limits'];
    }

    /**
     * Record daily usage
     */
    private function recordDailyUsage(User $user, string $action, int $creditsSpent): void
    {
        $today = now()->toDateString();

        // Get or create today's record
        $exists = DB::table('daily_usage_limits')
            ->where('user_id', $user->id)
            ->where('usage_date', $today)
            ->exists();

        if (!$exists) {
            DB::table('daily_usage_limits')->insert([
                'user_id' => $user->id,
                'usage_date' => $today,
                'messages_sent' => 0,
                'images_generated' => 0,
                'credits_spent' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Increment based on action (explicit cast for defense-in-depth)
        $safeCredits = (int) $creditsSpent;
        $updates = ['credits_spent' => DB::raw("credits_spent + {$safeCredits}")];

        if (in_array($action, ['chat_message', 'chat_message_with_image'])) {
            $updates['messages_sent'] = DB::raw('messages_sent + 1');
        } elseif ($action === 'image_generation') {
            $updates['images_generated'] = DB::raw('images_generated + 1');
        }

        DB::table('daily_usage_limits')
            ->where('user_id', $user->id)
            ->where('usage_date', $today)
            ->update($updates);
    }

    /**
     * Get cost for a specific action
     */
    private function getActionCost(string $action): int
    {
        $costs = [
            'chat_message' => $this->getFeatureConfig('chat_credits_enabled', 'cost_per_message', 1),
            'chat_message_with_image' => $this->getFeatureConfig('chat_credits_enabled', 'cost_with_image', 2),
            'image_generation' => $this->getFeatureConfig('image_generation_enabled', 'cost_per_image', 5),
            'voice_transcription' => $this->getFeatureConfig('voice_input_enabled', 'cost_per_minute', 2),
            'quiz_generation' => 3, // Quiz from topic
            'quiz_from_image' => 3, // Quiz from image/PDF
            'scan_solve' => 2, // Scan and solve from image
        ];

        return $costs[$action] ?? 1; // Default to 1 credit
    }

    /**
     * Get feature flag value
     */
    private function getFeatureFlag(string $key, $default = false)
    {
        return Cache::remember("feature_flag.{$key}", 3600, function () use ($key, $default) {
            $flag = DB::table('feature_flags')
                ->where('feature_key', $key)
                ->first();

            return $flag ? $flag->is_enabled : $default;
        });
    }

    /**
     * Get feature configuration
     */
    private function getFeatureConfig(string $featureKey, string $configKey = null, $default = null)
    {
        $flag = DB::table('feature_flags')
            ->where('feature_key', $featureKey)
            ->first();

        if (!$flag || !$flag->config) {
            return $default;
        }

        $config = json_decode($flag->config, true);

        if ($configKey === null) {
            return $config ?? $default;
        }

        return $config[$configKey] ?? $default;
    }

    /**
     * Get user's credit statistics
     */
    public function getUserStats(User $user): array
    {
        $credits = DB::table('user_credits')
            ->where('user_id', $user->id)
            ->first();

        $todayUsage = DB::table('daily_usage_limits')
            ->where('user_id', $user->id)
            ->where('usage_date', now()->toDateString())
            ->first();

        return [
            'available_credits' => $credits->available_credits ?? 0,
            'total_earned' => $credits->total_earned ?? 0,
            'total_spent' => $credits->total_spent ?? 0,
            'unlimited_mode' => $credits->unlimited_mode ?? false,
            'unlimited_until' => $credits->unlimited_until ?? null,
            'today' => [
                'messages_sent' => $todayUsage->messages_sent ?? 0,
                'images_generated' => $todayUsage->images_generated ?? 0,
                'credits_spent' => $todayUsage->credits_spent ?? 0,
            ],
        ];
    }

    /**
     * Check if the user's plan allows a specific feature based on daily limits.
     *
     * @param User $user
     * @param string $featureType - e.g., 'doubts_per_day', 'quizzes_per_day', 'mock_tests_per_month'
     * @return array ['allowed' => bool, 'reason' => string, 'used' => int, 'limit' => int|string]
     */
    public function checkFeatureDailyLimit(User $user, string $featureType): array
    {
        // Admins bypass limits
        if ($user->role === 'admin') {
            return ['allowed' => true, 'reason' => 'Admin bypass', 'used' => 0, 'limit' => 'Unlimited'];
        }

        // Get user's plan
        $plan = $user->plan_id ? DB::table('user_plans')->where('id', $user->plan_id)->first() : null;

        if (!$plan) {
            return ['allowed' => false, 'reason' => 'No active plan', 'used' => 0, 'limit' => 0];
        }

        $features = is_string($plan->features) ? json_decode($plan->features, true) : (array) $plan->features;
        $limit = data_get($features, "daily_limits.{$featureType}");

        // If limit is not configured, allow by default
        if ($limit === null) {
            return ['allowed' => true, 'reason' => 'No limit configured', 'used' => 0, 'limit' => 'N/A'];
        }

        $limit = (int) $limit;

        // -1 means unlimited
        if ($limit === -1) {
            return ['allowed' => true, 'reason' => 'Unlimited', 'used' => 0, 'limit' => 'Unlimited'];
        }

        // 0 means feature is disabled
        if ($limit === 0) {
            return ['allowed' => false, 'reason' => 'Feature not available on your plan', 'used' => 0, 'limit' => 0];
        }

        // Map feature type to the column name in daily_usage_limits
        $columnMap = [
            'doubts_per_day' => 'doubts_used',
            'video_solutions_per_day' => 'video_solutions_used',
            'whiteboard_videos_per_day' => 'whiteboard_videos_used',
            'quizzes_per_day' => 'quizzes_used',
            'scans_per_day' => 'scans_used',
            'mock_tests_per_month' => 'mock_tests_used',
        ];

        $column = $columnMap[$featureType] ?? null;
        if (!$column) {
            return ['allowed' => true, 'reason' => 'Unknown feature type', 'used' => 0, 'limit' => $limit];
        }

        // For monthly limits, check the current month; otherwise check today
        if (str_contains($featureType, '_per_month')) {
            $used = (int) DB::table('daily_usage_limits')
                ->where('user_id', $user->id)
                ->whereYear('usage_date', now()->year)
                ->whereMonth('usage_date', now()->month)
                ->sum($column);
        } else {
            $today = now()->toDateString();
            $record = DB::table('daily_usage_limits')
                ->where('user_id', $user->id)
                ->where('usage_date', $today)
                ->first();
            $used = $record ? (int) $record->$column : 0;
        }

        if ($used >= $limit) {
            $period = str_contains($featureType, '_per_month') ? 'monthly' : 'daily';
            return [
                'allowed' => false,
                'reason' => "Your {$period} limit for this feature has been reached ({$used}/{$limit}). Please upgrade your plan.",
                'used' => $used,
                'limit' => $limit,
            ];
        }

        return ['allowed' => true, 'reason' => 'Within limits', 'used' => $used, 'limit' => $limit];
    }

    /**
     * Record usage for a specific feature type.
     */
    public function recordFeatureUsage(User $user, string $featureType): void
    {
        $columnMap = [
            'doubts_per_day' => 'doubts_used',
            'video_solutions_per_day' => 'video_solutions_used',
            'whiteboard_videos_per_day' => 'whiteboard_videos_used',
            'quizzes_per_day' => 'quizzes_used',
            'scans_per_day' => 'scans_used',
            'mock_tests_per_month' => 'mock_tests_used',
        ];

        $column = $columnMap[$featureType] ?? null;
        if (!$column) {
            return;
        }

        $today = now()->toDateString();

        $exists = DB::table('daily_usage_limits')
            ->where('user_id', $user->id)
            ->where('usage_date', $today)
            ->exists();

        if (!$exists) {
            DB::table('daily_usage_limits')->insert([
                'user_id' => $user->id,
                'usage_date' => $today,
                'messages_sent' => 0,
                'images_generated' => 0,
                'credits_spent' => 0,
                'doubts_used' => 0,
                'video_solutions_used' => 0,
                'whiteboard_videos_used' => 0,
                'quizzes_used' => 0,
                'scans_used' => 0,
                'mock_tests_used' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('daily_usage_limits')
            ->where('user_id', $user->id)
            ->where('usage_date', $today)
            ->update([$column => DB::raw("{$column} + 1")]);
    }

    /**
     * Get today's feature usage summary for a user.
     */
    public function getFeatureUsageSummary(User $user): array
    {
        $today = now()->toDateString();
        $record = DB::table('daily_usage_limits')
            ->where('user_id', $user->id)
            ->where('usage_date', $today)
            ->first();

        $plan = $user->plan_id ? DB::table('user_plans')->where('id', $user->plan_id)->first() : null;
        $features = $plan && $plan->features
            ? (is_string($plan->features) ? json_decode($plan->features, true) : (array) $plan->features)
            : [];
        $limits = data_get($features, 'daily_limits', []);

        $featureTypes = [
            'doubts_per_day' => 'doubts_used',
            'video_solutions_per_day' => 'video_solutions_used',
            'whiteboard_videos_per_day' => 'whiteboard_videos_used',
            'quizzes_per_day' => 'quizzes_used',
            'scans_per_day' => 'scans_used',
            'mock_tests_per_month' => 'mock_tests_used',
        ];

        $summary = [];
        foreach ($featureTypes as $limitKey => $usedColumn) {
            $limit = $limits[$limitKey] ?? null;
            $used = $record ? (int) $record->$usedColumn : 0;

            $summary[$limitKey] = [
                'used' => $used,
                'limit' => $limit === -1 ? 'Unlimited' : ($limit ?? 'N/A'),
                'remaining' => $limit === -1 ? 'Unlimited' : ($limit !== null ? max(0, $limit - $used) : 'N/A'),
            ];
        }

        return $summary;
    }

    /**
     * Clean up old daily usage records (older than 30 days)
     */
    public function cleanupOldUsageRecords(): int
    {
        $deleted = DB::table('daily_usage_limits')
            ->where('usage_date', '<', now()->subDays(30)->toDateString())
            ->delete();

        Log::info('Cleaned up old daily usage records', ['count' => $deleted]);

        return $deleted;
    }
}
