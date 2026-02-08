<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Usage Limit Service
 *
 * Simple logic: Check Plan → Check Limit → Allow or Block → Log Usage
 *
 * NO credit system. Only daily/monthly counters.
 * Daily reset at 12:00 AM (automatic via date-based records).
 */
class UsageLimitService
{
    /**
     * Alias map: maps legacy/alternative feature keys to canonical keys
     */
    private const FEATURE_ALIASES = [
        'quizzes_per_day'             => 'video_quiz',
        'whiteboard_videos_per_day'   => 'whiteboard_video',
        'whiteboard_videos_per_month' => 'whiteboard_video',
        'topic_quiz_per_day'          => 'topic_quiz',
        'topic_quiz_per_month'        => 'topic_quiz',
        'video_quiz_per_day'          => 'video_quiz',
        'quiz_per_day'                => 'video_quiz',
        'quiz_per_month'              => 'video_quiz',
        'exam_prep_per_day'           => 'exam_prep',
        'exam_prep_per_month'         => 'exam_prep',
        'scan_solve_per_day'          => 'scan_solve',
        'scan_solve_per_month'        => 'scan_solve',
        'pdf_uploads_per_day'         => 'pdf_upload',
        'pdf_uploads_per_month'       => 'pdf_upload',
        'chat_messages_per_day'       => 'chat',
        'messages_per_day'            => 'chat',
    ];

    /**
     * Feature type → DB column mapping
     */
    private const COLUMN_MAP = [
        'video_quiz'        => 'video_quiz_used',
        'whiteboard_video'  => 'whiteboard_videos_used',
        'topic_quiz'        => 'topic_quiz_used',
        'exam_prep'         => 'exam_prep_used',
        'scan_solve'        => 'scans_used',
        'pdf_upload'        => 'pdf_uploads_used',
        'chat'              => 'messages_sent',
    ];

    /**
     * Feature type → plan limit key mapping
     */
    private const LIMIT_KEY_MAP = [
        'video_quiz'        => 'video_quiz_per_day',
        'whiteboard_video'  => 'whiteboard_videos_per_month',
        'topic_quiz'        => 'topic_quiz_per_day',
        'exam_prep'         => 'exam_prep_per_day',
        'scan_solve'        => 'scan_solve_per_day',
        'pdf_upload'        => 'pdf_uploads_per_month',
        'chat'              => 'chat_messages_per_day',
    ];

    /**
     * Features that use monthly limits instead of daily
     */
    private const MONTHLY_FEATURES = [
        'pdf_upload',
        'whiteboard_video',
    ];

    /**
     * Check if user can use a feature.
     *
     * @return array ['allowed' => bool, 'reason' => string, 'used' => int, 'limit' => int|string, 'remaining' => int|string]
     */
    public function canUse(User $user, string $feature): array
    {
        // Resolve aliases to canonical feature names
        $feature = self::FEATURE_ALIASES[$feature] ?? $feature;

        // Admins bypass all limits
        if ($user->role === 'admin') {
            return $this->allowed('Admin bypass');
        }

        // Check if paid plan has expired
        if ($user->plan_id && $user->plan_expires_at && now()->gt($user->plan_expires_at)) {
            // Downgrade to free plan
            $user->update([
                'plan_id' => null,
                'plan_expires_at' => null,
            ]);
            $user->refresh();
        }

        // Get user's plan
        $plan = $this->getUserPlan($user);
        if (!$plan) {
            return $this->blocked('No active plan. Please subscribe to continue.', 0, 0);
        }

        // Get plan features/limits
        $features = $this->getPlanFeatures($plan);
        $dailyLimits = $features['daily_limits'] ?? [];

        // Get the limit key for this feature
        $limitKey = self::LIMIT_KEY_MAP[$feature] ?? null;
        if (!$limitKey) {
            Log::warning('Unknown feature key passed to canUse', ['feature' => $feature]);
            return $this->blocked('Unknown feature. Access denied.', 0, 0);
        }

        // Check if the limit exists in the plan (try primary key, then all aliases)
        $limit = $dailyLimits[$limitKey] ?? null;

        // If not found by primary key, search all alias keys for this feature
        if ($limit === null) {
            foreach (self::FEATURE_ALIASES as $aliasKey => $canonicalFeature) {
                if ($canonicalFeature === $feature && isset($dailyLimits[$aliasKey])) {
                    $limitKey = $aliasKey;
                    $limit = $dailyLimits[$aliasKey];
                    break;
                }
            }
        }

        if ($limit === null) {
            // No limit configured = feature not available on this plan
            return $this->blocked('This feature is not available on your plan. Please upgrade.', 0, 0);
        }

        $limit = (int) $limit;

        // -1 = unlimited
        if ($limit === -1) {
            return $this->allowed('Unlimited on your plan');
        }

        // 0 = disabled
        if ($limit === 0) {
            return $this->blocked('This feature is not available on your plan. Please upgrade.', 0, 0);
        }

        // Get current usage
        $isMonthly = $this->isMonthlyFeature($feature, $plan);
        $used = $this->getUsage($user, $feature, $isMonthly);

        if ($used >= $limit) {
            $period = $isMonthly ? 'monthly' : 'daily';
            return $this->blocked(
                "You've reached your {$period} limit ({$used}/{$limit}). Please upgrade your plan for more.",
                $used,
                $limit
            );
        }

        return [
            'allowed' => true,
            'reason' => 'Within limits',
            'used' => $used,
            'limit' => $limit,
            'remaining' => $limit - $used,
        ];
    }

