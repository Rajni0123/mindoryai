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

        if (!$planType) {
            return response()->json([
                'success' => false,
                'error' => 'subscription_required',
                'message' => 'Active subscription required. Please choose a plan to continue.',
                'upgrade_prompt' => [
                    'title' => 'Subscribe to Lite',
                    'message' => 'Get started with Lite plan from ₹79/month',
                    'target_plan' => 'lite',
                    'price' => 79,
                ],
            ], 403);
        }

        $limits = $this->planLimits[$planType] ?? null;
        if (!$limits) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_plan',
                'message' => 'Unknown subscription plan.',
            ], 403);
        }

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
    private function getUserPlanType($user): ?string
    {
        if (!$user->plan_id) {
            return null;
        }

        $plan = DB::table('user_plans')->where('id', $user->plan_id)->first();
        return $plan->slug ?? null;
    }

    /**
     * Check token limits (monthly or lifetime)
     */
    private function checkTokenLimit($user, ?string $planType, array $limits): array
    {
        if (!$planType) {
            return [
                'allowed' => false,
                'error' => 'subscription_required',
                'message' => 'Active subscription required. Please choose a plan to continue.',
            ];
        }

        // Paid users: check monthly tokens
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
    private function getUpgradePrompt(?string $currentPlan): ?array
    {
        $prompts = [
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
