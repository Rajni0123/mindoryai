<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.admin-login');
    }

    /**
     * Admin login with email+password or mobile+password.
     */
    public function login(Request $request)
    {
        $rateLimitKey = 'admin-password-login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'login_method' => ['required', 'in:email,mobile'],
            'email' => ['required_if:login_method,email', 'nullable', 'email', 'max:255'],
            'mobile' => ['required_if:login_method,mobile', 'nullable', 'regex:/^[6-9]\d{9}$/'],
            'password' => ['required', 'string'],
        ], [
            'email.required_if' => 'Email address is required',
            'mobile.required_if' => 'Mobile number is required',
            'mobile.regex' => 'Please enter a valid 10-digit mobile number',
            'password.required' => 'Password is required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $method = $request->input('login_method');
        $admin = $method === 'email'
            ? User::where('email', $request->email)->where('role', 'admin')->first()
            : User::where('mobile', $request->mobile)->where('role', 'admin')->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            RateLimiter::hit($rateLimitKey, 600);

            if ($admin) {
                $admin->incrementLoginAttempts();
            }

            Log::warning('Admin password login failed', [
                'method' => $method,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if ($admin->isLocked()) {
            $minutesLeft = now()->diffInMinutes($admin->locked_until);

            return response()->json([
                'success' => false,
                'message' => "Account is locked. Try again in {$minutesLeft} minutes.",
            ], 423);
        }

        RateLimiter::clear($rateLimitKey);

        if ($admin->two_factor_enabled) {
            session([
                'admin_id_pending_2fa' => $admin->id,
                'admin_mobile_verified' => $method === 'mobile' ? $request->mobile : null,
                'admin_email_verified' => $method === 'email' ? $request->email : null,
            ]);

            return response()->json([
                'success' => true,
                'requires_2fa' => true,
                'message' => 'Please enter your 2FA code.',
                'redirect' => route('admin.2fa.verify'),
            ]);
        }

        return $this->completeLogin($admin, $request);
    }

    private function completeLogin(User $admin, Request $request)
    {
        $admin->resetLoginAttempts();
        $admin->updateLastLogin($request);

        Auth::login($admin, true);
        $request->session()->regenerate();
        session()->forget(['admin_mobile_verified', 'admin_email_verified', 'admin_id_pending_2fa']);
        $request->session()->save();

        Log::info('Admin password login successful', [
            'admin_id' => $admin->id,
            'ip' => $request->ip(),
            'session_id' => session()->getId(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful!',
            'redirect' => route('admin.dashboard'),
        ])->withCookie(
            cookie(
                config('session.cookie', 'laravel_session'),
                session()->getId(),
                config('session.lifetime', 120),
                config('session.path', '/'),
                config('session.domain', null),
                config('session.secure', false),
                config('session.http_only', true),
                false,
                config('session.same_site', 'lax')
            )
        );
    }
}