    /**
     * Record usage after a feature is used.
     */
    public function recordUsage(User $user, string $feature): void
    {
        $feature = self::FEATURE_ALIASES[$feature] ?? $feature;
        $column = self::COLUMN_MAP[$feature] ?? null;
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
                'video_quiz_used' => 0,
                'whiteboard_videos_used' => 0,
                'topic_quiz_used' => 0,
                'exam_prep_used' => 0,
                'scans_used' => 0,
                'pdf_uploads_used' => 0,
                'doubts_used' => 0,
                'video_solutions_used' => 0,
                'quizzes_used' => 0,
                'mock_tests_used' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('daily_usage_limits')
            ->where('user_id', $user->id)
            ->where('usage_date', $today)
            ->update([
                $column => DB::raw("{$column} + 1"),
                'updated_at' => now(),
            ]);

        Log::info('Usage recorded', [
            'user_id' => $user->id,
            'feature' => $feature,
            'column' => $column,
        ]);
    }

    /**
     * Check and record in one atomic call.
     * Returns same as canUse(). If allowed, also records usage.
     */
    public function checkAndRecord(User $user, string $feature): array
    {
        $check = $this->canUse($user, $feature);
        if ($check['allowed']) {
            $this->recordUsage($user, $feature);
        }
        return $check;
    }

    /**
     * Get today's usage summary for a user.
     */
    public function getUsageSummary(User $user): array
    {
        $plan = $this->getUserPlan($user);
        $features = $plan ? $this->getPlanFeatures($plan) : [];
        $dailyLimits = $features['daily_limits'] ?? [];
        $today = now()->toDateString();

        $record = DB::table('daily_usage_limits')
            ->where('user_id', $user->id)
            ->where('usage_date', $today)
            ->first();

        $summary = [];
        foreach (self::LIMIT_KEY_MAP as $feature => $limitKey) {
            $column = self::COLUMN_MAP[$feature] ?? null;
            if (!$column) continue;

            $actualLimitKey = $limitKey;
            $limit = $dailyLimits[$actualLimitKey] ?? null;

            // If not found by primary key, search all aliases for this feature
            if ($limit === null) {
                foreach (self::FEATURE_ALIASES as $aliasKey => $canonicalFeature) {
                    if ($canonicalFeature === $feature && isset($dailyLimits[$aliasKey])) {
                        $actualLimitKey = $aliasKey;
                        $limit = $dailyLimits[$aliasKey];
                        break;
                    }
                }
            }

            $isMonthly = $this->isMonthlyFeature($feature, $plan);
            $used = $record ? (int) ($record->$column ?? 0) : 0;

            // For monthly features, sum the whole month
            if ($isMonthly) {
                $used = $this->getUsage($user, $feature, true);
            }

            if ($limit === null) {
                $summary[$feature] = ['used' => $used, 'limit' => 'N/A', 'remaining' => 'N/A'];
            } elseif ((int) $limit === -1) {
                $summary[$feature] = ['used' => $used, 'limit' => 'Unlimited', 'remaining' => 'Unlimited'];
            } else {
                $limit = (int) $limit;
                $summary[$feature] = [
                    'used' => $used,
                    'limit' => $limit,
                    'remaining' => max(0, $limit - $used),
                ];
            }
        }

        return $summary;
    }

    /**
     * Get plan config values for a user.
     */
    public function getPlanConfig(User $user): array
    {
        $plan = $this->getUserPlan($user);
        if (!$plan) {
            return [
                'max_video_length_seconds' => 30,
                'frames_per_video' => 5,
                'pages_per_pdf' => 5,
                'history_days' => 3,
                'watermark' => true,
                'ads' => true,
                'priority_queue' => false,
            ];
        }

        $features = $this->getPlanFeatures($plan);
        return [
            'max_video_length_seconds' => (int) ($features['max_video_length_seconds'] ?? 30),
            'frames_per_video' => (int) ($features['frames_per_video'] ?? 5),
            'pages_per_pdf' => (int) ($features['pages_per_pdf'] ?? 5),
            'history_days' => (int) ($features['history_days'] ?? 3),
            'watermark' => (bool) ($features['watermark'] ?? true),
            'ads' => (bool) ($features['ads'] ?? true),
            'priority_queue' => (bool) ($features['priority_queue'] ?? false),
        ];
    }

