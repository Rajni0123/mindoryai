<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserDashboardController extends Controller
{
    /**
     * Show user dashboard/profile
     */
    public function index()
    {
        $user = Auth::user();

        // Refresh user data and load relationships
        $user->refresh();
        $user->load('userPlan');

        // Get user's latest payment
        $latestPayment = Payment::with('plan')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Get all user payments
        $payments = Payment::with('plan')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get available pricing plans for upgrades
        $pricingPlans = \App\Models\PricingPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        return view('user.dashboard', compact('user', 'latestPayment', 'payments', 'pricingPlans'));
    }

    /**
     * Show user settings page
     */
    public function settings()
    {
        return view('user.settings');
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'class_level' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8)->confirmed()],
        ]);

        $user = Auth::user();

        // Check if current password is correct
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }
}
