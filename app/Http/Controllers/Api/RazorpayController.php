<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SeasonalPack;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Razorpay\Api\Api;

class RazorpayController extends Controller
{
    protected Api $razorpay;

    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {
        $this->razorpay = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    /**
     * Create Razorpay order for plan subscription.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_slug' => 'required|string|exists:plans,slug',
            'billing_cycle' => 'required|in:monthly,annual',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan = Plan::where('slug', $request->plan_slug)->firstOrFail();
        $amount = $request->billing_cycle === 'annual' ? $plan->price_annual : $plan->price_monthly;

        // Free plan doesn't need payment
        if ($amount === 0) {
            // Directly subscribe
            $subscription = $this->subscriptionService->subscribe(
                $request->user(),
                $plan,
                $request->billing_cycle
            );

            return response()->json([
                'success' => true,
                'message' => 'Subscribed to free plan successfully!',
                'subscription' => [
                    'id' => $subscription->id,
                    'plan' => $plan->name,
                    'expires_at' => $subscription->expires_at->toDateTimeString(),
                ],
            ]);
        }

        try {
            $order = $this->razorpay->order->create([
                'receipt' => 'sub_' . $request->user()->id . '_' . time(),
                'amount' => $amount * 100, // Razorpay expects amount in paise
                'currency' => 'INR',
                'notes' => [
                    'user_id' => $request->user()->id,
                    'plan_slug' => $plan->slug,
                    'billing_cycle' => $request->billing_cycle,
                    'type' => 'subscription',
                ],
            ]);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'amount' => $amount,
                'currency' => 'INR',
                'plan' => [
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                ],
                'billing_cycle' => $request->billing_cycle,
                'key' => config('services.razorpay.key'),
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment order. Please try again.',
            ], 500);
        }
    }

    /**
     * Create Razorpay order for seasonal pack.
     */
    public function createPackOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pack_slug' => 'required|string|exists:seasonal_packs,slug',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $pack = SeasonalPack::where('slug', $request->pack_slug)->firstOrFail();

        if (!$pack->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'This pack is not currently available.',
            ], 400);
        }

        try {
            $order = $this->razorpay->order->create([
                'receipt' => 'pack_' . $request->user()->id . '_' . time(),
                'amount' => $pack->price * 100, // Razorpay expects amount in paise
                'currency' => 'INR',
                'notes' => [
                    'user_id' => $request->user()->id,
                    'pack_slug' => $pack->slug,
                    'type' => 'seasonal_pack',
                ],
            ]);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'amount' => $pack->price,
                'currency' => 'INR',
                'pack' => [
                    'name' => $pack->name,
                    'slug' => $pack->slug,
                    'duration_days' => $pack->duration_days,
                    'plan_name' => $pack->plan->name,
                ],
                'key' => config('services.razorpay.key'),
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay pack order creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment order. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify payment and activate subscription.
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'plan_slug' => 'nullable|string|exists:plans,slug',
            'pack_slug' => 'nullable|string|exists:seasonal_packs,slug',
            'billing_cycle' => 'nullable|in:monthly,annual',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify signature
        $expectedSignature = hash_hmac(
            'sha256',
            $request->razorpay_order_id . '|' . $request->razorpay_payment_id,
            config('services.razorpay.secret')
        );

        if ($expectedSignature !== $request->razorpay_signature) {
            Log::warning('Invalid Razorpay signature', [
                'order_id' => $request->razorpay_order_id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Invalid signature.',
            ], 400);
        }

        try {
            // Handle seasonal pack purchase
            if ($request->pack_slug) {
                $pack = SeasonalPack::where('slug', $request->pack_slug)->firstOrFail();
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
                        'expires_at' => $subscription->expires_at->toDateTimeString(),
                    ],
                ]);
            }

            // Handle plan subscription
            if ($request->plan_slug) {
                $plan = Plan::where('slug', $request->plan_slug)->firstOrFail();
                $subscription = $this->subscriptionService->subscribe(
                    $request->user(),
                    $plan,
                    $request->billing_cycle ?? 'monthly',
                    $request->razorpay_order_id,
                    $request->razorpay_payment_id
                );

                return response()->json([
                    'success' => true,
                    'message' => "Successfully subscribed to {$plan->name}!",
                    'subscription' => [
                        'id' => $subscription->id,
                        'plan' => $plan->name,
                        'billing_cycle' => $subscription->billing_cycle,
                        'expires_at' => $subscription->expires_at->toDateTimeString(),
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No plan or pack specified.',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Subscription activation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verified but subscription activation failed. Please contact support.',
            ], 500);
        }
    }

    /**
     * Handle Razorpay webhook.
     */
    public function webhook(Request $request): JsonResponse
    {
        $webhookSecret = config('services.razorpay.webhook_secret');

        // Verify webhook signature if secret is configured
        if ($webhookSecret) {
            $signature = $request->header('X-Razorpay-Signature');
            $expectedSignature = hash_hmac('sha256', $request->getContent(), $webhookSecret);

            if ($signature !== $expectedSignature) {
                Log::warning('Invalid Razorpay webhook signature');
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        $payload = $request->all();

        try {
            $this->subscriptionService->handlePaymentWebhook($payload);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }
}