    // ── Private helpers ──

    private function getUserPlan(User $user)
    {
        if (!$user->plan_id) {
            // Default to free plan
            $freePlan = DB::table('user_plans')
                ->where('slug', 'free')
                ->where('is_active', true)
                ->first();

            // Safety: if free plan doesn't exist, create a minimal one
            if (!$freePlan) {
                Log::warning('Free plan not found in database - creating default free plan');
                $id = DB::table('user_plans')->insertGetId([
                    'name' => 'Free',
                    'slug' => 'free',
                    'description' => 'Free plan with basic features',
                    'price' => 0,
                    'billing_period' => 'monthly',
                    'validity_days' => 0,
                    'is_active' => true,
                    'order' => 0,
                    'features' => json_encode([
                        'daily_limits' => [
                            'quizzes_per_day' => 3,
                            'whiteboard_videos_per_month' => 2,
                            'exam_prep_per_day' => 2,
                        ],
                        'ads' => true,
                        'watermark' => true,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $freePlan = DB::table('user_plans')->find($id);
            }

            return $freePlan;
        }

        return DB::table('user_plans')
            ->where('id', $user->plan_id)
            ->where('is_active', true)
            ->first();
    }

    private function getPlanFeatures($plan): array
    {
        if (!$plan || !$plan->features) {
            return [];
        }

        $features = $plan->features;
        if (is_string($features)) {
            $features = json_decode($features, true);
            // Handle double-encoded JSON
            if (is_string($features)) {
                $features = json_decode($features, true);
            }
        }

        return is_array($features) ? $features : [];
    }

    private function isMonthlyFeature(string $feature, $plan = null): bool
    {
        return in_array($feature, self::MONTHLY_FEATURES);
    }

    private function getUsage(User $user, string $feature, bool $monthly = false): int
    {
        $column = self::COLUMN_MAP[$feature] ?? null;
        if (!$column) return 0;

        if ($monthly) {
            return (int) DB::table('daily_usage_limits')
                ->where('user_id', $user->id)
                ->whereYear('usage_date', now()->year)
                ->whereMonth('usage_date', now()->month)
                ->sum($column);
        }

        $today = now()->toDateString();
        $record = DB::table('daily_usage_limits')
            ->where('user_id', $user->id)
            ->where('usage_date', $today)
            ->first();

        return $record ? (int) ($record->$column ?? 0) : 0;
    }

    private function allowed(string $reason): array
    {
        return [
            'allowed' => true,
            'reason' => $reason,
            'used' => 0,
            'limit' => 'Unlimited',
            'remaining' => 'Unlimited',
        ];
    }

    private function blocked(string $reason, int $used, int $limit): array
    {
        return [
            'allowed' => false,
            'reason' => $reason,
            'used' => $used,
            'limit' => $limit,
            'remaining' => 0,
        ];
    }

    /**
     * Check if chat should be throttled (slower response) for Lite plan users
     * Returns delay in seconds if throttling needed, 0 otherwise
     */
    public function shouldThrottleChat(User $user): int
    {
        // Admins never throttled
        if ($user->role === 'admin') {
            return 0;
        }

        $plan = $this->getUserPlan($user);
        if (!$plan) {
            return 0;
        }

        $features = $this->getPlanFeatures($plan);

        // Check if this plan has throttling enabled
        if (!($features['throttle_after_fast_limit'] ?? false)) {
            return 0;
        }

        // Get fast chat limit
        $fastChatLimit = $features['daily_limits']['fast_chat_per_day'] ?? null;
        if ($fastChatLimit === null || $fastChatLimit === -1) {
            return 0; // No throttling for unlimited fast chats
        }

        // Get today's chat usage
        $chatUsage = $this->getUsage($user, 'chat', false);

        // If usage exceeds fast limit, apply throttle delay
        if ($chatUsage >= (int) $fastChatLimit) {
            $delay = (int) ($features['throttle_delay_seconds'] ?? 3);
            Log::info('Chat throttled for Lite user', [
                'user_id' => $user->id,
                'chat_usage' => $chatUsage,
                'fast_limit' => $fastChatLimit,
                'delay_seconds' => $delay,
            ]);
            return $delay;
        }

        return 0;
    }
}
