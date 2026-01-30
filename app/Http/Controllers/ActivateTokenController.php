<?php

namespace App\Http\Controllers;

use App\Models\UserActivationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ActivateTokenController extends Controller
{
    /**
     * Show the token activation form
     */
    public function show()
    {
        $user = Auth::user();

        // If user is already activated, redirect to chat
        if ($user && $user->token_activated) {
            return redirect()->route('chat');
        }

        return view('activate-token');
    }

    /**
     * Activate user account with token
     */
    public function activate(Request $request)
    {
        // Rate limiting - 5 attempts per minute
        $key = 'token_activation_' . $request->ip();
        $attempts = Cache::get($key, 0);

        if ($attempts >= 5) {
            return back()->with('error', 'Too many activation attempts. Please try again later.');
        }

        Cache::put($key, $attempts + 1, now()->addMinute());

        $validated = $request->validate([
            'activation_token' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user) {
            return back()->with('error', 'You must be logged in to activate your account.');
        }

        // Check if already activated
        if ($user->token_activated) {
            return redirect()->route('chat')
                ->with('success', 'Your account is already activated!');
        }

        // Verify and activate the token
        $token = UserActivationToken::verifyAndActivate($validated['activation_token'], $user->id);

        if (!$token) {
            return back()->with('error', 'Invalid or expired activation token.');
        }

        // Get the plan associated with the token
        $plan = $token->plan;

        // Update user with plan details
        $user->update([
            'plan_id' => $plan->id,
            'token_activated' => true,
            'token_activated_at' => now(),
            'token_limit' => $plan->message_tokens,
            'tokens_used' => 0,
            'is_active' => true,
            'can_use_gpt4' => $plan->can_use_gpt4,
            'can_use_claude' => $plan->can_use_claude,
            'can_use_deepseek' => $plan->can_use_deepseek,
            'can_use_grok' => $plan->can_use_grok,
        ]);

        // Clear rate limiting
        Cache::forget($key);

        return redirect()->route('chat')
            ->with('success', 'Your account has been activated successfully with the ' . $plan->name . ' plan!');
    }
}
