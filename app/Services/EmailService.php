<?php

namespace App\Services;

use App\Mail\AdminAlertMail;
use App\Mail\PaymentFailedMail;
use App\Mail\PaymentSuccessMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Send welcome email to new user + notify admin
     */
    public static function sendWelcome(User $user): void
    {
        // Send to user
        if ($user->email) {
            self::safeSend($user->email, new WelcomeMail($user));
        }

        // Notify admin
        self::sendAdminAlert('new_user', 'New User Registered', [
            'Name' => $user->name ?? 'N/A',
            'Email' => $user->email ?? 'N/A',
            'Phone' => $user->mobile ? '+91 ' . $user->mobile : 'N/A',
            'Login Method' => $user->password ? 'Email/Password' : 'OTP/Social',
            'Registered At' => now()->format('d M Y, h:i A'),
        ]);
    }

    /**
     * Send payment success email to user + notify admin
     */
    public static function sendPaymentSuccess(User $user, string $planName, string $amount, string $transactionId, string $paymentMethod, string $expiryDate): void
    {
        // Send to user
        if ($user->email) {
            self::safeSend($user->email, new PaymentSuccessMail(
                $user, $planName, $amount, $transactionId, $paymentMethod, $expiryDate
            ));
        }

        // Notify admin
        self::sendAdminAlert('payment_success', 'Payment Received - ' . $planName, [
            'User' => $user->name ?? 'N/A',
            'Email' => $user->email ?? 'N/A',
            'Phone' => $user->mobile ? '+91 ' . $user->mobile : 'N/A',
            'Plan' => $planName,
            'Amount' => '₹' . $amount,
            'Transaction ID' => $transactionId,
            'Payment Method' => ucfirst($paymentMethod),
            'Valid Until' => $expiryDate,
        ]);
    }

    /**
     * Send payment failed email to user + notify admin with full data
     */
    public static function sendPaymentFailed(User $user, string $planName, string $amount, string $transactionId, string $reason = 'Payment could not be processed'): void
    {
        // Send to user
        if ($user->email) {
            self::safeSend($user->email, new PaymentFailedMail(
                $user, $planName, $amount, $transactionId, $reason
            ));
        }

        // Notify admin with full user data
        self::sendAdminAlert('payment_failed', 'Payment Failed - ₹' . $amount, [
            'User' => $user->name ?? 'N/A',
            'User ID' => '#' . $user->id,
            'Email' => $user->email ?? 'N/A',
            'Phone' => $user->mobile ? '+91 ' . $user->mobile : 'N/A',
            'Plan' => $planName,
            'Amount' => '₹' . $amount,
            'Transaction ID' => $transactionId,
            'Failure Reason' => $reason,
            'Current Plan' => $user->userPlan->name ?? 'Free',
            'Failed At' => now()->format('d M Y, h:i A'),
        ]);
    }

    /**
     * Send plan expired notification to user + notify admin
     */
    public static function sendPlanExpired($user, string $planName): void
    {
        // Send to user
        if ($user->email) {
            self::safeSend($user->email, new \App\Mail\PlanExpiredMail($user, $planName));
        }

        // Notify admin
        self::sendAdminAlert('plan_expired', 'Plan Expired - ' . $planName, [
            'User' => $user->name ?? 'N/A',
            'Email' => $user->email ?? 'N/A',
            'Plan' => $planName,
            'Status' => 'Downgraded to Free',
            'Expired At' => now()->format('d M Y, h:i A'),
        ]);
    }

    /**
     * Send admin alert email
     */
    public static function sendAdminAlert(string $type, string $title, array $details): void
    {
        $adminEmail = config('services.admin_email');
        if ($adminEmail) {
            self::safeSend($adminEmail, new AdminAlertMail($type, $title, $details));
        }
    }

    /**
     * Safe send - catches exceptions so mail failures don't break the app
     */
    private static function safeSend(string $to, $mailable): void
    {
        try {
            Mail::to($to)->send($mailable);
            Log::info('[EmailService] Sent ' . class_basename($mailable) . ' to ' . $to);
        } catch (\Exception $e) {
            Log::error('[EmailService] Failed to send ' . class_basename($mailable) . ' to ' . $to, [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
