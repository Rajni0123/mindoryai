<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OTPVerification;
use App\Models\UserSession;
use App\Support\TestAccountHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Normalize mobile app payload (mobile/otp) to API fields.
     */
    private function normalizeMobileRequest(Request $request): void
    {
        if (!$request->filled('phone_number') && $request->filled('mobile')) {
            $request->merge(['phone_number' => $request->input('mobile')]);
        }
        if (!$request->filled('otp_code') && $request->filled('otp')) {
            $request->merge(['otp_code' => $request->input('otp')]);
        }
    }

    /**
     * Step 1: Send OTP to phone number
     */
    public function sendOTP(Request $request)
    {
        try {
            $this->normalizeMobileRequest($request);

            $request->validate([
                'phone_number' => 'required|string|regex:/^[6-9]\d{9}$/',
            ]);

            $phoneNumber = $request->phone_number;

            if (TestAccountHelper::isAnyTestAccount($phoneNumber)) {
                Log::info('Test Account - Skipping OTP send', ['phone' => $phoneNumber]);

                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'phone_number' => $phoneNumber,
                    'expires_in' => 600,
                    'is_test_account' => true,
                ]);
            }

            $recentOTPCount = OTPVerification::where('mobile', $phoneNumber)
                ->where('created_at', '>', Carbon::now()->subHour())
                ->count();

            if ($recentOTPCount >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many OTP requests. Please try again later.',
                ], 429);
            }

            $otp = OTPVerification::createForPhone($phoneNumber);
            $this->sendOTPSMS($phoneNumber, $otp->otp_code);

            Log::info('OTP sent', [
                'phone' => $phoneNumber,
                'otp_id' => $otp->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'phone_number' => $phoneNumber,
                'expires_in' => 600,
            ]);
        } catch (\Throwable $e) {
            Log::error('Mobile sendOTP failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not send OTP right now. Please try again.',
            ], 500);
        }
    }

    /**
     * Step 2: Verify OTP and login/register user
     */
    public function verifyOTP(Request $request)
    {
        try {
            $this->normalizeMobileRequest($request);

            $request->validate([
                'phone_number' => 'required|string',
                'otp_code' => 'required|string|size:4',
                'device_id' => 'nullable|string',
                'device_name' => 'nullable|string',
                'device_type' => 'nullable|string|in:android,ios,web',
            ]);

            $phoneNumber = $request->phone_number;
            $otpCode = $request->otp_code;
            $isTestAccount = TestAccountHelper::isAnyTestAccount($phoneNumber);

            if ($isTestAccount) {
                if (!TestAccountHelper::verifyAnyTestOtp($phoneNumber, $otpCode)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid OTP for test account.',
                    ], 400);
                }
                Log::info('Test Account Login', ['phone' => $phoneNumber]);
            } else {
                $otpRecord = OTPVerification::validForPhone($phoneNumber, $otpCode)->first();

                if (!$otpRecord) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired OTP. Please try again.',
                    ], 400);
                }

                $otpRecord->markVerified();
            }

            $user = User::where('mobile', $phoneNumber)->first();

            $isFirstTime = false;
            $needsProfileCompletion = false;

            if (!$user) {
                $user = new User();
                $user->forceFill([
                    'mobile' => $phoneNumber,
                    'name' => 'User ' . substr($phoneNumber, -4),
                    'email' => $phoneNumber . '_' . time() . '@mobile.user',
                    'password' => Hash::make(Str::random(32)),
                    'mobile_verified_at' => now(),
                    'role' => 'user',
                    'is_profile_complete' => false,
                    'login_count' => 1,
                    'plan_id' => null,
                    'token_limit' => 150,
                    'tokens_used' => 0,
                    'is_active' => true,
                ])->save();

                $isFirstTime = true;
                $needsProfileCompletion = true;

                Log::info('New user registered via OTP', [
                    'user_id' => $user->id,
                    'phone' => $phoneNumber,
                ]);
            } else {
                $user->increment('login_count');

                if (
                    !$user->is_profile_complete
                    || empty($user->name)
                    || empty($user->target_exam)
                    || empty($user->student_class)
                ) {
                    $needsProfileCompletion = true;
                }

                Log::info('User logged in via OTP', [
                    'user_id' => $user->id,
                    'phone' => $phoneNumber,
                ]);
            }

            $token = $user->createToken('mobile-app')->plainTextToken;

            try {
                UserSession::logLogin(
                    $user->id,
                    $isFirstTime,
                    $needsProfileCompletion,
                    $request->device_id,
                    $request->device_name,
                    $request->device_type,
                    $request->ip()
                );
            } catch (\Throwable $e) {
                Log::warning('UserSession log skipped', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully',
                'token' => $token,
                'user' => $this->formatUser($user),
                'data' => [
                    'user' => $this->formatUser($user),
                    'token' => $token,
                    'is_first_time' => $isFirstTime,
                    'needs_profile_completion' => $needsProfileCompletion,
                ],
                'needs_profile_completion' => $needsProfileCompletion,
            ]);
        } catch (\Throwable $e) {
            Log::error('Mobile verifyOTP failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Step 3: Complete user profile
     */
    public function completeProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'nullable|email|unique:users,email,' . auth()->id(),
            'student_class' => 'nullable|string|in:6,7,8,9,10,11,12',
            'target_exam' => 'nullable|string|in:JEE,NEET,CBSE,UPSC,Other',
            'exam_date' => 'nullable|date|after:today',
        ]);

        $user = auth()->user();

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'student_class' => $request->student_class,
            'target_exam' => $request->target_exam,
            'exam_date' => $request->exam_date,
            'is_profile_complete' => true,
            'profile_completed' => true,
            'profile_completed_at' => Carbon::now(),
        ]);

        Log::info('Profile completed', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $this->formatUser($user->fresh()),
            ],
        ]);
    }

    /**
     * Get current user data
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->formatUser($user),
                'needs_profile_completion' => !$user->is_profile_complete || empty($user->name),
            ],
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'phone_number' => $user->mobile,
            'mobile' => $user->mobile,
            'name' => $user->name,
            'email' => $user->email,
            'is_profile_complete' => (bool) $user->is_profile_complete,
            'profile_completed' => (bool) ($user->profile_completed ?? $user->is_profile_complete),
            'login_count' => $user->login_count,
            'student_class' => $user->student_class,
            'target_exam' => $user->target_exam,
            'subjects' => $user->favorite_subject,
            'plan_id' => $user->plan_id,
        ];
    }

    /**
     * Helper: Send OTP via SMS
     */
    private function sendOTPSMS(string $phoneNumber, string $otpCode)
    {
        $authKey = config('services.msg91.auth_key');
        $templateId = config('services.msg91.template_id');

        if ($authKey && $templateId) {
            try {
                $url = 'https://api.msg91.com/api/v5/otp';

                $response = \Http::post($url, [
                    'template_id' => $templateId,
                    'mobile' => '91' . $phoneNumber,
                    'authkey' => $authKey,
                    'otp' => $otpCode,
                ]);

                Log::info('OTP sent via MSG91', [
                    'phone' => $phoneNumber,
                    'response' => $response->body(),
                ]);

                return true;
            } catch (\Throwable $e) {
                Log::error('MSG91 OTP failed', ['error' => $e->getMessage()]);
            }
        }

        // Development / fallback — OTP already stored in database
        Log::info('OTP ready (SMS fallback)', ['phone' => $phoneNumber, 'otp' => $otpCode]);

        return true;
    }
}
