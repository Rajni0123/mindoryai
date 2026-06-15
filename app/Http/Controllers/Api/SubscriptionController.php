<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SeasonalPack;
use App\Services\FeatureLimitService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected FeatureLimitService $featureLimitService
    ) {}

    /**
     * List all plans with features.
     */
    public function plans(): JsonResponse
    {
        $plans = Plan::active()
            ->ordered()
            ->with('features')
            ->get()
            ->map(function ($plan) {
                $features = [];
                foreach ($plan->features as $feature) {
                    $features[$feature->feature_slug] = [
                        'name' => $feature->getDisplayName(),
                        'limit' => $feature->limit_value,
                        'limit_type' => $feature->limit_type,
                        'description' => $feature->getLimitDescription(),
                        'extra_info' => $feature->extra_info,
                    ];
                }

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price_monthly' => $plan->price_monthly,
                    'price_annual' => $plan->price_annual,
                    'features' => $features,
                ];
            });

        return response()->json([
            'success' => true,
            'plans' => $plans,
        ]);
    }

    /**
     * Get seasonal packs.
     */
    public function seasonalPacks(): JsonResponse
    {
        $packs = SeasonalPack::available()
            ->with('plan')
            ->get()
            ->map(function ($pack) {
                return [
                    'id' => $pack->id,
                    'name' => $pack->name,
                    'slug' => $pack->slug,
                    'price' => $pack->price,
                    'duration_days' => $pack->duration_days,
                    'plan_name' => $pack->plan->name,
                    'plan_slug' => $pack->plan->slug,
                    'description' => $pack->description,
                    'tag' => $pack->tag,
                    'icon' => $pack->icon,
                ];
            });

        return response()->json([
            'success' => true,
            'packs' => $packs,
        ]);
    }

    /**
     * Subscribe to a plan.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_slug' => 'required|string|exists:plans,slug',
            'billing_cycle' => 'required|in:monthly,annual',
            'razorpay_order_id' => 'nullable|string',
            'razorpay_payment_id' => 'nullable|string',
            'razorpay_subscription_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan = Plan::where('slug', $request->plan_slug)->firstOrFail();

        try {
            $subscription = $this->subscriptionService->subscribe(
                $request->user(),
                $plan,
                $request->billing_cycle,
                $request->razorpay_order_id,
                $request->razorpay_payment_id,
                $request->razorpay_subscription_id
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully subscribed to {$plan->name}!",
                'subscription' => [
                    'id' => $subscription->id,
                    'plan' => $plan->name,
                    'billing_cycle' => $subscription->billing_cycle,
                    'starts_at' => $subscription->starts_at->toDateTimeString(),
                    'expires_at' => $subscription->expires_at->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Buy a seasonal pack.
     */
    public function buySeasonalPack(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pack_slug' => 'required|string|exists:seasonal_packs,slug',
            'razorpay_order_id' => 'nullable|string',
            'razorpay_payment_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $pack = SeasonalPack::where('slug', $request->pack_slug)->firstOrFail();

        try {
            $subscription = $this->subscriptionService->buySeasonalPack(
                $request->user(),
                $pack,
                $request->razorpay_order_id,
                $request->razorpay_payment_id
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully purchased {$pack->name}!",
                'subscription' => [
                    'id' => $subscription->id,
                    'plan' => $subscription->plan->name,
                    'pack' => $pack->name,
                    'starts_at' => $subscription->starts_at->toDateTimeString(),
                    'expires_at' => $subscription->expires_at->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get current subscription.
     */
    public function current(Request $request): JsonResponse
    {
        $subscription = $this->subscriptionService->getActive($request->user());
        $plan = $this->featureLimitService->getUserPlan($request->user());

        $response = [
            'success' => true,
            'has_subscription' => $subscription !== null,
            'plan' => [
                'name' => $plan->name,
                'slug' => $plan->slug,
            ],
        ];

        if ($subscription) {
            $response['subscription'] = [
                'id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'plan_name' => $plan->name,
                'billing_cycle' => $subscription->billing_cycle,
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at->toDateTimeString(),
                'expires_at' => $subscription->expires_at->toDateTimeString(),
                'ends_at' => $subscription->expires_at->toDateTimeString(),
                'days_remaining' => $subscription->getDaysRemaining(),
                'seasonal_pack' => $subscription->seasonalPack ? $subscription->seasonalPack->name : null,
            ];
        }

        return response()->json($response);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $cancelled = $this->subscriptionService->cancel($request->user());

        if (!$cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found to cancel.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully. You can continue using the service until the current period ends.',
        ]);
    }

    /**
     * Get usage summary for all features.
     */
    public function usage(Request $request): JsonResponse
    {
        $summary = $this->featureLimitService->getUsageSummary($request->user());

        return response()->json([
            'success' => true,
            ...$summary,
        ]);
    }

    /**
     * Get specific feature usage.
     */
    public function featureUsage(Request $request, string $feature): JsonResponse
    {
        $remaining = $this->featureLimitService->getRemaining($request->user(), $feature);

        return response()->json([
            'success' => true,
            'feature' => $feature,
            ...$remaining,
        ]);
    }

    /**
     * Get subscription history.
     */
    public function history(Request $request): JsonResponse
    {
        $subscriptions = $this->subscriptionService->getAll($request->user());

        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'plan' => $sub->plan->name,
                    'billing_cycle' => $sub->billing_cycle,
                    'status' => $sub->status,
                    'starts_at' => $sub->starts_at->toDateTimeString(),
                    'expires_at' => $sub->expires_at->toDateTimeString(),
                    'seasonal_pack' => $sub->seasonalPack ? $sub->seasonalPack->name : null,
                ];
            }),
        ]);
    }
}
