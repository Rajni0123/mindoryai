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
     * Feature type → DB column mapping
     */
    private const COLUMN_MAP = [
        'video_quiz'        => 'video_quiz_used',
        'whiteboard_video'  => 'whiteboard_videos_used',
        'topic_quiz'        => 'topic_quiz_used',
        'exam_prep'         => 'exam_prep_used',
        'scan_solve'        => 'scans_used',
        'pdf_upload'        => 'pdf_uploads_used',
    ];

    /**
     * Feature type → plan limit key mapping
     */
    private const LIMIT_KEY_MAP = [
        'video_quiz'        => 'video_quiz_per_day',
        'whiteboard_video'  => 'whiteboard_videos_per_day',
        'topic_quiz'        => 'topic_quiz_per_day',
        'exam_prep'         => 'exam_prep_per_day',
        'scan_solve'        => 'scan_solve_per_day',
        'pdf_upload'        => 'pdf_uploads_per_month',
    ];

    /**
     * Features that use monthly limits instead of daily
     */
    private const MONTHLY_FEATURES = [
        'pdf_upload',
        'whiteboard_video_free', // Free plan has monthly whiteboard limit
    ];

    /**
     * Check if user can use a feature.
     *
     * @return array ['allowed' => bool, 'reason' => string, 'used' => int, 'limit' => int|string, 'remaining' => int|string]
     */
    public function canUse(User $user, string $feature): array
    {
        // Admins bypass all limits
        if ($user->role === 'admin') {
            return $this->allowed('Admin bypass');
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
            return $this->allowed('Unknown feature, allowed by default');
        }

        // Check if the limit exists in the plan
        $limit = $dailyLimits[$limitKey] ?? null;

        // Special case: Free plan whiteboard uses monthly limit
        if ($feature === 'whiteboard_video' && $plan->slug === 'free') {
            $limitKey = 'whiteboard_videos_per_month';
            $limit = $dailyLimits[$limitKey] ?? 0;
        }

        if ($limit === null) {
            return $this->allowed('No limit configured');
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

            // Handle free plan monthly whiteboard
            $actualLimitKey = $limitKey;
            if ($feature === 'whiteboard_video' && $plan && $plan->slug === 'free') {
                $actualLimitKey = 'whiteboard_videos_per_month';
            }

            $limit = $dailyLimits[$actualLimitKey] ?? null;
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
            return DB::table('user_plans')
                ->where('slug', 'free')
                ->where('is_active', true)
                ->first();
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
        if ($feature === 'pdf_upload') return true;
        if ($feature === 'whiteboard_video' && $plan && $plan->slug === 'free') return true;
        return false;
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
}
