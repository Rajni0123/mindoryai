<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use PragmaRX\Google2FA\Google2FA;

class Admin2FAController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Show 2FA verification page (after OTP is verified)
     */
    public function showVerify(Request $request)
    {
        // Check if admin completed first auth step (password / OTP)
        if (!session('admin_id_pending_2fa')) {
            return redirect()->route('admin.login')->with('error', 'Session expired. Please login again.');
        }

        return view('auth.admin-2fa-verify');
    }

    /**
     * Verify 2FA code and complete login
     */
    public function verify2FA(Request $request)
    {
        // SECURITY: Rate limiting to prevent brute force attacks on 2FA
        $rateLimitKey = '2fa_verify:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Log::warning('2FA rate limit exceeded', ['ip' => $request->ip()]);
            return response()->json([
                'success' => false,
                'message' => "Too many attempts. Please try again in {$seconds} seconds."
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 300); // 5 attempts per 5 minutes

        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/']
        ], [
            'code.required' => '2FA code is required',
            'code.size' => '2FA code must be 6 digits',
            'code.regex' => '2FA code must contain only numbers'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Get admin from session
        $adminId = session('admin_id_pending_2fa');
        if (!$adminId) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please login again.'
            ], 401);
        }

        $admin = \App\Models\User::find($adminId);

        if (!$admin || !$admin->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => '2FA is not enabled for this account'
            ], 400);
        }

        // Decrypt and verify the 2FA code
        $secret = decrypt($admin->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            Log::warning('Admin 2FA verification failed', [
                'admin_id' => $admin->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid 2FA code. Please try again.'
            ], 400);
        }

        // 2FA verified, complete login
        RateLimiter::clear($rateLimitKey); // Clear rate limit on success
        $admin->resetLoginAttempts();
        $admin->updateLastLogin($request);

        // Login admin with "remember me" enabled
        Auth::login($admin, true);

        // Regenerate session to prevent session fixation attacks
        $request->session()->regenerate();

        // Clear session data
        session()->forget(['admin_mobile_verified', 'admin_email_verified', 'admin_id_pending_2fa']);

        // CRITICAL: Save session before responding
        // This ensures the session is persisted before the redirect happens
        $request->session()->save();

        Log::info('Admin 2FA login successful', [
            'admin_id' => $admin->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful!',
            'redirect' => '/admin/dashboard'
        ]);
    }

    /**
     * Show 2FA setup page (in admin settings)
     */
    public function showSetup()
    {
        $admin = Auth::user();

        if (!$admin->isAdmin()) {
            abort(404);
        }

        // Generate new secret if not exists
        if (!$admin->two_factor_secret) {
            $secret = $this->google2fa->generateSecretKey();
            $admin->two_factor_secret = encrypt($secret);
            $admin->save();
        } else {
            $secret = decrypt($admin->two_factor_secret);
        }

        // Generate QR code URL
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $admin->email,
            $secret
        );

        return view('auth.admin-2fa-setup', [
            'qrCodeUrl' => $qrCodeUrl,
            'secret' => $secret,
            'enabled' => $admin->two_factor_enabled
        ]);
    }

    /**
     * Enable 2FA
     */
    public function enable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $admin = Auth::user();

        if (!$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $secret = decrypt($admin->two_factor_secret);

        // Verify the code before enabling
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid 2FA code. Please scan QR code and try again.'
            ], 400);
        }

        // Enable 2FA
        $admin->two_factor_enabled = true;
        $admin->two_factor_confirmed_at = now();
        $admin->save();

        Log::info('Admin 2FA enabled', ['admin_id' => $admin->id]);

        return response()->json([
            'success' => true,
            'message' => '2FA enabled successfully! You will need to enter 2FA code on next login.'
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $admin = Auth::user();

        if (!$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Verify password before disabling 2FA
        if (!Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password'
            ], 400);
        }

        // Disable 2FA
        $admin->two_factor_enabled = false;
        $admin->two_factor_secret = null;
        $admin->two_factor_confirmed_at = null;
        $admin->save();

        Log::warning('Admin 2FA disabled', ['admin_id' => $admin->id]);

        return response()->json([
            'success' => true,
            'message' => '2FA disabled successfully'
        ]);
    }
}
