<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use App\Support\StudyProfileCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Google OAuth page
     */
    public function redirectToGoogle()
    {
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
            $googleUser = Socialite::driver('google')->user();
            $email = $googleUser->getEmail();

            if (empty($email)) {
                return redirect()->route('login')
                    ->with('error', 'Google did not return an email address. Please try again.');
            }

            $user = User::where('email', $email)->first();

            if ($user) {
                if ($user->isAdmin()) {
                    return redirect()->route('login')
                        ->with('error', 'Admin accounts must use mobile OTP login, not Google.');
                }
            } else {
                $freePlan = \Illuminate\Support\Facades\DB::table('user_plans')->where('slug', 'free')->first();

                $user = new User();
                $user->forceFill([
                    'name' => $googleUser->getName() ?: 'BlinkStudy User',
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'user',
                    'plan_id' => $freePlan?->id,
                    'is_active' => true,
                    'token_limit' => 150,
                    'tokens_used' => 0,
                    'can_use_gpt4' => false,
                    'can_use_claude' => false,
                    'can_use_deepseek' => false,
                    'can_use_grok' => false,
                ])->save();

                try {
                    \App\Services\EmailService::sendWelcome($user);
                } catch (\Throwable $mailError) {
                    Log::warning('Google signup welcome email failed', [
                        'user_id' => $user->id,
                        'error' => $mailError->getMessage(),
                    ]);
                }
            }

            Auth::login($user, true);
            request()->session()->regenerate();
            session()->put('access_granted', true);

            $user->updateLastLogin(request());

            return $this->redirectAfterSocialLogin($user);
        } catch (\Exception $e) {
            Log::error('Google login failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Unable to login with Google. Please try again or use mobile OTP login.');
        }
    }

    private function redirectAfterSocialLogin(User $user): RedirectResponse
    {
        if (StudyProfileCatalog::needsStudySetup(
            $user->target_exam,
            $user->student_class,
            $user->is_profile_complete
        )) {
            return redirect()->route('class.select')
                ->with('success', 'Welcome! Complete your quick setup to get started.');
        }

        $chatUrl = rtrim((string) env('CHAT_SUBDOMAIN_URL', 'https://chat.blinkstudy.in'), '/');
        $authToken = $user->createToken('web-chat-transfer', ['web-chat'])->plainTextToken;
        $tokenHash = hash('sha256', $authToken);
        Cache::put("chat_auth_transfer:{$tokenHash}", $user->id, now()->addMinutes(5));

        return redirect($chatUrl . '?auth_token=' . urlencode($authToken));
    }
}
