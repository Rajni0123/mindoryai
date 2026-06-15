<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckQuestionLimit
{
    /**
     * Plan limits configuration
     */
    private $planLimits = [
        'free' => [
            'daily_questions' => 5,
            'monthly_tokens' => null,
            'lifetime_tokens' => 50000
        ],
        'lite' => [
            'daily_questions' => 20,
            'monthly_tokens' => 150000
        ],
        'pro' => [
            'daily_questions' => 100,
            'monthly_tokens' => 750000
        ],
        'ultimate' => [
            'daily_questions' => 333,
            'monthly_tokens' => 2500000
        ]
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        $planType = $this->getUserPlanType($user);
        $limits = $this->planLimits[$planType] ?? $this->planLimits['free'];
        $today = Carbon::today();

        // Get or create today's rate limit record
        $rateLimit = DB::table('rate_limits')
            ->where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$rateLimit) {
            DB::table('rate_limits')->insert([
                'user_id' => $user->id,
                'date' => $today,
                'questions_today' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $questionsToday = 0;
        } else {
            $questionsToday = $rateLimit->questions_today;
        }

        // Check daily question limit
        if ($questionsToday >= $limits['daily_questions']) {
            return response()->json([
                'success' => false,
                'error' => 'daily_limit_reached',
                'message' => 'Daily question limit reached',
                'limit' => $limits['daily_questions'],
                'used' => $questionsToday,
                'reset_at' => Carbon::tomorrow()->toISOString(),
                'upgrade_prompt' => $this->getUpgradePrompt($planType)
            ], 429);
        }

        // Check monthly/lifetime token limit
        $tokenCheck = $this->checkTokenLimit($user, $planType, $limits);
        if (!$tokenCheck['allowed']) {
            return response()->json([
                'success' => false,
                'error' => $tokenCheck['error'],
                'message' => $tokenCheck['message'],
                'limit' => $tokenCheck['limit'],
                'used' => $tokenCheck['used'],
                'reset_at' => $tokenCheck['reset_at'] ?? null,
                'upgrade_prompt' => $this->getUpgradePrompt($planType)
            ], 429);
        }

        // Store current usage in request for later tracking
        $request->merge([
            'current_daily_count' => $questionsToday,
            'daily_limit' => $limits['daily_questions'],
            'plan_type' => $planType
        ]);

        return $next($request);
    }

    /**
     * Get user's plan type
     */
    private function getUserPlanType($user): string
    {
        if (!$user->plan_id) {
            return 'free';
        }

        $plan = DB::table('user_plans')->where('id', $user->plan_id)->first();
        return $plan->slug ?? 'free';
    }

    /**
     * Check token limits (monthly or lifetime)
     */
    private function checkTokenLimit($user, string $planType, array $limits): array
    {
        // For FREE users, check lifetime tokens
        if ($planType === 'free') {
            $lifetimeUsed = $user->lifetime_tokens_used ?? 0;
            $lifetimeLimit = $limits['lifetime_tokens'];

            if ($lifetimeUsed >= $lifetimeLimit) {
                return [
                    'allowed' => false,
                    'error' => 'lifetime_limit_reached',
                    'message' => 'Lifetime token limit reached. Upgrade to continue!',
                    'limit' => $lifetimeLimit,
                    'used' => $lifetimeUsed
                ];
            }

            return ['allowed' => true];
        }

        // For paid users, check monthly tokens
        $currentMonth = Carbon::now()->format('Y-m');
        $monthlyUsage = DB::table('user_monthly_tokens')
            ->where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->first();

        $tokensUsed = $monthlyUsage->tokens_used ?? 0;
        $monthlyLimit = $limits['monthly_tokens'];

        if ($tokensUsed >= $monthlyLimit) {
            return [
                'allowed' => false,
                'error' => 'monthly_limit_reached',
                'message' => 'Monthly token limit reached',
                'limit' => $monthlyLimit,
                'used' => $tokensUsed,
                'reset_at' => Carbon::now()->endOfMonth()->toISOString()
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Get upgrade prompt based on current plan
     */
    private function getUpgradePrompt(string $currentPlan): ?array
    {
        $prompts = [
            'free' => [
                'title' => 'Upgrade to LITE',
                'message' => 'Get 20 questions/day for just Rs.99/month',
                'target_plan' => 'lite',
                'price' => 99
            ],
            'lite' => [
                'title' => 'Upgrade to PRO',
                'message' => 'Get 100 questions/day + priority support',
                'target_plan' => 'pro',
                'price' => 299
            ],
            'pro' => [
                'title' => 'Upgrade to ULTIMATE',
                'message' => '333 questions/day + 2.5M tokens/month',
                'target_plan' => 'ultimate',
                'price' => 999
            ]
        ];

        return $prompts[$currentPlan] ?? null;
    }
}
