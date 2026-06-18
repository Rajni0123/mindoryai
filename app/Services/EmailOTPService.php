<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EmailOTPService
{
    private int $otpLength;
    private int $otpExpiryMinutes;
    private int $maxAttempts;

    public function __construct()
    {
        // Get OTP settings from database config (admin panel)
        $this->otpLength = (int) \App\Models\FrontendConfig::getValue('auth.email_otp.length', 4);
        $this->otpExpiryMinutes = (int) \App\Models\FrontendConfig::getValue('auth.email_otp.expiry_minutes', 5);
        $this->maxAttempts = (int) \App\Models\FrontendConfig::getValue('auth.email_otp.max_attempts', 3);
    }

    /**
     * Send OTP to email address
     *
     * @param string $email Email address
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return array
     */
    public function sendOTP(string $email, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        try {
            // Validate email
            if (!$this->isValidEmail($email)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email address format.'
                ];
            }

            // Check if OTP was sent recently (rate limiting - 1 minute)
            $recentOTP = DB::table('email_otp_verifications')
                ->where('email', $email)
                ->where('created_at', '>', Carbon::now()->subMinute())
                ->first();

            if ($recentOTP) {
                return [
                    'success' => false,
                    'message' => 'OTP already sent. Please wait 1 minute before requesting again.'
                ];
            }

            // Generate OTP (length configured in admin panel)
            $otpCode = $this->generateOTP();

            // Send OTP via Email
            $emailResponse = $this->sendOTPEmail($email, $otpCode);

            if (!$emailResponse['success']) {
                return $emailResponse;
            }

            // Store OTP in database
            DB::table('email_otp_verifications')->insert([
                'email' => $email,
                'otp_code' => $otpCode,
                'expires_at' => Carbon::now()->addMinutes($this->otpExpiryMinutes),
                'verified' => false,
                'attempts' => 0,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            Log::info('Email OTP sent successfully', [
                'email' => $email,
                'ip' => $ipAddress
            ]);

            return [
                'success' => true,
                'message' => 'OTP sent successfully to ' . $this->maskEmail($email),
                'expires_in' => $this->otpExpiryMinutes . ' minutes'
            ];

        } catch (\Exception $e) {
            Log::error('Email OTP send failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.'
            ];
        }
    }

    /**
     * Verify Email OTP
     *
     * @param string $email
     * @param string $otpCode
     * @return array
     */
    public function verifyOTP(string $email, string $otpCode): array
    {
        try {
            Log::info('Email OTP Verification Attempt', [
                'email' => $email,
                'otp_entered' => $otpCode
            ]);

            // Get latest OTP for this email
            $otpRecord = DB::table('email_otp_verifications')
                ->where('email', $email)
                ->where('verified', false)
                ->orderBy('created_at', 'desc')
                ->first();

            Log::info('Email OTP Record Found', [
                'found' => $otpRecord ? 'yes' : 'no',
                'record' => $otpRecord
            ]);

            if (!$otpRecord) {
                return [
                    'success' => false,
                    'message' => 'No OTP found. Please request a new OTP.'
                ];
            }

            // Check if OTP is expired
            if (Carbon::parse($otpRecord->expires_at)->isPast()) {
                return [
                    'success' => false,
                    'message' => 'OTP has expired. Please request a new OTP.'
                ];
            }

            // Check if max attempts exceeded
            if ($otpRecord->attempts >= $this->maxAttempts) {
                return [
                    'success' => false,
                    'message' => 'Maximum verification attempts exceeded. Please request a new OTP.'
                ];
            }

            // Increment attempts
            DB::table('email_otp_verifications')
                ->where('id', $otpRecord->id)
                ->increment('attempts');

            // Verify OTP
            Log::info('Comparing Email OTPs', [
                'stored_otp' => $otpRecord->otp_code,
                'entered_otp' => $otpCode,
                'match' => $otpRecord->otp_code === $otpCode
            ]);

            if ($otpRecord->otp_code !== $otpCode) {
                $remainingAttempts = $this->maxAttempts - ($otpRecord->attempts + 1);
                Log::warning('Invalid Email OTP entered', [
                    'email' => $email,
                    'attempts' => $otpRecord->attempts + 1
                ]);
                return [
                    'success' => false,
                    'message' => "Invalid OTP. You have {$remainingAttempts} attempts remaining."
                ];
            }

            // Mark as verified
            DB::table('email_otp_verifications')
                ->where('id', $otpRecord->id)
                ->update([
                    'verified' => true,
                    'updated_at' => Carbon::now()
                ]);

            Log::info('Email OTP verified successfully', ['email' => $email]);

            return [
                'success' => true,
                'message' => 'OTP verified successfully!'
            ];

        } catch (\Exception $e) {
            Log::error('Email OTP verification failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Verification failed. Please try again.'
            ];
        }
    }

    /**
     * Send OTP via Email
     *
     * @param string $email
     * @param string $otpCode
     * @return array
     */
    private function sendOTPEmail(string $email, string $otpCode): array
    {
        try {
            // Get email template from config
            $template = \App\Models\FrontendConfig::getValue(
                'auth.email_otp.email_template',
                'Your verification code is: {otp}. Valid for {minutes} minutes.'
            );

            $appName = config('app.name', 'NexovaAI');
            $subject = \App\Models\FrontendConfig::getValue(
                'auth.email_otp.email_subject',
                'Your Verification Code'
            );

            // Replace placeholders
            $message = str_replace(
                ['{otp}', '{minutes}', '{app_name}'],
                [$otpCode, $this->otpExpiryMinutes, $appName],
                $template
            );

            // Debug mode: Log OTP if APP_DEBUG is true
            if (config('app.debug')) {
                Log::warning('🔐 DEBUG MODE - Email OTP Code for Testing', [
                    'email' => $email,
                    'OTP' => $otpCode,
                    'message' => 'Use this OTP for testing. Set APP_DEBUG=false in production!'
                ]);
            }

            // Send email
            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)
                    ->subject($subject);
            });

            Log::info('Email OTP sent successfully via mail', [
                'email' => $email
            ]);

            return [
                'success' => true,
                'message' => 'OTP sent successfully via email'
            ];

        } catch (\Exception $e) {
            Log::error('Email OTP mail sending failed', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send email. Please try again later.'
            ];
        }
    }

    /**
     * Generate random OTP
     *
     * @return string
     */
    private function generateOTP(): string
    {
        // Calculate max value based on OTP length (e.g., 6 digits = 999999)
        $max = (int) str_repeat('9', $this->otpLength);
        return str_pad((string) random_int(0, $max), $this->otpLength, '0', STR_PAD_LEFT);
    }

    /**
     * Validate email format
     *
     * @param string $email
     * @return bool
     */
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Mask email for display
     *
     * @param string $email
     * @return string
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) === 2) {
            $name = $parts[0];
            $domain = $parts[1];

            $maskedName = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 4)) . substr($name, -2);
            return $maskedName . '@' . $domain;
        }
        return 'email@*****.com';
    }

    /**
     * Clean expired OTP records (call this periodically)
     *
     * @return int Number of records deleted
     */
    public function cleanExpiredOTPs(): int
    {
        return DB::table('email_otp_verifications')
            ->where('expires_at', '<', Carbon::now()->subHour())
            ->delete();
    }
}
