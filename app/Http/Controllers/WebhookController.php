<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\Payment;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Razorpay webhook events
     * Endpoint: POST /webhooks/razorpay
     */
    public function razorpay(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        // Get Razorpay gateway config
        $gateway = PaymentGateway::where('name', 'razorpay')->first();

        if (!$gateway) {
            Log::warning('Razorpay webhook: Gateway not configured');
            return response()->json(['error' => 'Gateway not configured'], 400);
        }

        // Verify webhook signature
        $webhookSecret = $gateway->webhook_secret ?? $gateway->api_secret;
        if ($webhookSecret && $signature) {
            $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Razorpay webhook: Invalid signature', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        $event = $request->input('event');
        $paymentEntity = $request->input('payload.payment.entity', []);

        Log::info('Razorpay webhook received', [
            'event' => $event,
            'order_id' => $paymentEntity['order_id'] ?? null,
            'payment_id' => $paymentEntity['id'] ?? null,
        ]);

        switch ($event) {
            case 'payment.captured':
                return $this->handlePaymentCaptured($paymentEntity);

            case 'payment.failed':
                return $this->handlePaymentFailed($paymentEntity);

            case 'refund.processed':
                return $this->handleRefundProcessed($request->input('payload.refund.entity', []));

            default:
                Log::info('Razorpay webhook: Unhandled event', ['event' => $event]);
                return response()->json(['status' => 'ignored']);
        }
    }

    /**
     * Handle payment.captured event
     */
    private function handlePaymentCaptured(array $payment)
    {
        $orderId = $payment['order_id'] ?? null;
        $paymentId = $payment['id'] ?? null;

        if (!$orderId) {
            return response()->json(['error' => 'Missing order_id'], 400);
        }

        // Find transaction by gateway_order_id
        $transaction = Transaction::where('gateway_order_id', $orderId)->first();

        if (!$transaction) {
            Log::warning('Razorpay webhook: Transaction not found', ['order_id' => $orderId]);
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        // Idempotency check - already processed
        if ($transaction->status === 'completed') {
            Log::info('Razorpay webhook: Payment already processed', ['order_id' => $orderId]);
            return response()->json(['status' => 'already_processed']);
        }

        // Mark transaction as completed
        $transaction->markAsCompleted($paymentId, $payment);

        // Activate user plan
        $planId = $transaction->metadata['plan_id'] ?? null;
        if ($planId) {
            $plan = PricingPlan::find($planId);
            $user = $transaction->user;

            if ($plan && $user) {
                $expiryDate = now()->addDays($plan->validity_days ?? 30);

                $user->update([
                    'plan_id' => $plan->id,
                    'plan_expires_at' => $expiryDate,
                    'is_active' => true,
                    'token_limit' => $plan->message_tokens ?? 10000,
                    'tokens_used' => 0,
                    'can_use_gpt4' => (bool) ($plan->can_use_gpt4 ?? false),
                    'can_use_claude' => (bool) ($plan->can_use_claude ?? false),
                    'can_use_deepseek' => (bool) ($plan->can_use_deepseek ?? false),
                    'can_use_grok' => (bool) ($plan->can_use_grok ?? false),
                ]);

                // Create Payment record
                Payment::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'amount' => $transaction->amount,
                    'transaction_id' => $paymentId,
                    'payment_method' => 'razorpay',
                    'status' => 'completed',
                    'upi_transaction_id' => $orderId,
                    'verified_at' => now(),
                ]);

                // Log subscription event
                $this->logSubscriptionEvent($user->id, 'purchase', [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'amount' => $transaction->amount,
                    'payment_id' => $paymentId,
                    'expires_at' => $expiryDate->toDateTimeString(),
                ]);

                // Send success email
                EmailService::sendPaymentSuccess(
                    $user,
                    $plan->name ?? 'Premium',
                    (string) $transaction->amount,
                    $paymentId,
                    'razorpay',
                    $expiryDate->format('d M Y')
                );

                Log::info('Razorpay webhook: Plan activated', [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'payment_id' => $paymentId,
                ]);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle payment.failed event
     */
    private function handlePaymentFailed(array $payment)
    {
        $orderId = $payment['order_id'] ?? null;
        $errorCode = $payment['error_code'] ?? 'unknown';
        $errorDescription = $payment['error_description'] ?? 'Payment failed';

        if (!$orderId) {
            return response()->json(['error' => 'Missing order_id'], 400);
        }

        $transaction = Transaction::where('gateway_order_id', $orderId)->first();

        if ($transaction) {
            $transaction->markAsFailed([
                'error_code' => $errorCode,
                'error_description' => $errorDescription,
            ]);

            // Send failure notification
            $planId = $transaction->metadata['plan_id'] ?? null;
            if ($planId) {
                $plan = PricingPlan::find($planId);
                $user = $transaction->user;

                if ($plan && $user) {
                    EmailService::sendPaymentFailed(
                        $user,
                        $plan->name ?? 'Premium',
                        (string) $transaction->amount,
                        $transaction->transaction_id,
                        $errorDescription
                    );
                }
            }

            Log::info('Razorpay webhook: Payment failed', [
                'order_id' => $orderId,
                'error' => $errorDescription,
            ]);
        }

        return response()->json(['status' => 'logged']);
    }

    /**
     * Handle refund.processed event
     */
    private function handleRefundProcessed(array $refund)
    {
        $paymentId = $refund['payment_id'] ?? null;
        $refundAmount = ($refund['amount'] ?? 0) / 100; // Convert from paise

        if (!$paymentId) {
            return response()->json(['error' => 'Missing payment_id'], 400);
        }

        // Find payment and transaction
        $payment = Payment::where('transaction_id', $paymentId)->first();

        if ($payment) {
            $payment->update([
                'status' => 'refunded',
            ]);

            $user = $payment->user;
            if ($user) {
                // Downgrade to free plan
                $user->update([
                    'plan_id' => null,
                    'plan_expires_at' => null,
                ]);

                // Log refund
                $this->logSubscriptionEvent($user->id, 'refund', [
                    'payment_id' => $paymentId,
                    'refund_amount' => $refundAmount,
                    'plan_id' => $payment->plan_id,
                ]);

                Log::info('Razorpay webhook: Refund processed, user downgraded', [
                    'user_id' => $user->id,
                    'payment_id' => $paymentId,
                    'refund_amount' => $refundAmount,
                ]);
            }
        }

        return response()->json(['status' => 'refund_processed']);
    }

    /**
     * Log subscription event
     */
    private function logSubscriptionEvent(int $userId, string $event, array $data): void
    {
        try {
            DB::table('subscription_logs')->insert([
                'user_id' => $userId,
                'event' => $event,
                'data' => json_encode($data),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::info("Subscription event: {$event}", array_merge(['user_id' => $userId], $data));
        }
    }
}
