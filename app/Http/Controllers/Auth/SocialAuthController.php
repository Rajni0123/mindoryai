<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Google OAuth page
     */
    public function redirectToGoogle()
    {
        // Check if Google login is enabled
        if (!SystemSetting::get('auth.google_login_enabled', true)) {
            return redirect()->route('login')
                ->with('error', 'Google login is currently disabled. Please contact administrator.');
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            // Get user info from Google
            $googleUser = Socialite::driver('google')->user();

            // Check if user exists with this email
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // User exists - just log them in
                if (!$user->isAdmin()) {
                    // Regular user login
                    Auth::login($user);
                    session()->regenerate();
                    session()->put('access_granted', true);

                    // Redirect to class selection if not selected, otherwise to chat
                    $redirectRoute = !$user->student_class ? route('class.select') : route('chat');

                    return redirect($redirectRoute)
                        ->with('success', 'Welcome back, ' . $user->name . '!');
                } else {
                    // Admin users should use regular login
                    return redirect()->route('login')
                        ->with('error', 'Admin accounts must use email/password login.');
                }
            } else {
                // New user — no plan until they subscribe
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(32)), // Random password for OAuth users
                    'role' => 'user',
                    'plan_id' => null,
                    'is_active' => true,
                    'token_limit' => 150,
                    'tokens_used' => 0,
                    'can_use_gpt4' => false,
                    'can_use_claude' => false,
                    'can_use_deepseek' => false,
                    'can_use_grok' => false,
                ]);

                // Send welcome email + admin notification
                \App\Services\EmailService::sendWelcome($user);

                // Log the new user in
                Auth::login($user);
                session()->regenerate();
                session()->put('access_granted', true);

                // Redirect to class selection for new users
                return redirect()->route('class.select')
                    ->with('success', 'Welcome to BlinkStudy! You have 150 free tokens. Please select your class.');
            }
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Unable to login with Google. Please try again or use email/password login.');
        }
    }
}
