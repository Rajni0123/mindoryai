<?php

namespace App\Services;

use App\Models\AutopayTrial;
use App\Models\PaymentGateway;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class TrialAutopayService
{
    protected ?Api $razorpay = null;

    public function getOffer(User $user): array
    {
        $eligible = $this->isEligible($user);
        $reason = $eligible ? null : $this->getIneligibilityReason($user);

        return [
            'enabled' => (bool) config('trial.enabled'),
            'eligible' => $eligible,
            'reason' => $reason,
            'trial_price' => config('trial.price'),
            'trial_days' => config('trial.days'),
            'renewal_price' => config('trial.renewal_price'),
            'renewal_period' => 'monthly',
            'plan_slug' => config('trial.plan_slug'),
            'headline' => config('trial.offer.headline'),
            'subline' => config('trial.offer.subline'),
            'tag' => config('trial.offer.tag'),
            'autopay_required' => true,
            'cancel_anytime' => true,
            'ui' => config('trial.ui'),
        ];
    }

    public function isEligible(User $user): bool
    {
        if (! config('trial.enabled')) {
            return false;
        }

        if ($user->role === 'admin') {
            return false;
        }

        if ($user->trial_used_at) {
            return false;
        }

        if ($user->plan_id && $user->plan_expires_at && Carbon::parse($user->plan_expires_at)->isFuture()) {
            return false;
        }

        if (AutopayTrial::where('user_id', $user->id)
            ->whereIn('status', [AutopayTrial::STATUS_PENDING, AutopayTrial::STATUS_TRIAL, AutopayTrial::STATUS_ACTIVE])
            ->exists()) {
            return false;
        }

        return (bool) $this->getRazorpayPlanId();
    }

    public function getIneligibilityReason(User $user): string
    {
        if (! config('trial.enabled')) {
            return 'Trial offer is currently unavailable.';
        }

        if ($user->trial_used_at) {
            return 'You have already used the trial offer.';
        }

        if ($user->plan_id && $user->plan_expires_at && Carbon::parse($user->plan_expires_at)->isFuture()) {
            return 'You already have an active subscription.';
        }

        if (! $this->getRazorpayPlanId()) {
            return 'Trial is not configured yet. Please contact support.';
        }

        return 'Trial offer is not available for your account.';
    }

    /**
     * Create Razorpay subscription: ₹1 addon now + Lite autopay after trial_days.
     */
    public function startTrial(User $user): array
    {
        if (! $this->isEligible($user)) {
            throw new \RuntimeException($this->getIneligibilityReason($user));
        }

        $planId = $this->getRazorpayPlanId();
        $trialDays = (int) config('trial.days');
        $startAt = Carbon::now()->addDays($trialDays)->timestamp;

        $subscription = $this->api()->subscription->create([
            'plan_id' => $planId,
            'total_count' => (int) config('trial.total_billing_cycles'),
            'quantity' => 1,
            'customer_notify' => 1,
            'start_at' => $startAt,
            'addons' => [
                [
                    'item' => [
                        'name' => config('trial.addon_name'),
                        'amount' => (int) config('trial.price') * 100,
                        'currency' => 'INR',
                    ],
                ],
            ],
            'notes' => [
                'user_id' => (string) $user->id,
                'type' => 'trial_autopay',
                'plan_slug' => config('trial.plan_slug'),
            ],
        ]);

        $trial = AutopayTrial::create([
            'user_id' => $user->id,
            'razorpay_subscription_id' => $subscription->id,
            'status' => AutopayTrial::STATUS_PENDING,
            'plan_slug' => config('trial.plan_slug'),
            'trial_price' => config('trial.price'),
            'renewal_price' => config('trial.renewal_price'),
            'next_billing_at' => Carbon::createFromTimestamp($startAt),
            'meta' => [
                'razorpay_status' => $subscription->status ?? null,
            ],
        ]);

        Log::info('Trial autopay subscription created', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
        ]);

        return [
            'subscription_id' => $subscription->id,
            'razorpay_key' => config('services.razorpay.key'),
            'trial_id' => $trial->id,
            'trial_price' => config('trial.price'),
            'trial_days' => $trialDays,
            'renewal_price' => config('trial.renewal_price'),
            'next_billing_at' => Carbon::createFromTimestamp($startAt)->toIso8601String(),
            'short_url' => $subscription->short_url ?? null,
        ];
    }

    /**
     * Verify subscription auth after Razorpay checkout (mobile callback).
     */
    public function verifySubscriptionAuth(User $user, string $subscriptionId, string $paymentId, string $signature): array
    {
        $expected = hash_hmac(
            'sha256',
            $paymentId . '|' . $subscriptionId,
            config('services.razorpay.secret')
        );

        if (! hash_equals($expected, $signature)) {
            throw new \RuntimeException('Invalid payment signature.');
        }

        $trial = AutopayTrial::where('razorpay_subscription_id', $subscriptionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->activateTrialPeriod($trial, $paymentId);

        return $this->formatTrialStatus($trial->fresh());
    }

    /**
     * Cancel Razorpay autopay (immediate).
     */
    public function cancelAutopay(User $user): bool
    {
        $trial = AutopayTrial::where('user_id', $user->id)
            ->whereIn('status', [AutopayTrial::STATUS_PENDING, AutopayTrial::STATUS_TRIAL, AutopayTrial::STATUS_ACTIVE])
            ->latest('id')
            ->first();

        if (! $trial) {
            return false;
        }

        try {
            $this->api()->subscription->cancel($trial->razorpay_subscription_id, [
                'cancel_at_cycle_end' => 0,
            ]);
        } catch (\Exception $e) {
            Log::warning('Razorpay subscription cancel API failed', [
                'subscription_id' => $trial->razorpay_subscription_id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->markCancelled($trial);

        return true;
    }

    public function getStatus(User $user): array
    {
        $trial = AutopayTrial::where('user_id', $user->id)->latest('id')->first();

        if (! $trial) {
            return [
                'has_trial' => false,
                'offer' => $this->getOffer($user),
            ];
        }

        return array_merge(['has_trial' => true], $this->formatTrialStatus($trial));
    }

    /**
     * Handle Razorpay subscription webhook events.
     */
    public function handleWebhookEvent(string $event, array $payload): void
    {
        $entity = $payload['payload']['subscription']['entity'] ?? [];
        $subscriptionId = $entity['id'] ?? null;

        if (! $subscriptionId) {
            return;
        }

        $trial = AutopayTrial::where('razorpay_subscription_id', $subscriptionId)->first();

        if (! $trial) {
            Log::info('Trial webhook: no autopay_trials record', ['subscription_id' => $subscriptionId, 'event' => $event]);
            return;
        }

        match ($event) {
            'subscription.authenticated', 'subscription.activated' => $this->activateTrialPeriod($trial),
            'subscription.charged' => $this->handleRenewalCharged($trial, $payload),
            'subscription.cancelled' => $this->markCancelled($trial),
            'subscription.halted', 'subscription.pending' => $this->handleHalted($trial, $entity),
            default => Log::info('Trial webhook ignored', ['event' => $event, 'subscription_id' => $subscriptionId]),
        };
    }

    /**
     * Create or fetch Razorpay monthly plan for Lite (run once via artisan).
     */
    public function ensureRazorpayPlan(): string
    {
        $existing = config('trial.razorpay_plan_id');
        if ($existing) {
            return $existing;
        }

        $plan = $this->api()->plan->create([
            'period' => 'monthly',
            'interval' => 1,
            'item' => [
                'name' => 'BlinkStudy Lite Monthly',
                'amount' => (int) config('trial.renewal_price') * 100,
                'currency' => 'INR',
                'description' => 'Lite plan — auto-renewal after trial',
            ],
        ]);

        Log::info('Created Razorpay plan for trial autopay', ['plan_id' => $plan->id]);

        return $plan->id;
    }

    protected function activateTrialPeriod(AutopayTrial $trial, ?string $paymentId = null): void
    {
        if (in_array($trial->status, [AutopayTrial::STATUS_TRIAL, AutopayTrial::STATUS_ACTIVE], true)) {
            return;
        }

        DB::transaction(function () use ($trial, $paymentId) {
            $user = $trial->user;
            $trialDays = (int) config('trial.days');
            $startsAt = now();
            $endsAt = now()->addDays($trialDays);

            $trial->update([
                'status' => AutopayTrial::STATUS_TRIAL,
                'trial_starts_at' => $startsAt,
                'trial_ends_at' => $endsAt,
                'meta' => array_merge($trial->meta ?? [], [
                    'auth_payment_id' => $paymentId,
                    'activated_at' => now()->toIso8601String(),
                ]),
            ]);

            $user->update([
                'trial_used_at' => now(),
                'razorpay_subscription_id' => $trial->razorpay_subscription_id,
            ]);

            $this->syncUserPlanAccess($user, $trial->plan_slug, $endsAt, true, $trial);

            Log::info('Trial activated', [
                'user_id' => $user->id,
                'subscription_id' => $trial->razorpay_subscription_id,
                'expires_at' => $endsAt->toDateTimeString(),
            ]);
        });

        Cache::forget("user_plan_{$trial->user_id}");
    }

    protected function handleRenewalCharged(AutopayTrial $trial, array $payload): void
    {
        $payment = $payload['payload']['payment']['entity'] ?? [];
        $amountPaise = (int) ($payment['amount'] ?? 0);
        $amountInr = $amountPaise / 100;

        // Skip if this is only the ₹1 addon (first charge during auth)
        if ($amountInr <= (int) config('trial.price')) {
            Log::info('Trial webhook: skipping addon charge for renewal handler', [
                'subscription_id' => $trial->razorpay_subscription_id,
                'amount' => $amountInr,
            ]);
            return;
        }

        DB::transaction(function () use ($trial, $amountInr) {
            $user = $trial->user;
            $expiresAt = now()->addMonth();

            $trial->update([
                'status' => AutopayTrial::STATUS_ACTIVE,
                'trial_ends_at' => $trial->trial_ends_at ?? now(),
                'next_billing_at' => $expiresAt,
            ]);

            $this->syncUserPlanAccess($user, $trial->plan_slug, $expiresAt, false, $trial);

            Log::info('Trial converted to paid autopay', [
                'user_id' => $user->id,
                'amount' => $amountInr,
                'expires_at' => $expiresAt->toDateTimeString(),
            ]);
        });

        Cache::forget("user_plan_{$trial->user_id}");
    }

    protected function markCancelled(AutopayTrial $trial): void
    {
        $trial->update([
            'status' => AutopayTrial::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        $user = $trial->user;
        if ($user->razorpay_subscription_id === $trial->razorpay_subscription_id) {
            $user->update(['razorpay_subscription_id' => null]);
        }

        // Keep access until plan_expires_at — do not revoke immediately if paid period remains
        if (! $user->plan_expires_at || Carbon::parse($user->plan_expires_at)->isPast()) {
            $user->update(['plan_id' => null, 'plan_expires_at' => null]);
        }

        DB::table('user_subscriptions')
            ->where('user_id', $user->id)
            ->where('razorpay_subscription_id', $trial->razorpay_subscription_id)
            ->update([
                'auto_renew' => false,
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);

        Cache::forget("user_plan_{$user->id}");
    }

    protected function handleHalted(AutopayTrial $trial, array $entity): void
    {
        $trial->update([
            'status' => AutopayTrial::STATUS_HALTED,
            'meta' => array_merge($trial->meta ?? [], ['halted' => $entity]),
        ]);

        $user = $trial->user;

        // After trial ends and payment fails — remove access
        if (! $trial->trial_ends_at || $trial->trial_ends_at->isPast()) {
            $user->update([
                'plan_id' => null,
                'plan_expires_at' => null,
                'razorpay_subscription_id' => null,
            ]);

            DB::table('user_subscriptions')
                ->where('user_id', $user->id)
                ->where('razorpay_subscription_id', $trial->razorpay_subscription_id)
                ->update(['status' => 'expired', 'updated_at' => now()]);
        }

        Cache::forget("user_plan_{$user->id}");
    }

    protected function syncUserPlanAccess(User $user, string $planSlug, Carbon $expiresAt, bool $isTrial, AutopayTrial $trial): void
    {
        $userPlan = DB::table('user_plans')->where('slug', $planSlug)->where('is_active', true)->first();

        if (! $userPlan) {
            throw new \RuntimeException("Plan {$planSlug} not found in user_plans.");
        }

        $user->update([
            'plan_id' => $userPlan->id,
            'plan_expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        $subscriptionPayload = [
            'plan_id' => $userPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount_paid' => $isTrial ? config('trial.price') : config('trial.renewal_price'),
            'start_date' => now(),
            'end_date' => $expiresAt,
            'next_billing_date' => $expiresAt,
            'payment_method' => 'razorpay_autopay',
            'transaction_id' => $trial->razorpay_subscription_id,
            'razorpay_subscription_id' => $trial->razorpay_subscription_id,
            'auto_renew' => true,
            'is_trial' => $isTrial,
            'trial_ends_at' => $isTrial ? $expiresAt : $trial->trial_ends_at,
            'updated_at' => now(),
        ];

        $existing = DB::table('user_subscriptions')->where('user_id', $user->id)->first();

        if ($existing) {
            DB::table('user_subscriptions')->where('user_id', $user->id)->update($subscriptionPayload);
        } else {
            $subscriptionPayload['user_id'] = $user->id;
            $subscriptionPayload['created_at'] = now();
            DB::table('user_subscriptions')->insert($subscriptionPayload);
        }
    }

    protected function formatTrialStatus(AutopayTrial $trial): array
    {
        return [
            'trial_id' => $trial->id,
            'status' => $trial->status,
            'subscription_id' => $trial->razorpay_subscription_id,
            'plan_slug' => $trial->plan_slug,
            'trial_starts_at' => $trial->trial_starts_at?->toIso8601String(),
            'trial_ends_at' => $trial->trial_ends_at?->toIso8601String(),
            'next_billing_at' => $trial->next_billing_at?->toIso8601String(),
            'renewal_price' => $trial->renewal_price,
            'is_trial_active' => $trial->isTrialActive(),
            'can_cancel' => in_array($trial->status, [
                AutopayTrial::STATUS_PENDING,
                AutopayTrial::STATUS_TRIAL,
                AutopayTrial::STATUS_ACTIVE,
            ], true),
        ];
    }

    protected function getRazorpayPlanId(): ?string
    {
        $fromConfig = config('trial.razorpay_plan_id');
        if ($fromConfig) {
            return $fromConfig;
        }

        $gateway = PaymentGateway::where('name', 'razorpay')->where('is_enabled', true)->first();
        if ($gateway && ! empty($gateway->settings['lite_monthly_plan_id'])) {
            return $gateway->settings['lite_monthly_plan_id'];
        }

        return null;
    }

    protected function api(): Api
    {
        if ($this->razorpay === null) {
            $key = config('services.razorpay.key');
            $secret = config('services.razorpay.secret');

            if (! $key || ! $secret) {
                throw new \RuntimeException('Razorpay is not configured.');
            }

            $this->razorpay = new Api($key, $secret);
        }

        return $this->razorpay;
    }
}
