<?php

namespace App\Services;

use App\Models\User;
use App\Models\UsageLog;
use Illuminate\Support\Facades\Auth;

class UsageTrackingService
{
    /**
     * Track AI API call
     */
    public static function trackApiCall($userId, $aiModel, $tokensConsumed = 0, $requestData = null, $responseData = null)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        // Log the usage
        UsageLog::logUsage($userId, 'api_call', [
            'ai_model' => $aiModel,
            'tokens_consumed' => $tokensConsumed,
            'request_data' => $requestData,
            'response_data' => $responseData,
        ]);

        // Update user's token usage
        $user->consumeTokens($tokensConsumed);

        return true;
    }

    /**
     * Track message
     */
    public static function trackMessage($userId, $aiModel = null, $tokensConsumed = 0)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        UsageLog::logUsage($userId, 'message', [
            'ai_model' => $aiModel,
            'tokens_consumed' => $tokensConsumed,
        ]);

        if ($tokensConsumed > 0) {
            $user->consumeTokens($tokensConsumed);
        }

        return true;
    }

    /**
     * Track image upload
     */
    public static function trackImageUpload($userId, $creditsConsumed = 1)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        UsageLog::logUsage($userId, 'image_upload', [
            'credits_consumed' => $creditsConsumed,
        ]);

        return true;
    }

    /**
     * Track image generation
     */
    public static function trackImageGeneration($userId, $aiModel = null, $creditsConsumed = 1)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        UsageLog::logUsage($userId, 'image_generation', [
            'ai_model' => $aiModel,
            'credits_consumed' => $creditsConsumed,
        ]);

        return true;
    }

    /**
     * Check if user can perform action based on limits
     */
    public static function canPerformAction($userId, $actionType)
    {
        $user = User::find($userId);
        if (!$user || !$user->canAccessAI()) {
            return false;
        }

        if (!$user->userPlan) {
            return $user->hasTokensRemaining();
        }

        $plan = $user->userPlan;
        $stats = UsageLog::getUserStats($userId);

        switch ($actionType) {
            case 'api_call':
                return $stats['total_api_calls'] < $plan->api_calls;

            case 'message':
                return $stats['total_tokens_consumed'] < $plan->message_tokens;

            case 'image_upload':
                return $stats['total_image_uploads'] < $plan->image_uploads;

            case 'image_generation':
                return $stats['total_image_generations'] < $plan->image_credits;

            default:
                return $user->hasTokensRemaining();
        }
    }

    /**
     * Get user's remaining limits
     */
    public static function getRemainingLimits($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        $stats = UsageLog::getUserStats($userId);

        if ($user->userPlan) {
            $plan = $user->userPlan;
            return [
                'api_calls' => max(0, $plan->api_calls - $stats['total_api_calls']),
                'message_tokens' => max(0, $plan->message_tokens - $stats['total_tokens_consumed']),
                'image_uploads' => max(0, $plan->image_uploads - $stats['total_image_uploads']),
                'image_credits' => max(0, $plan->image_credits - $stats['total_image_generations']),
            ];
        }

        return [
            'tokens' => $user->getRemainingTokens(),
        ];
    }
}
