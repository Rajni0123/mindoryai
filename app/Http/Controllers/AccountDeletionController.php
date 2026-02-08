<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AccountDeletionController extends Controller
{
    /**
     * Handle account deletion request
     */
    public function request(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
            'reason' => 'nullable|string',
            'feedback' => 'nullable|string|max:1000',
            'confirm' => 'required|accepted',
        ]);

        $mobile = preg_replace('/[^0-9]/', '', $request->mobile);

        // Find user by mobile
        $user = User::where('mobile', $mobile)
            ->orWhere('mobile', '+91' . $mobile)
            ->orWhere('mobile', '91' . $mobile)
            ->first();

        if (!$user) {
            return back()->with('error', 'No account found with this mobile number. Please check and try again.');
        }

        // Log the deletion request
        Log::info('Account deletion requested', [
            'user_id' => $user->id,
            'mobile' => $mobile,
            'reason' => $request->reason,
            'feedback' => $request->feedback,
        ]);

        // Store deletion request in database or send email to admin
        try {
            // Send notification to admin
            $adminEmail = config('mail.admin_email', 'support@blinkstudy.in');

            Mail::raw(
                "Account Deletion Request\n\n" .
                "User ID: {$user->id}\n" .
                "Name: {$user->name}\n" .
                "Mobile: {$user->mobile}\n" .
                "Email: {$user->email}\n" .
                "Reason: " . ($request->reason ?? 'Not specified') . "\n" .
                "Feedback: " . ($request->feedback ?? 'None') . "\n\n" .
                "Please process this deletion request within 30 days.",
                function ($message) use ($adminEmail) {
                    $message->to($adminEmail)
                        ->subject('Account Deletion Request - BlinkStudy');
                }
            );
        } catch (\Exception $e) {
            Log::error('Failed to send deletion request email', ['error' => $e->getMessage()]);
        }

        // Mark user for deletion (optional - add a deleted_at or deletion_requested_at field)
        $user->update([
            'deletion_requested_at' => now(),
        ]);

        return back()->with('success', 'Your account deletion request has been submitted. Your account and data will be deleted within 30 days. You will receive a confirmation once completed.');
    }
}
