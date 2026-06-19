<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TokenTrackingService
{
    /**
     * Plan monthly allocations
     */
    private $monthlyAllocations = [
        'lite' => 150000,
        'pro' => 750000,
        'ultimate' => 2500000
    ];

    /**
     * Plan rollover percentages
     */
    private $rolloverPercent = [
        'lite' => 0,
        'pro' => 0.2,      // 20%
        'ultimate' => 0.3  // 30%
    ];

    /**
     * Track usage after a question is answered
     */
    public function trackUsage(int $userId, int $tokensUsed, string $planType): array
    {
        $today = Carbon::today();
        $currentMonth = Carbon::now()->format('Y-m');

        // Update daily count
        DB::table('rate_limits')
            ->where('user_id', $userId)
            ->where('date', $today)
            ->increment('questions_today');

        DB::table('rate_limits')
            ->where('user_id', $userId)
            ->where('date', $today)
            ->update(['last_question_at' => now()]);

        // Subscription required — no free-tier token tracking
        if (!$planType || $planType === 'free') {
            return [
                'questions_today' => $this->getQuestionsToday($userId),
                'subscription_required' => true,
            ];
        }

        // Paid users: track monthly tokens
        $monthlyRecord = DB::table('user_monthly_tokens')
            ->where('user_id', $userId)
            ->where('month', $currentMonth)
            ->first();

        if (!$monthlyRecord) {
            // Create new monthly record with potential rollover
            $rolledOver = $this->calculateRollover($userId, $planType);
            $allocated = ($this->monthlyAllocations[$planType] ?? 150000) + $rolledOver;

            DB::table('user_monthly_tokens')->insert([
                'user_id' => $userId,
                'month' => $currentMonth,
                'tokens_allocated' => $allocated,
                'tokens_used' => $tokensUsed,
                'tokens_rolled_over' => $rolledOver,
                'questions_asked' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            DB::table('user_monthly_tokens')
                ->where('user_id', $userId)
                ->where('month', $currentMonth)
                ->update([
                    'tokens_used' => DB::raw("tokens_used + {$tokensUsed}"),
                    'questions_asked' => DB::raw('questions_asked + 1'),
                    'updated_at' => now()
                ]);
        }

        // Get updated record
        $monthlyRecord = DB::table('user_monthly_tokens')
            ->where('user_id', $userId)
            ->where('month', $currentMonth)
            ->first();

        return [
            'questions_today' => $this->getQuestionsToday($userId),
            'tokens_this_month' => $monthlyRecord->tokens_used,
            'tokens_allocated' => $monthlyRecord->tokens_allocated,
            'tokens_remaining' => $monthlyRecord->tokens_allocated - $monthlyRecord->tokens_used,
            'questions_this_month' => $monthlyRecord->questions_asked
        ];
    }

    /**
     * Get questions asked today
     */
    public function getQuestionsToday(int $userId): int
    {
        $today = Carbon::today();
        $rateLimit = DB::table('rate_limits')
            ->where('user_id', $userId)
            ->where('date', $today)
            ->first();

        return $rateLimit->questions_today ?? 0;
    }

    /**
     * Calculate rollover from previous month
     */
    private function calculateRollover(int $userId, string $planType): int
    {
        $rolloverPercent = $this->rolloverPercent[$planType] ?? 0;
        if ($rolloverPercent === 0) {
            return 0;
        }

        $previousMonth = Carbon::now()->subMonth()->format('Y-m');
        $previousRecord = DB::table('user_monthly_tokens')
            ->where('user_id', $userId)
            ->where('month', $previousMonth)
            ->first();

        if (!$previousRecord) {
            return 0;
        }

        $unusedTokens = $previousRecord->tokens_allocated - $previousRecord->tokens_used;
        $maxRollover = (int) ($previousRecord->tokens_allocated * $rolloverPercent);

        return min($unusedTokens, $maxRollover);
    }

    /**
     * Get user's current usage status
     */
    public function getUsageStatus(int $userId, string $planType): array
    {
        $today = Carbon::today();
        $currentMonth = Carbon::now()->format('Y-m');

        $questionsToday = $this->getQuestionsToday($userId);

        if (!$planType || $planType === 'free') {
            return [
                'plan_type' => null,
                'subscription_required' => true,
                'questions_today' => $questionsToday,
                'daily_limit' => 0,
                'daily_remaining' => 0,
            ];
        }

        $dailyLimits = [
            'lite' => 20,
            'pro' => 100,
            'ultimate' => 333
        ];

        $dailyLimit = $dailyLimits[$planType] ?? 20;

        $monthlyRecord = DB::table('user_monthly_tokens')
            ->where('user_id', $userId)
            ->where('month', $currentMonth)
            ->first();

        $tokensUsed = $monthlyRecord->tokens_used ?? 0;
        $tokensAllocated = $monthlyRecord->tokens_allocated ?? ($this->monthlyAllocations[$planType] ?? 150000);

        return [
            'plan_type' => $planType,
            'questions_today' => $questionsToday,
            'daily_limit' => $dailyLimit,
            'daily_remaining' => max(0, $dailyLimit - $questionsToday),
            'monthly_tokens_used' => $tokensUsed,
            'monthly_tokens_limit' => $tokensAllocated,
            'monthly_tokens_remaining' => max(0, $tokensAllocated - $tokensUsed),
            'questions_this_month' => $monthlyRecord->questions_asked ?? 0,
            'reset_date' => Carbon::now()->endOfMonth()->format('Y-m-d')
        ];
    }
}
