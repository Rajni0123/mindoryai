<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AccountDeletionRequest;
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

        // Check if already has pending request
        $existingRequest = AccountDeletionRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingRequest) {
            return back()->with('error', 'You already have a pending deletion request. Please wait for it to be processed.');
        }

        // Create deletion request
        $deletionRequest = AccountDeletionRequest::create([
            'user_id' => $user->id,
            'mobile' => $user->mobile,
            'reason' => $request->reason,
            'feedback' => $request->feedback,
            'status' => 'pending',
        ]);

        // Log the deletion request
        Log::info('Account deletion requested', [
            'request_id' => $deletionRequest->id,
            'user_id' => $user->id,
            'mobile' => $mobile,
            'reason' => $request->reason,
        ]);

        // Try to send email notification to admin
        try {
            $adminEmail = config('mail.admin_email', 'support@blinkstudy.in');
            Mail::raw(
                "Account Deletion Request #{$deletionRequest->id}\n\n" .
                "User ID: {$user->id}\n" .
                "Name: {$user->name}\n" .
                "Mobile: {$user->mobile}\n" .
                "Email: {$user->email}\n" .
                "Reason: " . ($request->reason ?? 'Not specified') . "\n" .
                "Feedback: " . ($request->feedback ?? 'None') . "\n\n" .
                "View in admin panel: " . url('/admin/deletion-requests'),
                function ($message) use ($adminEmail) {
                    $message->to($adminEmail)
                        ->subject('New Account Deletion Request - BlinkStudy');
                }
            );
        } catch (\Exception $e) {
            Log::error('Failed to send deletion request email', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Your account deletion request has been submitted. Your account and data will be deleted within 30 days. You will receive a confirmation once completed.');
    }
}
