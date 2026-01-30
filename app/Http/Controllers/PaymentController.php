<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Show payment page for selected plan
     */
    public function showPayment($planId)
    {
        $user = Auth::user();

        // Get the pricing plan
        $plan = PricingPlan::findOrFail($planId);

        // Get enabled payment gateways
        $paymentGateways = \App\Models\PaymentGateway::where('is_enabled', true)
            ->orderBy('sort_order')
            ->get();

        // Check if any payment gateway is enabled
        if ($paymentGateways->isEmpty()) {
            return back()->with('error', 'No payment methods available. Please contact administrator.');
        }

        // Create payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'transaction_id' => 'TXN' . strtoupper(Str::random(16)),
            'payment_method' => 'pending',
            'status' => 'pending',
        ]);

        return view('payment.checkout', compact('plan', 'payment', 'paymentGateways'));
    }

    /**
     * Verify payment manually (admin will verify)
     */
    public function verifyPayment(Request $request, $paymentId)
    {
        $validated = $request->validate([
            'transaction_ref' => 'required|string',
        ]);

        $payment = Payment::findOrFail($paymentId);

        // Check if payment belongs to authenticated user
        if ($payment->user_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized access.');
        }

        // Update payment with user-provided transaction reference
        $payment->update([
            'upi_transaction_id' => $validated['transaction_ref'],
            'status' => 'verifying',
        ]);

        return redirect()->route('payment.status', $payment->id)
            ->with('success', 'Payment submitted for verification. Admin will verify and activate your account within 24 hours.');
    }

    /**
     * Show payment status
     */
    public function paymentStatus($paymentId)
    {
        $payment = Payment::with('plan')->findOrFail($paymentId);

        // Check if payment belongs to authenticated user
        if ($payment->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('payment.status', compact('payment'));
    }

    /**
     * Admin: Confirm payment and activate user
     */
    public function confirmPayment($paymentId)
    {
        $payment = Payment::with('user', 'plan')->findOrFail($paymentId);

        // Update payment status
        $payment->update([
            'status' => 'completed',
            'verified_at' => now(),
        ]);

        // Activate user with plan details
        $plan = $payment->plan;
        $user = $payment->user;

        $user->update([
            'plan_id' => $plan->id,
            'is_active' => true,
            'token_limit' => $plan->message_tokens ?? 10000,
            'tokens_used' => 0,
            'can_use_gpt4' => (bool) ($plan->can_use_gpt4 ?? false),
            'can_use_claude' => (bool) ($plan->can_use_claude ?? false),
            'can_use_deepseek' => (bool) ($plan->can_use_deepseek ?? false),
            'can_use_grok' => (bool) ($plan->can_use_grok ?? false),
        ]);

        return back()->with('success', 'Payment confirmed and user activated successfully!');
    }

    /**
     * Create Cashfree payment order
     */
    public function createCashfreeOrder(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:user_plans,id',
        ]);

        $user = Auth::user();
        $plan = PricingPlan::findOrFail($request->plan_id);

        try {
            // Generate unique order ID
            $orderId = 'ORDER_' . $user->id . '_' . time();

            // Get Cashfree config from services
            $appId = config('services.cashfree.app_id');
            $secretKey = config('services.cashfree.secret_key');

            if (!$appId || !$secretKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment gateway not configured',
                ], 500);
            }

            // Store order details in database
            $payment = Payment::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $plan->price,
                'transaction_id' => $orderId,
                'payment_method' => 'cashfree',
                'status' => 'pending',
            ]);

            // Create payment link using Cashfree
            $callbackUrl = route('payment.cashfree.callback');

            // For now, return a simplified response
            // In production, you'll use Cashfree SDK to generate the payment link
            $paymentUrl = "https://payments.cashfree.com/order/#{$orderId}";

            return response()->json([
                'success' => true,
                'payment_url' => $paymentUrl,
                'order_id' => $orderId,
                'amount' => $plan->price,
                'callback_url' => $callbackUrl,
            ]);

        } catch (\Exception $e) {
            \Log::error('Cashfree order creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment order. Please try again.',
            ], 500);
        }
    }

    /**
     * Cashfree payment callback
     */
    public function cashfreeCallback(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $txStatus = $request->input('txStatus');
            $referenceId = $request->input('referenceId');

            // Find payment
            $payment = Payment::where('transaction_id', $orderId)->firstOrFail();

            if ($txStatus === 'SUCCESS') {
                // Update payment status
                $payment->update([
                    'status' => 'completed',
                    'upi_transaction_id' => $referenceId,
                    'verified_at' => now(),
                ]);

                // Activate user subscription
                $plan = $payment->plan;
                $user = $payment->user;

                $user->update([
                    'plan_id' => $plan->id,
                    'is_active' => true,
                    'token_limit' => $plan->message_tokens ?? 10000,
                    'tokens_used' => 0,
                    'can_use_gpt4' => (bool) ($plan->can_use_gpt4 ?? false),
                    'can_use_claude' => (bool) ($plan->can_use_claude ?? false),
                    'can_use_deepseek' => (bool) ($plan->can_use_deepseek ?? false),
                    'can_use_grok' => (bool) ($plan->can_use_grok ?? false),
                ]);

                return redirect()->route('home')
                    ->with('success', 'Payment successful! Your subscription is now active.');

            } else {
                // Payment failed
                $payment->update(['status' => 'failed']);

                return redirect()->route('home')
                    ->with('error', 'Payment failed. Please try again.');
            }

        } catch (\Exception $e) {
            \Log::error('Cashfree callback processing failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('home')
                ->with('error', 'Payment processing failed. Please contact support.');
        }
    }

    /**
     * API: Get enabled payment gateways
     */
    public function getEnabledGateways()
    {
        $gateways = PaymentGateway::where('is_enabled', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'display_name', 'logo_url']);

        return response()->json([
            'success' => true,
            'gateways' => $gateways,
        ]);
    }

    /**
     * API: Create payment order for any gateway
     */
    public function createPaymentOrder(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:user_plans,id',
            'gateway' => 'required|in:razorpay,cashfree,phonepe',
        ]);

        $user = Auth::user();
        $plan = PricingPlan::findOrFail($request->plan_id);

        // Check if gateway is enabled
        $gateway = PaymentGateway::where('name', $request->gateway)
            ->where('is_enabled', true)
            ->first();

        if (!$gateway) {
            return response()->json([
                'success' => false,
                'message' => 'Payment gateway is not available',
            ], 400);
        }

        // Create payment order using PaymentService
        $paymentService = new PaymentService();
        $result = $paymentService->createOrder(
            $request->gateway,
            $plan->price,
            'plan_purchase_' . $plan->id,
            $user->id,
            ['plan_id' => $plan->id]
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'transaction_id' => $result['transaction_id'],
            'gateway_order_id' => $result['gateway_order_id'],
            'gateway_data' => $result['gateway_data'],
            'amount' => $plan->price,
        ]);
    }

    /**
     * API: Verify payment
     */
    public function verifyPaymentOrder(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'payment_data' => 'required|array',
        ]);

        $paymentService = new PaymentService();
        $result = $paymentService->verifyPayment(
            $request->transaction_id,
            $request->payment_data
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        // Find transaction and activate user plan
        $transaction = \App\Models\Transaction::where('transaction_id', $request->transaction_id)->first();

        if ($transaction && isset($transaction->metadata['plan_id'])) {
            $plan = PricingPlan::find($transaction->metadata['plan_id']);
            $user = Auth::user();

            if ($plan && $user) {
                $user->update([
                    'plan_id' => $plan->id,
                    'is_active' => true,
                    'token_limit' => $plan->message_tokens ?? 10000,
                    'tokens_used' => 0,
                    'can_use_gpt4' => (bool) ($plan->can_use_gpt4 ?? false),
                    'can_use_claude' => (bool) ($plan->can_use_claude ?? false),
                    'can_use_deepseek' => (bool) ($plan->can_use_deepseek ?? false),
                    'can_use_grok' => (bool) ($plan->can_use_grok ?? false),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment verified successfully',
        ]);
    }

    /**
     * API: Gateway-specific payment callbacks
     */
    public function gatewayCallback(Request $request, $gateway)
    {
        try {
            $transactionId = $request->input('transaction_id') ?? $request->input('order_id');

            if (!$transactionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction ID not found',
                ], 400);
            }

            $paymentService = new PaymentService();
            $result = $paymentService->verifyPayment($transactionId, $request->all());

            if ($result['success']) {
                // Find transaction and activate user plan
                $transaction = \App\Models\Transaction::where('transaction_id', $transactionId)->first();

                if ($transaction && isset($transaction->metadata['plan_id'])) {
                    $plan = PricingPlan::find($transaction->metadata['plan_id']);
                    $user = $transaction->user;

                    if ($plan && $user) {
                        $user->update([
                            'plan_id' => $plan->id,
                            'is_active' => true,
                            'token_limit' => $plan->message_tokens ?? 10000,
                            'tokens_used' => 0,
                            'can_use_gpt4' => (bool) ($plan->can_use_gpt4 ?? false),
                            'can_use_claude' => (bool) ($plan->can_use_claude ?? false),
                            'can_use_deepseek' => (bool) ($plan->can_use_deepseek ?? false),
                            'can_use_grok' => (bool) ($plan->can_use_grok ?? false),
                        ]);
                    }
                }
            }

            // For web callbacks, redirect back
            if ($request->has('redirect')) {
                return redirect()->route('home')
                    ->with($result['success'] ? 'success' : 'error', $result['message']);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('Payment callback processing failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
            ], 500);
        }
    }
}
