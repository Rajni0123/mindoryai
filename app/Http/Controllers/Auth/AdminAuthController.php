<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminCredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function __construct(
        private readonly AdminCredentialService $credentials
    ) {}

    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect('/admin/dashboard');
        }

        return view('auth.admin-login');
    }

    /**
     * Admin login with email+password or mobile+password (server-side form or JSON).
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $rateLimitKey = 'admin-password-login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $message = "Too many login attempts. Please try again in {$seconds} seconds.";

            return $this->loginResponse($request, false, $message, 429);
        }

        $validator = Validator::make($request->all(), [
            'login_method' => ['required', 'in:email,mobile'],
            'email' => ['required_if:login_method,email', 'nullable', 'email', 'max:255'],
            'mobile' => ['required_if:login_method,mobile', 'nullable', 'string', 'max:15'],
            'password' => ['required', 'string'],
        ], [
            'email.required_if' => 'Email address is required',
            'mobile.required_if' => 'Mobile number is required',
            'password.required' => 'Password is required',
        ]);

        if ($validator->fails()) {
            return $this->loginResponse($request, false, $validator->errors()->first(), 422);
        }

        $method = $request->input('login_method');
        $email = $method === 'email' ? strtolower(trim((string) $request->email)) : null;
        $mobile = $method === 'mobile' ? $this->credentials->normalizeMobile((string) $request->mobile) : null;

        if ($mobile && ! preg_match('/^[6-9]\d{9}$/', $mobile)) {
            return $this->loginResponse($request, false, 'Please enter a valid 10-digit mobile number', 422);
        }

        $admin = $this->credentials->findAdmin($email, $mobile);

        if (! $admin || ! $this->credentials->verifyPassword($admin, (string) $request->password)) {
            RateLimiter::hit($rateLimitKey, 600);

            if ($admin) {
                $admin->incrementLoginAttempts();
            }

            Log::warning('Admin password login failed', [
                'method' => $method,
                'ip' => $request->ip(),
            ]);

            return $this->loginResponse($request, false, 'Invalid credentials', 401);
        }

        if ($admin->isLocked()) {
            $minutesLeft = now()->diffInMinutes($admin->locked_until);

            return $this->loginResponse(
                $request,
                false,
                "Account is locked. Try again in {$minutesLeft} minutes.",
                423
            );
        }

        RateLimiter::clear($rateLimitKey);

        if ($admin->two_factor_enabled) {
            session([
                'admin_id_pending_2fa' => $admin->id,
                'admin_mobile_verified' => $mobile,
                'admin_email_verified' => $email,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'requires_2fa' => true,
                    'message' => 'Please enter your 2FA code.',
                    'redirect' => '/admin/verify-2fa',
                ]);
            }

            return redirect('/admin/verify-2fa');
        }

        return $this->completeLogin($admin, $request);
    }

    private function completeLogin(User $admin, Request $request): JsonResponse|RedirectResponse
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
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Login successful!',
                'redirect' => '/admin/dashboard',
            ]);
        }

        return redirect('/admin/dashboard')->with('success', 'Welcome back, Admin!');
    }

    private function loginResponse(Request $request, bool $success, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
            ], $status);
        }

        return back()
            ->with($success ? 'success' : 'error', $message)
            ->withInput($request->only('login_method', 'email', 'mobile'));
    }
}
