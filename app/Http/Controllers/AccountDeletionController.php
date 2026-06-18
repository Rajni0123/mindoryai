<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AccountDeletionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            return back()->withInput()->with('error', 'No account found with this mobile number. Please check and try again.');
        }

        // Check if already has pending request
        $existingRequest = AccountDeletionRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingRequest) {
            return back()->withInput()->with('error', 'You already have a pending deletion request. Please wait for it to be processed.');
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

        return back()->with('success', 'Your account deletion request has been submitted successfully! Your account and data will be deleted within 30 days. You will receive a confirmation once completed.');
    }
}
