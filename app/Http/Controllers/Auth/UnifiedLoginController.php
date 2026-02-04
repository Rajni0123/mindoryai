<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RenflairOTPService;
use App\Services\EmailOTPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class UnifiedLoginController extends Controller
{
    private RenflairOTPService $otpService;
    private EmailOTPService $emailOTPService;

    public function __construct(RenflairOTPService $otpService, EmailOTPService $emailOTPService)
    {
        $this->otpService = $otpService;
        $this->emailOTPService = $emailOTPService;
    }

    /**
     * Show unified login form (single URL for both admin and users)
     */
    public function showLoginForm()
    {
        // Check if Google login is enabled in system settings
        $googleLoginEnabled = \App\Models\SystemSetting::get('auth.google_login_enabled', true);

        return view('auth.unified-login', compact('googleLoginEnabled'));
    }

    /**
     * Send OTP to mobile number (auto-detects role)
     *
     * Security:
     * - Rate limiting: 5 attempts per 10 minutes per IP
     * - Role detection is server-side only
     * - No role information exposed to frontend
     */
    public function sendOTP(Request $request)
    {
        // Rate limiting: 5 attempts per 10 minutes
        $key = 'unified-otp-send:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many OTP requests. Please try again in {$seconds} seconds."
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/']
        ], [
            'mobile.required' => 'Mobile number is required',
            'mobile.regex' => 'Please enter a valid 10-digit mobile number'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $mobile = $request->mobile;

        // Check if mobile exists in database
        $user = User::where('mobile', $mobile)->first();

        if (!$user) {
            // For security: Don't reveal if user exists or not
            // For new users, we'll handle registration during OTP verification
            Log::info('Login attempt with unregistered mobile', [
                'mobile' => $mobile,
                'ip' => $request->ip(),
            ]);
        } else {
            // Check if account is locked (for admin accounts)
            if ($user->role === 'admin' && $user->isLocked()) {
                $minutesLeft = now()->diffInMinutes($user->locked_until);
                return response()->json([
                    'success' => false,
                    'message' => "Account is locked. Try again in {$minutesLeft} minutes."
                ], 423);
            }

            // Store role in session for later use (encrypted session)
            session(['pending_login_role' => $user->role]);
            session(['pending_login_mobile' => $mobile]);
        }

        // Send OTP
        $result = $this->otpService->sendOTP($mobile, $request->ip(), $request->userAgent());

        if ($result['success']) {
            RateLimiter::hit($key, 600); // 10 minutes

            Log::info('Unified login OTP sent', [
                'mobile' => $mobile,
                'ip' => $request->ip(),
                'role' => $user?->role ?? 'new_user',
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'expires_in' => $result['expires_in'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }

    /**
     * Verify OTP and redirect based on role
     *
     * Security:
     * - Rate limiting: 10 attempts per 10 minutes
     * - Session regeneration to prevent fixation
     * - Explicit cookie setting for session persistence
     * - Role-based redirection (server-side only)
     */
    public function verifyOTP(Request $request)
    {
        // Rate limiting: 10 attempts per 10 minutes
        $key = 'unified-otp-verify:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many verification attempts. Try again in {$seconds} seconds."
            ], 429);
        }

        // Get OTP length from config
        $otpLength = (int) \App\Models\FrontendConfig::getValue('auth.otp.length', 4);

        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'otp' => ['required', 'string', 'size:' . $otpLength],
            'referral_code' => ['nullable', 'string', 'max:20']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $mobile = $request->mobile;
        $otp = $request->otp;

        // Verify OTP
        $result = $this->otpService->verifyOTP($mobile, $otp);

        if (!$result['success']) {
            RateLimiter::hit($key, 600);

            // Increment failed attempts for existing users
            $user = User::where('mobile', $mobile)->first();
            if ($user) {
                $user->incrementLoginAttempts();

                Log::warning('Unified login OTP verification failed', [
                    'mobile' => $mobile,
                    'ip' => $request->ip(),
                    'failed_attempts' => $user->failed_login_attempts,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        // OTP verified, get or create user
        $user = User::where('mobile', $mobile)->first();
        $isNewUser = false;

        if (!$user) {
            // Check if mobile is admin mobile
            $adminMobiles = ['9102453339']; // Add admin mobile numbers here
            $isAdmin = in_array($mobile, $adminMobiles);

            // Create new user (role based on admin list)
            $uniqueEmail = $mobile . '_' . time() . ($isAdmin ? '@admin.' . config('services.main_domain', 'example.com') : '@mobile.user');

            try {
                // Get default plan (check user_plans table, not pricing_plans)
                $defaultPlanId = DB::table('user_plans')->where('id', 1)->exists() ? 1 : null;

                // Use forceFill to set 'role' which is guarded against mass-assignment
                $user = new User();
                $user->forceFill([
                    'name' => $isAdmin ? 'Admin' : 'User ' . substr($mobile, -4),
                    'email' => $uniqueEmail,
                    'mobile' => $mobile,
                    'mobile_verified_at' => now(),
                    'password' => bcrypt(uniqid()),
                    'role' => $isAdmin ? 'admin' : 'user',
                    'plan_id' => $defaultPlanId,
                    'token_limit' => $isAdmin ? 999999 : 150,
                    'tokens_used' => 0,
                    'is_active' => true,
                ])->save();
                $isNewUser = true;

                Log::info('New user registered via unified login', [
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                    'role' => $user->role,
                    'plan_id' => $defaultPlanId,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create user during OTP verification', [
                    'mobile' => $mobile,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Account creation failed. Please contact support.'
                ], 500);
            }
        }

        // Handle referral code for new users
        $referralApplied = false;
        if ($isNewUser && $request->has('referral_code')) {
            $referralCode = $request->input('referral_code');
            if (!empty($referralCode)) {
                try {
                    $referralApplied = $user->applyReferralCode($referralCode);
                    if ($referralApplied) {
                        Log::info('Referral code applied during registration', [
                            'user_id' => $user->id,
                            'referral_code' => $referralCode,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to apply referral code during registration', [
                        'user_id' => $user->id,
                        'referral_code' => $referralCode,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail registration if referral code application fails
                }
            }
        }

        // Check if admin needs 2FA
        if ($user->role === 'admin' && $user->two_factor_enabled) {
            // Store verified mobile in session for 2FA step
            session(['admin_mobile_verified' => $mobile]);
            session(['admin_id_pending_2fa' => $user->id]);

            return response()->json([
                'success' => true,
                'requires_2fa' => true,
                'message' => 'OTP verified. Please enter 2FA code.',
                'redirect' => route('admin.2fa.verify')
            ]);
        }

        // Complete login based on role
        return $this->completeLogin($user, $request, $isNewUser);
    }

    /**
     * Complete login with role-based redirection
     *
     * CRITICAL SECURITY:
     * - Session regeneration prevents session fixation
     * - Explicit session save ensures persistence
     * - Role-based redirection happens server-side
     * - Admin sessions have additional logging
     * - Mobile app gets API token instead of session
     */
    private function completeLogin(User $user, Request $request, bool $isNewUser = false)
    {
        // Reset failed attempts
        $user->resetLoginAttempts();

        // Update login information
        $user->updateLastLogin($request);

        // Check if this is an API request (mobile app)
        // IMPORTANT: Only check route, not expectsJson() because web login also sends JSON
        $isApiRequest = $request->is('api/*');

        if ($isApiRequest) {
            // Mobile App: Return token-based authentication
            $token = $user->createToken('mobile-app')->plainTextToken;

            Log::info('Mobile app login successful', [
                'user_id' => $user->id,
                'mobile' => $user->mobile,
                'ip' => $request->ip(),
            ]);

            // Check if user needs to complete profile
            $needsProfileCompletion = !$user->hasCompletedProfile();

            return response()->json([
                'success' => true,
                'message' => 'Login successful!',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'role' => $user->role,
                    'plan_id' => $user->plan_id,
                    'token_limit' => $user->token_limit,
                    'tokens_used' => $user->tokens_used,
                    'profile_completed' => $user->profile_completed,
                ],
                'needs_profile_completion' => $needsProfileCompletion,
                'redirect' => $user->role === 'admin' ? 'admin' : 'chat',
            ]);
        }

        // Web: Session-based authentication
        // Login user with "remember me" enabled
        Auth::login($user, true);

        // CRITICAL: Regenerate and save session to prevent session fixation
        $request->session()->regenerate();
        $request->session()->save();

        // Clear temporary session data
        session()->forget(['pending_login_role', 'pending_login_mobile', 'admin_mobile_verified', 'admin_id_pending_2fa']);

        // Flag for first-time profile completion popup
        if (!$user->hasCompletedProfile()) {
            session(['show_profile_popup' => true]);
        }

        // Role-based redirection (SERVER-SIDE ONLY)
        $redirectRoute = $this->getRedirectRoute($user, $isNewUser);

        // Enhanced logging for admin logins
        if ($user->role === 'admin') {
            Log::info('Admin login successful via unified login', [
                'admin_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
            ]);
        } else {
            Log::info('User login successful via unified login', [
                'user_id' => $user->id,
                'mobile' => $user->mobile,
            ]);
        }

        // Create response with role-based redirect
        $response = response()->json([
            'success' => true,
            'message' => 'Login successful!',
            'redirect' => $redirectRoute,
        ]);

        // Explicitly set the session cookie for persistence
        return $response->withCookie(
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

    /**
     * Get redirect route based on user role
     *
     * ROLE-BASED REDIRECTION:
     * - admin → /admin/dashboard
     * - user (new, no class) → /select-class
     * - user (existing) → /chat
     */
    private function getRedirectRoute(User $user, bool $isNewUser): string
    {
        if ($user->role === 'admin') {
            // Admin redirect to admin subdomain
            $adminUrl = config('services.subdomains.admin_url');
            if ($adminUrl) {
                return rtrim($adminUrl, '/') . '/admin/dashboard';
            }
            // Fallback to route if URL not configured
            return route('admin.dashboard');
        }

        // Profile completion is now handled via popup on the chat page

        // Redirect to chat subdomain after successful login
        $chatUrl = config('services.subdomains.chat_url');
        if ($chatUrl) {
            return $chatUrl;
        }
        // Fallback to chat route
        return route('chat');
    }

    /**
     * Send OTP to email address
     *
     * Security:
     * - Rate limiting: 5 attempts per 10 minutes per IP
     * - Role detection is server-side only
     */
    public function sendEmailOTP(Request $request)
    {
        // Rate limiting: 5 attempts per 10 minutes
        $key = 'email-otp-send:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many OTP requests. Please try again in {$seconds} seconds."
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255']
        ], [
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $email = $request->email;

        // Check if email exists in database
        $user = User::where('email', $email)->first();

        if (!$user) {
            // For security: Don't reveal if user exists or not
            // For new users, we'll handle registration during OTP verification
            Log::info('Login attempt with unregistered email', [
                'email' => $email,
                'ip' => $request->ip(),
            ]);
        } else {
            // Check if account is locked (for admin accounts)
            if ($user->role === 'admin' && $user->isLocked()) {
                $minutesLeft = now()->diffInMinutes($user->locked_until);
                return response()->json([
                    'success' => false,
                    'message' => "Account is locked. Try again in {$minutesLeft} minutes."
                ], 423);
            }

            // Store role in session for later use (encrypted session)
            session(['pending_login_role' => $user->role]);
            session(['pending_login_email' => $email]);
        }

        // Send OTP
        $result = $this->emailOTPService->sendOTP($email, $request->ip(), $request->userAgent());

        if ($result['success']) {
            RateLimiter::hit($key, 600); // 10 minutes

            Log::info('Email OTP sent', [
                'email' => $email,
                'ip' => $request->ip(),
                'role' => $user?->role ?? 'new_user',
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'expires_in' => $result['expires_in'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }

    /**
     * Send WhatsApp OTP to mobile number
     *
     * Security:
     * - Rate limiting: 5 attempts per 10 minutes per IP
     * - Role detection is server-side only
     * - Uses Renflair WhatsApp API
     */
    public function sendWhatsAppOTP(Request $request)
    {
        // Rate limiting: 20 attempts per 10 minutes (increased for testing)
        $key = 'whatsapp-otp-send:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many OTP requests. Please try again in {$seconds} seconds."
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/']
        ], [
            'mobile.required' => 'Mobile number is required',
            'mobile.regex' => 'Please enter a valid 10-digit mobile number'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $mobile = $request->mobile;

        // Check if mobile exists in database
        $user = User::where('mobile', $mobile)->first();

        if (!$user) {
            // For security: Don't reveal if user exists or not
            Log::info('WhatsApp OTP attempt with unregistered mobile', [
                'mobile' => $mobile,
                'ip' => $request->ip(),
            ]);
        } else {
            // Check if account is locked (for admin accounts)
            if ($user->role === 'admin' && $user->isLocked()) {
                $minutesLeft = now()->diffInMinutes($user->locked_until);
                return response()->json([
                    'success' => false,
                    'message' => "Account is locked. Try again in {$minutesLeft} minutes."
                ], 423);
            }

            // Store role in session for later use
            session(['pending_login_role' => $user->role]);
            session(['pending_login_mobile' => $mobile]);
        }

        // Send OTP via WhatsApp
        $result = $this->otpService->sendWhatsAppOTP($mobile, $request->ip(), $request->userAgent());

        if ($result['success']) {
            RateLimiter::hit($key, 600); // 10 minutes

            Log::info('WhatsApp OTP sent', [
                'mobile' => $mobile,
                'ip' => $request->ip(),
                'role' => $user?->role ?? 'new_user',
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'expires_in' => $result['expires_in'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }

    /**
     * Verify Email OTP and redirect based on role
     *
     * Security:
     * - Rate limiting: 10 attempts per 10 minutes
     * - Session regeneration to prevent fixation
     * - Role-based redirection (server-side only)
     */
    public function verifyEmailOTP(Request $request)
    {
        // Rate limiting: 10 attempts per 10 minutes
        $key = 'email-otp-verify:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many verification attempts. Try again in {$seconds} seconds."
            ], 429);
        }

        // Get OTP length from config
        $otpLength = (int) \App\Models\FrontendConfig::getValue('auth.email_otp.length', 4);

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'string', 'size:' . $otpLength],
            'referral_code' => ['nullable', 'string', 'max:20']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $email = $request->email;
        $otp = $request->otp;

        // Verify OTP
        $result = $this->emailOTPService->verifyOTP($email, $otp);

        if (!$result['success']) {
            RateLimiter::hit($key, 600);

            // Increment failed attempts for existing users
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->incrementLoginAttempts();

                Log::warning('Email OTP verification failed', [
                    'email' => $email,
                    'ip' => $request->ip(),
                    'failed_attempts' => $user->failed_login_attempts,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        // OTP verified, get or create user
        $user = User::where('email', $email)->first();
        $isNewUser = false;

        if (!$user) {
            // Check if email is admin email
            $adminEmails = [config('services.admin_email')]; // Add admin emails here
            $isAdmin = in_array($email, $adminEmails);

            // Create new user (role based on admin list)
            // Extract a name from email (before @)
            $emailName = explode('@', $email)[0];
            $userName = $isAdmin ? 'Admin' : ucfirst($emailName);

            try {
                // Get default plan (check user_plans table, not pricing_plans)
                $defaultPlanId = DB::table('user_plans')->where('id', 1)->exists() ? 1 : null;

                // Use forceFill to set 'role' which is guarded against mass-assignment
                $user = new User();
                $user->forceFill([
                    'name' => $userName,
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => bcrypt(uniqid()),
                    'role' => $isAdmin ? 'admin' : 'user',
                    'plan_id' => $defaultPlanId,
                    'token_limit' => $isAdmin ? 999999 : 150,
                    'tokens_used' => 0,
                    'is_active' => true,
                ])->save();
                $isNewUser = true;

                Log::info('New user registered via email login', [
                    'user_id' => $user->id,
                    'email' => $email,
                    'role' => $user->role,
                    'plan_id' => $defaultPlanId,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create user during email OTP verification', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Account creation failed. Please contact support.'
                ], 500);
            }
        }

        // Handle referral code for new users
        if ($isNewUser && $request->has('referral_code')) {
            $referralCode = $request->input('referral_code');
            if (!empty($referralCode)) {
                try {
                    $referralApplied = $user->applyReferralCode($referralCode);
                    if ($referralApplied) {
                        Log::info('Referral code applied during email registration', [
                            'user_id' => $user->id,
                            'referral_code' => $referralCode,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to apply referral code during email registration', [
                        'user_id' => $user->id,
                        'referral_code' => $referralCode,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail registration if referral code application fails
                }
            }
        }

        // Check if admin needs 2FA
        if ($user->role === 'admin' && $user->two_factor_enabled) {
            // Store verified email in session for 2FA step
            session(['admin_email_verified' => $email]);
            session(['admin_id_pending_2fa' => $user->id]);

            return response()->json([
                'success' => true,
                'requires_2fa' => true,
                'message' => 'OTP verified. Please enter 2FA code.',
                'redirect' => route('admin.2fa.verify')
            ]);
        }

        // Complete login based on role
        return $this->completeLogin($user, $request, $isNewUser);
    }

    /**
     * Logout (clears session or revokes token)
     *
     * Handles both web (session-based) and mobile (token-based) logout
     */
    public function logout(Request $request)
    {
        $userId = Auth::id();
        $userRole = Auth::user()?->role;

        // Check if this is an API request (mobile app)
        // IMPORTANT: Only check route, not expectsJson() to avoid confusion
        $isApiRequest = $request->is('api/*');

        if ($isApiRequest) {
            // Mobile App: Revoke current access token
            $request->user()->currentAccessToken()->delete();

            Log::info('Mobile app logout successful', [
                'user_id' => $userId,
                'role' => $userRole,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        }

        // Web: Session-based logout
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('User logout via unified login', [
            'user_id' => $userId,
            'role' => $userRole,
        ]);

        return redirect()->route('home')->with('success', 'Logged out successfully');
    }

    /**
     * Show class selection page
     *
     * @return \Illuminate\View\View
     */
    public function showClassSelection()
    {
        return view('auth.select-class');
    }

    /**
     * Update user's class
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateClass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_class' => ['required', 'string']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = Auth::user();
        $user->student_class = $request->student_class;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Class updated successfully!'
        ]);
    }

    /**
     * Handle Google Sign-In for mobile app
     *
     * Supports both ID token (native apps) and authorization code (Expo/web)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function googleSignIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_token' => ['required_without:code', 'nullable', 'string'],
            'code' => ['required_without:id_token', 'nullable', 'string'],
            'platform' => ['required', 'in:android,ios,web,expo'],
            'referral_code' => ['nullable', 'string', 'max:20'],
        ], [
            'platform.required' => 'Platform is required',
            'platform.in' => 'Platform must be android, ios, web, or expo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');
            $redirectUri = config('services.google.redirect');

            // Initialize Google Client
            $client = new \Google_Client([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

            $payload = null;

            // If authorization code is provided (Expo/web flow)
            if ($request->has('code') && !empty($request->code)) {
                // Exchange authorization code for tokens
                $client->setRedirectUri($redirectUri);
                $token = $client->fetchAccessTokenWithAuthCode($request->code);

                if (isset($token['error'])) {
                    Log::error('Google token exchange error', [
                        'error' => $token['error'],
                        'error_description' => $token['error_description'] ?? '',
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to exchange authorization code. Please try again.'
                    ], 401);
                }

                // Verify the ID token
                if (isset($token['id_token'])) {
                    $payload = $client->verifyIdToken($token['id_token']);
                }
            }
            // If ID token is provided (native app flow)
            elseif ($request->has('id_token') && !empty($request->id_token)) {
                // For mobile apps, use platform-specific client ID if available
                if ($request->platform === 'android' && config('services.google.android_client_id')) {
                    $clientId = config('services.google.android_client_id');
                } elseif ($request->platform === 'ios' && config('services.google.ios_client_id')) {
                    $clientId = config('services.google.ios_client_id');
                }

                $client->setClientId($clientId);
                $payload = $client->verifyIdToken($request->id_token);
            }

            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google credentials. Please try again.'
                ], 401);
            }

            // Extract user information from Google token
            $googleId = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'] ?? null;
            $picture = $payload['picture'] ?? null;
            $verifiedEmail = $payload['email_verified'] ?? false;

            if (!$verifiedEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not verified by Google.'
                ], 403);
            }

            // Find or create user
            $user = User::where('email', $email)->first();

            if (!$user) {
                // Use forceFill to set 'role' which is guarded against mass-assignment
                $user = new User();
                $user->forceFill([
                    'name' => $name ?? 'Google User',
                    'email' => $email,
                    'password' => bcrypt(str_random(32)),
                    'role' => 'user',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
                $user->save();

                // Handle referral code for new Google users
                if ($request->has('referral_code')) {
                    $referralCode = $request->input('referral_code');
                    if (!empty($referralCode)) {
                        try {
                            $referralApplied = $user->applyReferralCode($referralCode);
                            if ($referralApplied) {
                                Log::info('Referral code applied during Google registration', [
                                    'user_id' => $user->id,
                                    'referral_code' => $referralCode,
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to apply referral code during Google registration', [
                                'user_id' => $user->id,
                                'referral_code' => $referralCode,
                                'error' => $e->getMessage(),
                            ]);
                            // Don't fail registration if referral code application fails
                        }
                    }
                }

                Log::info('New user registered via Google Sign-In', [
                    'user_id' => $user->id,
                    'email' => $email,
                    'google_id' => $googleId,
                    'platform' => $request->platform,
                ]);
            } else {
                // Update existing user's info if needed
                if (!$user->name) {
                    $user->name = $name;
                }
                if (!$user->email_verified_at) {
                    $user->email_verified_at = now();
                }
                $user->save();
            }

            // Generate API token for mobile app
            $token = $user->createToken('mobile-app')->plainTextToken;

            // Check if user needs to complete profile
            $needsProfileCompletion = !$user->hasCompletedProfile();

            return response()->json([
                'success' => true,
                'message' => 'Login successful!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'role' => $user->role,
                    'plan_id' => $user->plan_id,
                    'is_active' => $user->is_active,
                    'profile_completed' => $user->profile_completed,
                ],
                'token' => $token,
                'needs_profile_completion' => $needsProfileCompletion,
            ]);

        } catch (\Exception $e) {
            Log::error('Google Sign-In error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed. Please try again.'
            ], 500);
        }
    }
}
