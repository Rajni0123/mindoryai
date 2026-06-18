<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\SeasonalPack;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class SubscriptionService
{
    /**
     * Subscribe user to a plan.
     */
    public function subscribe(
        User $user,
        Plan $plan,
        string $billingCycle,
        ?string $razorpayOrderId = null,
        ?string $razorpayPaymentId = null,
        ?string $razorpaySubscriptionId = null
    ): UserSubscription {
        return DB::transaction(function () use ($user, $plan, $billingCycle, $razorpayOrderId, $razorpayPaymentId, $razorpaySubscriptionId) {
            // Cancel any existing active subscriptions
            $this->cancelAllActiveSubscriptions($user);

            // Calculate expiry date
            $startsAt = Carbon::now();
            $expiresAt = $this->calculateExpiryDate($billingCycle, $startsAt);

            // Create new subscription
            return UserSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'status' => UserSubscription::STATUS_ACTIVE,
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_subscription_id' => $razorpaySubscriptionId,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
            ]);
        });
    }

    /**
     * Buy seasonal pack.
     */
    public function buySeasonalPack(
        User $user,
        SeasonalPack $pack,
        ?string $razorpayOrderId = null,
        ?string $razorpayPaymentId = null
    ): UserSubscription {
        if (!$pack->isAvailable()) {
            throw new Exception('This seasonal pack is not currently available.');
        }

        return DB::transaction(function () use ($user, $pack, $razorpayOrderId, $razorpayPaymentId) {
            // Check if user already has a subscription with a higher plan
            $existingSubscription = $this->getActive($user);
            if ($existingSubscription && $existingSubscription->plan->isHigherThan($pack->plan)) {
                throw new Exception('You already have a higher plan subscription.');
            }

            // Cancel existing subscriptions with lower or equal plans
            $this->cancelAllActiveSubscriptions($user);

            // Create new subscription
            $startsAt = Carbon::now();
            $expiresAt = $startsAt->copy()->addDays($pack->duration_days);

            return UserSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $pack->plan_id,
                'seasonal_pack_id' => $pack->id,
                'billing_cycle' => UserSubscription::CYCLE_SEASONAL,
                'status' => UserSubscription::STATUS_ACTIVE,
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
            ]);
        });
    }

    /**
     * Cancel user's subscription.
     */
    public function cancel(User $user): bool
    {
        $subscription = $this->getActive($user);

        if (!$subscription) {
            return false;
        }

        return $subscription->cancel();
    }

    /**
     * Check if user has an active subscription.
     */
    public function isActive(User $user): bool
    {
        return $this->getActive($user) !== null;
    }

    /**
     * Get user's active subscription.
     */
    public function getActive(User $user): ?UserSubscription
    {
        return UserSubscription::where('user_id', $user->id)
            ->active()
            ->with(['plan', 'seasonalPack'])
            ->first();
    }

    /**
     * Get user's best active subscription (highest plan).
     */
    public function getBestActive(User $user): ?UserSubscription
    {
        return UserSubscription::where('user_id', $user->id)
            ->active()
            ->with(['plan', 'seasonalPack'])
            ->join('plans', 'user_subscriptions.plan_id', '=', 'plans.id')
            ->orderBy('plans.sort_order', 'desc')
            ->select('user_subscriptions.*')
            ->first();
    }

    /**
     * Get all user subscriptions (including expired).
     */
    public function getAll(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return UserSubscription::where('user_id', $user->id)
            ->with(['plan', 'seasonalPack'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Handle Razorpay webhook for payment events.
     */
    public function handlePaymentWebhook(array $payload): void
    {
        $event = $payload['event'] ?? null;

        Log::info('Razorpay webhook received', ['event' => $event, 'payload' => $payload]);

        switch ($event) {
            case 'payment.captured':
                $this->handlePaymentCaptured($payload);
                break;

            case 'payment.failed':
                $this->handlePaymentFailed($payload);
                break;

            case 'subscription.activated':
                $this->handleSubscriptionActivated($payload);
                break;

            case 'subscription.cancelled':
                $this->handleSubscriptionCancelled($payload);
                break;

            case 'subscription.charged':
                $this->handleSubscriptionCharged($payload);
                break;

            default:
                Log::warning('Unhandled Razorpay webhook event', ['event' => $event]);
        }
    }

    /**
     * Check and expire old subscriptions (run daily via scheduler).
     */
    public function expireSubscriptions(): int
    {
        $now = Carbon::now();

        $expired = UserSubscription::where('status', UserSubscription::STATUS_ACTIVE)
            ->where('expires_at', '<', $now)
            ->update(['status' => UserSubscription::STATUS_EXPIRED]);

        Log::info("Expired {$expired} subscriptions");

        return $expired;
    }

    /**
     * Extend subscription by days.
     */
    public function extend(UserSubscription $subscription, int $days): UserSubscription
    {
        $subscription->expires_at = $subscription->expires_at->addDays($days);
        $subscription->save();

        return $subscription;
    }

    /**
     * Upgrade user to a higher plan.
     */
    public function upgrade(User $user, Plan $newPlan, string $billingCycle): UserSubscription
    {
        $currentSubscription = $this->getActive($user);

        if ($currentSubscription && !$newPlan->isHigherThan($currentSubscription->plan)) {
            throw new Exception('You can only upgrade to a higher plan.');
        }

        return $this->subscribe($user, $newPlan, $billingCycle);
    }

    /**
     * Calculate expiry date based on billing cycle.
     */
    private function calculateExpiryDate(string $billingCycle, Carbon $startDate): Carbon
    {
        return match ($billingCycle) {
            UserSubscription::CYCLE_MONTHLY => $startDate->copy()->addMonth(),
            UserSubscription::CYCLE_ANNUAL => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth(),
        };
    }

    /**
     * Cancel all active subscriptions for a user.
     */
    private function cancelAllActiveSubscriptions(User $user): void
    {
        UserSubscription::where('user_id', $user->id)
            ->where('status', UserSubscription::STATUS_ACTIVE)
            ->update([
                'status' => UserSubscription::STATUS_CANCELLED,
                'cancelled_at' => Carbon::now(),
            ]);
    }

    /**
     * Handle payment captured event.
     */
    private function handlePaymentCaptured(array $payload): void
    {
        $payment = $payload['payload']['payment']['entity'] ?? [];
        $orderId = $payment['order_id'] ?? null;
        $paymentId = $payment['id'] ?? null;

        if (!$orderId) {
            return;
        }

        // Find subscription by order ID and update with payment ID
        $subscription = UserSubscription::where('razorpay_order_id', $orderId)->first();

        if ($subscription) {
            $subscription->razorpay_payment_id = $paymentId;
            $subscription->status = UserSubscription::STATUS_ACTIVE;
            $subscription->save();

            Log::info("Subscription activated for order {$orderId}");
        }
    }

    /**
     * Handle payment failed event.
     */
    private function handlePaymentFailed(array $payload): void
    {
        $payment = $payload['payload']['payment']['entity'] ?? [];
        $orderId = $payment['order_id'] ?? null;

        if (!$orderId) {
            return;
        }

        // Find subscription by order ID and mark as cancelled
        $subscription = UserSubscription::where('razorpay_order_id', $orderId)->first();

        if ($subscription && $subscription->status !== UserSubscription::STATUS_ACTIVE) {
            $subscription->status = UserSubscription::STATUS_CANCELLED;
            $subscription->save();

            Log::info("Subscription cancelled due to payment failure for order {$orderId}");
        }
    }

    /**
     * Handle subscription activated event.
     */
    private function handleSubscriptionActivated(array $payload): void
    {
        $subscriptionData = $payload['payload']['subscription']['entity'] ?? [];
        $subscriptionId = $subscriptionData['id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = UserSubscription::where('razorpay_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            $subscription->status = UserSubscription::STATUS_ACTIVE;
            $subscription->save();
        }
    }

    /**
     * Handle subscription cancelled event.
     */
    private function handleSubscriptionCancelled(array $payload): void
    {
        $subscriptionData = $payload['payload']['subscription']['entity'] ?? [];
        $subscriptionId = $subscriptionData['id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = UserSubscription::where('razorpay_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            $subscription->status = UserSubscription::STATUS_CANCELLED;
            $subscription->cancelled_at = Carbon::now();
            $subscription->save();
        }
    }

    /**
     * Handle subscription charged event (renewal).
     */
    private function handleSubscriptionCharged(array $payload): void
    {
        $subscriptionData = $payload['payload']['subscription']['entity'] ?? [];
        $subscriptionId = $subscriptionData['id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = UserSubscription::where('razorpay_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            // Extend the subscription
            $subscription->expires_at = $this->calculateExpiryDate(
                $subscription->billing_cycle,
                $subscription->expires_at
            );
            $subscription->save();

            Log::info("Subscription renewed for subscription {$subscriptionId}");
        }
    }
}
