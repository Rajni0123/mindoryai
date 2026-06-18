<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\EmailService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display all payments
     */
    public function index()
    {
        $payments = Payment::with(['user', 'plan'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.payments.index', compact('payments'));
    }

    /**
     * Confirm payment and activate user
     */
    public function confirm($paymentId)
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

        // Send payment success email to user
        EmailService::sendPaymentSuccess(
            $user,
            $plan->name ?? 'Premium',
            (string) $payment->amount,
            $payment->transaction_id,
            $payment->payment_method ?? 'manual',
            $expiryDate->format('d M Y')
        );

        return back()->with('success', 'Payment confirmed and user activated successfully!');
    }

    /**
     * Reject payment
     */
    public function reject($paymentId)
    {
        $payment = Payment::with('user', 'plan')->findOrFail($paymentId);

        $payment->update([
            'status' => 'failed',
        ]);

        // Send payment failed email to user
        $user = $payment->user;
        $plan = $payment->plan;
        if ($user) {
            EmailService::sendPaymentFailed(
                $user,
                $plan->name ?? 'Unknown Plan',
                (string) $payment->amount,
                $payment->transaction_id,
                'Payment was rejected by admin. Contact support for details.'
            );
        }

        return back()->with('success', 'Payment rejected.');
    }
}
