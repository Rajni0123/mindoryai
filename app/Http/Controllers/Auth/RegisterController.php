<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * Show the registration form
     */
    public function showRegister()
    {
        // If already logged in, redirect to appropriate dashboard
        if (Auth::check()) {
            if (Auth::user()->isUser()) {
                return redirect()->route('chat');
            } elseif (Auth::user()->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }
        }

        // Check if email registration is enabled
        $emailRegistrationEnabled = SystemSetting::get('auth.email_registration_enabled', true);
        $googleLoginEnabled = SystemSetting::get('auth.google_login_enabled', true);

        return view('auth.register', compact('emailRegistrationEnabled', 'googleLoginEnabled'));
    }

    /**
     * Handle registration
     */
    public function register(Request $request)
    {
        // Check if email registration is enabled
        if (!SystemSetting::get('auth.email_registration_enabled', true)) {
            return back()->with('error', 'Email registration is currently disabled. Please contact administrator.');
        }

        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Create new user - inactive until they purchase a subscription
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'is_active' => false, // Inactive until subscription is purchased
            'token_limit' => 0,
            'tokens_used' => 0,
            'can_use_gpt4' => false,
            'can_use_claude' => false,
            'can_use_deepseek' => false,
            'can_use_grok' => false,
        ]);

        // Log the new user in
        Auth::login($user);
        $request->session()->regenerate();
        session()->put('access_granted', true);

        // Redirect to plans page to purchase subscription
        return redirect()->route('plans')
            ->with('success', 'Account created successfully! Please choose a subscription plan to start using AI features.');
    }
}
