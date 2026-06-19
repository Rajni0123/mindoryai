<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;
use App\Models\UserFeatureUsage;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class FeatureLimitService
{
    /**
     * Cache TTL for plan features (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Check if user can use a feature (without incrementing).
     */
    public function canUse(User $user, string $featureSlug): bool
    {
        $remaining = $this->getRemaining($user, $featureSlug);

        if ($remaining['limit'] === -1) {
            return true; // Unlimited
        }

        if ($remaining['limit'] === 0) {
            return false; // Feature disabled
        }

        return $remaining['remaining'] > 0;
    }

    /**
     * Get remaining usage for a feature.
     */
    public function getRemaining(User $user, string $featureSlug): array
    {
        $plan = $this->getUserPlan($user);

        if (!$plan) {
            return [
                'limit' => 0,
                'used' => 0,
                'remaining' => 0,
                'limit_type' => 'boolean',
                'is_unlimited' => false,
                'is_disabled' => true,
            ];
        }

        $feature = $this->getFeatureLimit($plan, $featureSlug);

        if (!$feature) {
            // Feature not configured for this plan - disabled by default
            return [
                'limit' => 0,
                'used' => 0,
                'remaining' => 0,
                'limit_type' => 'boolean',
                'is_unlimited' => false,
                'is_disabled' => true,
            ];
        }

        // Handle unlimited features
        if ($feature->isUnlimited()) {
            return [
                'limit' => -1,
                'used' => $this->getUsageCount($user, $featureSlug, $feature->limit_type),
                'remaining' => 'unlimited',
                'limit_type' => $feature->limit_type,
                'is_unlimited' => true,
                'is_disabled' => false,
            ];
        }

        // Handle disabled features
        if ($feature->isDisabled()) {
            return [
                'limit' => 0,
                'used' => 0,
                'remaining' => 0,
                'limit_type' => 'boolean',
                'is_unlimited' => false,
                'is_disabled' => true,
            ];
        }

        // Handle boolean features (enabled/disabled with value)
        if ($feature->limit_type === PlanFeature::LIMIT_TYPE_BOOLEAN) {
            return [
                'limit' => $feature->limit_value,
                'used' => 0,
                'remaining' => $feature->limit_value,
                'limit_type' => 'boolean',
                'is_unlimited' => false,
                'is_disabled' => $feature->limit_value === 0,
                'extra_info' => $feature->extra_info,
            ];
        }

        // Handle daily/monthly limits
        $used = $this->getUsageCount($user, $featureSlug, $feature->limit_type);
        $remaining = max(0, $feature->limit_value - $used);

        return [
            'limit' => $feature->limit_value,
            'used' => $used,
            'remaining' => $remaining,
            'limit_type' => $feature->limit_type,
            'is_unlimited' => false,
            'is_disabled' => false,
            'extra_info' => $feature->extra_info,
        ];
    }

    /**
     * Increment usage.
     */
    public function trackUsage(User $user, string $featureSlug, int $count = 1): void
    {
        $usage = UserFeatureUsage::getOrCreateForToday($user->id, $featureSlug);
        $usage->incrementUsage($count);
    }

    /**
     * Get user's current plan.
     */
    public function getUserPlan(User $user): ?Plan
    {
        // Get active subscription
        $subscription = UserSubscription::where('user_id', $user->id)
            ->active()
            ->with('plan')
            ->orderBy('plan_id', 'desc') // Higher plan_id = higher tier
            ->first();

        if ($subscription && $subscription->plan) {
            return $subscription->plan;
        }

        if ($user->plan_id) {
            $userPlan = $user->relationLoaded('userPlan')
                ? $user->userPlan
                : $user->userPlan()->first();

            if ($userPlan?->slug) {
                return Plan::where('slug', $userPlan->slug)->first();
            }
        }

        return null;
    }

    /**
     * Get all feature limits for a plan.
     */
    public function getPlanFeatures(Plan $plan): Collection
    {
        $cacheKey = "plan_features:{$plan->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($plan) {
            return $plan->features()->get();
        });
    }

    /**
     * Get user's complete usage summary (for dashboard/API).
     */
    public function getUsageSummary(User $user): array
    {
        $plan = $this->getUserPlan($user);
        if (!$plan) {
            return [
                'plan' => null,
                'plan_name' => null,
                'requires_subscription' => true,
                'expires_at' => null,
                'days_remaining' => null,
                'features' => [],
            ];
        }

        $subscription = UserSubscription::where('user_id', $user->id)
            ->active()
            ->first();

        $features = $this->getPlanFeatures($plan);
        $summary = [];

        foreach ($features as $feature) {
            $remaining = $this->getRemaining($user, $feature->feature_slug);
            $summary[$feature->feature_slug] = [
                'name' => $feature->getDisplayName(),
                'limit' => $remaining['limit'],
                'used' => $remaining['used'],
                'remaining' => $remaining['remaining'],
                'type' => $remaining['limit_type'],
                'is_unlimited' => $remaining['is_unlimited'],
                'is_disabled' => $remaining['is_disabled'],
                'extra_info' => $feature->extra_info,
            ];
        }

        return [
            'plan' => $plan->slug,
            'plan_name' => $plan->name,
            'expires_at' => $subscription ? $subscription->expires_at->toDateString() : null,
            'days_remaining' => $subscription ? $subscription->getDaysRemaining() : null,
            'features' => $summary,
        ];
    }

    /**
     * Get limit exceeded response data.
     */
    public function getLimitExceededResponse(User $user, string $featureSlug): array
    {
        $plan = $this->getUserPlan($user);
        if (!$plan) {
            return [
                'success' => false,
                'error' => 'subscription_required',
                'feature' => $featureSlug,
                'feature_name' => PlanFeature::FEATURE_NAMES[$featureSlug] ?? ucwords(str_replace('_', ' ', $featureSlug)),
                'message' => 'Active subscription required. Please choose a plan to continue.',
                'upgrade_plan' => 'lite',
            ];
        }

        $feature = $this->getFeatureLimit($plan, $featureSlug);
        $remaining = $this->getRemaining($user, $featureSlug);
        $nextPlan = $plan->getNextPlan();

        $resetAt = 'tomorrow';
        if ($feature && $feature->limit_type === PlanFeature::LIMIT_TYPE_MONTHLY) {
            $resetAt = Carbon::now()->startOfMonth()->addMonth()->format('F 1');
        }

        $upgradeMessage = null;
        $upgradePrice = null;
        if ($nextPlan) {
            $nextFeature = $this->getFeatureLimit($nextPlan, $featureSlug);
            $nextLimit = $nextFeature ? ($nextFeature->isUnlimited() ? 'Unlimited' : $nextFeature->limit_value) : 0;
            $limitSuffix = $feature && $feature->limit_type === PlanFeature::LIMIT_TYPE_MONTHLY ? '/month' : '/day';
            $upgradeMessage = "Upgrade to {$nextPlan->name} for {$nextLimit}{$limitSuffix}!";
            $upgradePrice = $nextPlan->price_monthly;
        }

        return [
            'success' => false,
            'error' => 'limit_exceeded',
            'feature' => $featureSlug,
            'feature_name' => PlanFeature::FEATURE_NAMES[$featureSlug] ?? ucwords(str_replace('_', ' ', $featureSlug)),
            'limit' => $remaining['limit'],
            'used' => $remaining['used'],
            'remaining' => 0,
            'reset_at' => $resetAt,
            'upgrade_message' => $upgradeMessage,
            'upgrade_plan' => $nextPlan ? $nextPlan->slug : null,
            'upgrade_price' => $upgradePrice,
        ];
    }

    /**
     * Reset daily usage (call via scheduler at midnight).
     */
    public function resetDailyUsage(): void
    {
        // Daily usage auto-resets because we use date-based records
        // This method can be used for cleanup of old records
        $cutoffDate = Carbon::now()->subDays(90);

        UserFeatureUsage::where('usage_date', '<', $cutoffDate)->delete();
    }

    /**
     * Clear plan features cache.
     */
    public function clearCache(?Plan $plan = null): void
    {
        if ($plan) {
            Cache::forget("plan_features:{$plan->id}");
        } else {
            // Clear all plan feature caches
            $plans = Plan::all();
            foreach ($plans as $p) {
                Cache::forget("plan_features:{$p->id}");
            }
        }
    }

    /**
     * Get feature limit for a plan.
     */
    private function getFeatureLimit(Plan $plan, string $featureSlug): ?PlanFeature
    {
        $features = $this->getPlanFeatures($plan);
        return $features->firstWhere('feature_slug', $featureSlug);
    }

    /**
     * Get usage count based on limit type.
     */
    private function getUsageCount(User $user, string $featureSlug, string $limitType): int
    {
        if ($limitType === PlanFeature::LIMIT_TYPE_MONTHLY) {
            return UserFeatureUsage::getMonthlyUsage($user->id, $featureSlug);
        }

        return UserFeatureUsage::getTodayUsage($user->id, $featureSlug);
    }
}
