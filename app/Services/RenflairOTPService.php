<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RenflairOTPService
{
    private string $apiKey;
    private int $otpLength;
    private int $otpExpiryMinutes;
    private int $maxAttempts;

    // Cache settings to avoid repeated DB queries
    private static ?array $cachedSettings = null;

    public function __construct()
    {
        $this->apiKey = config('services.renflair.api_key');

        // Get OTP settings from cache to avoid DB queries on every request
        $settings = $this->getOTPSettings();
        $this->otpLength = $settings['length'];
        $this->otpExpiryMinutes = $settings['expiry_minutes'];
        $this->maxAttempts = $settings['max_attempts'];
    }

    /**
     * Get OTP settings from cache (avoids DB query on every instantiation)
     */
    private function getOTPSettings(): array
    {
        // Use static cache for same request
        if (self::$cachedSettings !== null) {
            return self::$cachedSettings;
        }

        try {
            // Use Laravel cache for cross-request caching (5 minutes)
            self::$cachedSettings = Cache::remember('otp_service_settings', 300, function () {
                return [
                    'length' => (int) \App\Models\FrontendConfig::getValue('auth.otp.length', 4),
                    'expiry_minutes' => (int) \App\Models\FrontendConfig::getValue('auth.otp.expiry_minutes', 5),
                    'max_attempts' => (int) \App\Models\FrontendConfig::getValue('auth.otp.max_attempts', 3),
                ];
            });
        } catch (\Exception $e) {
            Log::warning('OTP settings fallback to defaults', ['error' => $e->getMessage()]);
            self::$cachedSettings = [
                'length' => 4,
                'expiry_minutes' => 5,
                'max_attempts' => 3,
            ];
        }

        return self::$cachedSettings;
    }

    /**
     * Clear cached settings (call when admin updates OTP settings)
     */
    public static function clearSettingsCache(): void
    {
        self::$cachedSettings = null;
        Cache::forget('otp_service_settings');
    }

    /**
     * Send OTP to mobile number
     *
     * @param string $mobile Mobile number (10 digits)
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return array
     */
    public function sendOTP(string $mobile, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        try {
            // Normalize mobile number (remove spaces, +91, etc.)
            $mobile = $this->normalizeMobile($mobile);

            // Validate mobile number
            if (!$this->isValidMobile($mobile)) {
                return [
                    'success' => false,
                    'message' => 'Invalid mobile number format. Please enter 10 digit mobile number.'
                ];
            }

            // Permanent test accounts — skip SMS, OTP verified directly in verifyOTP
            $permanentTestAccounts = [
                '8888888888' => '5678',
                '9999999999' => '1234',
            ];

            if (isset($permanentTestAccounts[$mobile])) {
                Log::info('Permanent test account OTP request (no SMS)', [
                    'mobile' => $this->maskMobile($mobile),
                    'ip' => $ipAddress,
                ]);

                return [
                    'success' => true,
                    'message' => 'OTP sent successfully to ' . $this->maskMobile($mobile),
                    'expires_in' => '60 minutes',
                    'is_test_account' => true,
                ];
            }

            // ═══════════════════════════════════════════════════════════════
            // Google Play Reviewer Test Account - Skip SMS, use fixed OTP
            // ═══════════════════════════════════════════════════════════════
            $testPhone = env('TEST_PHONE', '');
            $testOTP = env('TEST_OTP', '');

            if (!empty($testPhone) && $mobile === $testPhone) {
                Log::info('🧪 Google Play Reviewer Test Account - OTP request (no SMS sent)', [
                    'mobile' => $this->maskMobile($mobile),
                    'ip' => $ipAddress
                ]);

                // Invalidate previous OTPs for test account
                DB::table('otp_verifications')
                    ->where('mobile', $mobile)
                    ->where('verified', false)
                    ->update(['verified' => true, 'updated_at' => Carbon::now()]);

                // Store fixed test OTP in database
                DB::table('otp_verifications')->insert([
                    'mobile' => $mobile,
                    'otp_code' => (string) $testOTP,
                    'expires_at' => Carbon::now()->addMinutes(60), // Longer expiry for testing
                    'verified' => false,
                    'attempts' => 0,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                return [
                    'success' => true,
                    'message' => 'OTP sent successfully to ' . $this->maskMobile($mobile),
                    'expires_in' => '60 minutes'
                ];
            }
            // ═══════════════════════════════════════════════════════════════

            // Invalidate all previous unverified OTPs for this mobile
            DB::table('otp_verifications')
                ->where('mobile', $mobile)
                ->where('verified', false)
                ->update(['verified' => true, 'updated_at' => Carbon::now()]);

            // Check if OTP was sent recently (rate limiting - 1 minute)
            // Only check for unverified OTPs to avoid blocking after invalidation
            $recentOTP = DB::table('otp_verifications')
                ->where('mobile', $mobile)
                ->where('verified', false)
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

            // Send OTP via Renflair API
            $apiResponse = $this->callRenflairAPI($mobile, $otpCode);

            if (!$apiResponse['success']) {
                return $apiResponse;
            }

            // Store OTP in database - ensure OTP is stored as string
            $inserted = DB::table('otp_verifications')->insert([
                'mobile' => $mobile,
                'otp_code' => (string) $otpCode, // Explicitly cast to string
                'expires_at' => Carbon::now()->addMinutes($this->otpExpiryMinutes),
                'verified' => false,
                'attempts' => 0,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            Log::info('OTP sent and stored in database', [
                'mobile' => $this->maskMobile($mobile),
                'ip' => $ipAddress,
                'inserted' => $inserted,
                'expires_at' => Carbon::now()->addMinutes($this->otpExpiryMinutes)->toDateTimeString()
            ]);

            return [
                'success' => true,
                'message' => 'OTP sent successfully to ' . $this->maskMobile($mobile),
                'expires_in' => $this->otpExpiryMinutes . ' minutes'
            ];

        } catch (\Exception $e) {
            Log::error('OTP send failed', [
                'mobile' => $this->maskMobile($mobile),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.'
            ];
        }
    }

    /**
     * Verify OTP
     *
     * @param string $mobile
     * @param string $otpCode
     * @return array
     */
    public function verifyOTP(string $mobile, string $otpCode): array
    {
        try {
            // Normalize mobile number and OTP code
            $mobile = $this->normalizeMobile($mobile);
            $otpCode = trim($otpCode); // Remove any whitespace

            // Ensure OTP is string and not empty
            if (empty($otpCode)) {
                return [
                    'success' => false,
                    'message' => 'Please enter OTP code.'
                ];
            }

            // ═══════════════════════════════════════════════════════════════
            // Permanent Test Accounts - Never mark as verified (for testing)
            // ═══════════════════════════════════════════════════════════════
            $permanentTestAccounts = [
                '8888888888' => '5678',
                '9999999999' => '1234',
            ];

            // Check if this is a permanent test account
            if (isset($permanentTestAccounts[$mobile]) && $otpCode === $permanentTestAccounts[$mobile]) {
                Log::info('🧪 Permanent Test Account - OTP verified (not marking as used)', [
                    'mobile' => $this->maskMobile($mobile)
                ]);

                // DO NOT mark as verified - allow unlimited logins
                return [
                    'success' => true,
                    'message' => 'OTP verified successfully!',
                    'is_test_account' => true
                ];
            }

            // Google Play Reviewer Test Account from .env
            $testPhone = env('TEST_PHONE', '');
            $testOTP = env('TEST_OTP', '');

            if (!empty($testPhone) && !empty($testOTP) && $mobile === $testPhone && $otpCode === $testOTP) {
                Log::info('🧪 Google Play Reviewer Test Account - OTP verified successfully', [
                    'mobile' => $this->maskMobile($mobile)
                ]);

                // Mark any existing OTP as verified
                DB::table('otp_verifications')
                    ->where('mobile', $mobile)
                    ->where('verified', false)
                    ->update(['verified' => true, 'updated_at' => Carbon::now()]);

                return [
                    'success' => true,
                    'message' => 'OTP verified successfully!',
                    'is_test_account' => true  // Flag for controller to assign Ultimate plan
                ];
            }
            // ═══════════════════════════════════════════════════════════════

            // Get latest OTP for this mobile - single optimized query
            // This allows retry if user creation failed after OTP verification
            $otpRecord = DB::table('otp_verifications')
                ->where('mobile', $mobile)
                ->orderBy('created_at', 'desc')
                ->first();

            // Only log in debug mode to reduce overhead
            if (config('app.debug')) {
                Log::debug('OTP Verification Attempt', [
                    'mobile' => $this->maskMobile($mobile),
                    'found' => $otpRecord ? 'yes' : 'no',
                ]);
            }

            if (!$otpRecord) {
                // Check if there's any OTP at all
                $anyOTP = DB::table('otp_verifications')
                    ->where('mobile', $mobile)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$anyOTP) {
                    return [
                        'success' => false,
                        'message' => 'No OTP found for this mobile number. Please request OTP first.'
                    ];
                } else if ($anyOTP->verified) {
                    return [
                        'success' => false,
                        'message' => 'OTP already used. Please request a new OTP.'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'No active OTP found. Please request a new OTP.'
                    ];
                }
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
            DB::table('otp_verifications')
                ->where('id', $otpRecord->id)
                ->increment('attempts');

            // Verify OTP - ensure both are strings and trimmed
            $storedOTP = trim((string) $otpRecord->otp_code);
            $enteredOTP = trim((string) $otpCode);

            Log::debug('OTP comparison', [
                'stored_length' => strlen($storedOTP),
                'entered_length' => strlen($enteredOTP),
                'match' => $storedOTP === $enteredOTP,
                'attempts_before' => $otpRecord->attempts
            ]);

            if ($storedOTP !== $enteredOTP) {
                $remainingAttempts = $this->maxAttempts - ($otpRecord->attempts + 1);
                Log::warning('Invalid OTP entered', [
                    'mobile' => $this->maskMobile($mobile),
                    'attempts' => $otpRecord->attempts + 1,
                    'remaining' => $remainingAttempts
                ]);

                return [
                    'success' => false,
                    'message' => $remainingAttempts > 0
                        ? "Invalid OTP. You have {$remainingAttempts} attempt(s) remaining."
                        : "Invalid OTP. Maximum attempts exceeded. Please request a new OTP."
                ];
            }

            // Mark as verified
            DB::table('otp_verifications')
                ->where('id', $otpRecord->id)
                ->update([
                    'verified' => true,
                    'updated_at' => Carbon::now()
                ]);

            Log::info('OTP verified successfully', ['mobile' => $this->maskMobile($mobile)]);

            return [
                'success' => true,
                'message' => 'OTP verified successfully!'
            ];

        } catch (\Exception $e) {
            Log::error('OTP verification failed', [
                'mobile' => $this->maskMobile($mobile),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Verification failed. Please try again.'
            ];
        }
    }

    /**
     * Call Renflair API to send OTP with retry mechanism
     *
     * @param string $mobile
     * @param string $otpCode
     * @return array
     */
    private function callRenflairAPI(string $mobile, string $otpCode): array
    {
        try {
            // Check if OTP_SMS_ENABLED is set to false (for testing without SMS)
            // By default, SMS is enabled
            $smsEnabled = env('OTP_SMS_ENABLED', true);

            if (!$smsEnabled) {
                // In local dev only, log OTP for testing. NEVER log in production.
                if (config('app.env') === 'local') {
                    Log::info('🔐 LOCAL DEV ONLY - OTP for testing', [
                        'mobile' => $this->maskMobile($mobile),
                        'otp' => $otpCode
                    ]);
                } else {
                    Log::info('SMS disabled mode - OTP sent to masked mobile', [
                        'mobile' => $this->maskMobile($mobile)
                    ]);
                }

                // Simulate successful API response for testing
                return [
                    'success' => true,
                    'message' => 'OTP sent (SMS disabled for testing)'
                ];
            }

            // PRODUCTION MODE: Send via Renflair API using cURL with retry
            $API = $this->apiKey;
            $PHONE = $mobile;
            $OTP = $otpCode;
            $URL = "https://sms.renflair.in/V1.php?API=$API&PHONE=$PHONE&OTP=$OTP";

            // DEBUG MODE: Only log OTP in local development, never in production
            if (config('app.env') === 'local' && config('app.debug')) {
                Log::warning('🔐 LOCAL DEV ONLY - OTP for testing', [
                    'mobile' => $this->maskMobile($mobile),
                    'otp' => $otpCode
                ]);
            }

            Log::info('📤 Sending OTP via Renflair SMS API', [
                'mobile' => $this->maskMobile($mobile)
            ]);

            // Retry configuration
            $maxRetries = 2;
            $retryDelay = 1; // seconds
            $lastError = null;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                // Initialize cURL with optimized timeout
                $curl = curl_init($URL);
                curl_setopt($curl, CURLOPT_URL, $URL);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                // SSL verification - enabled in production, disabled only in local dev
                $sslVerify = config('app.env') !== 'local';
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $sslVerify);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : 0);
                // Increased timeout for better reliability (15s total, 8s connect)
                curl_setopt($curl, CURLOPT_TIMEOUT, 15);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 8);
                // DNS cache to speed up subsequent requests
                curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 300);

                $resp = curl_exec($curl);
                $curlError = curl_error($curl);
                $curlErrno = curl_errno($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                // Check for timeout errors specifically
                $isTimeout = in_array($curlErrno, [CURLE_OPERATION_TIMEDOUT, CURLE_COULDNT_CONNECT, 28, 7]);

                // Check for cURL errors
                if ($curlError) {
                    $lastError = $curlError;
                    Log::warning("⚠️ SMS API attempt $attempt failed", [
                        'error' => $curlError,
                        'errno' => $curlErrno,
                        'is_timeout' => $isTimeout
                    ]);

                    // Retry on timeout or connection errors
                    if ($isTimeout && $attempt < $maxRetries) {
                        sleep($retryDelay);
                        continue;
                    }

                    return [
                        'success' => false,
                        'message' => $isTimeout
                            ? 'SMS service is slow. Please try again.'
                            : 'Failed to connect to SMS gateway'
                    ];
                }

                // Log the response for debugging (no sensitive data)
                Log::info('📥 Renflair SMS API Response', [
                    'http_code' => $httpCode,
                    'status' => json_decode($resp, true)['status'] ?? 'unknown',
                    'attempt' => $attempt
                ]);

                // Decode JSON response
                $data = json_decode($resp, true);

                // Check if request was successful (HTTP 200 AND status is SUCCESS)
                if ($httpCode == 200 && isset($data['status']) && $data['status'] === 'SUCCESS') {
                    Log::info('✅ Renflair SMS API Success', ['attempt' => $attempt]);

                    return [
                        'success' => true,
                        'message' => 'OTP sent successfully via SMS'
                    ];
                }

                // If API returned error, don't retry (it's not a timeout issue)
                break;
            }

            // If API call failed - check for specific error messages
            $errorMessage = 'Failed to send OTP via SMS service. Please contact support.';
            if (isset($data['message'])) {
                if (str_contains($data['message'], 'BALANCE')) {
                    $errorMessage = 'API balance exhausted. Please recharge your Renflair account.';
                } else {
                    $errorMessage = $data['message'];
                }
            }

            Log::error('❌ Renflair SMS API Failed', [
                'http_code' => $httpCode ?? 0,
                'error_message' => $errorMessage,
                'last_curl_error' => $lastError
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error('Renflair API exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'SMS service is temporarily unavailable.'
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
     * Validate mobile number format
     *
     * @param string $mobile
     * @return bool
     */
    private function isValidMobile(string $mobile): bool
    {
        // Indian mobile number: 10 digits starting with 6-9
        return preg_match('/^[6-9]\d{9}$/', $mobile) === 1;
    }

    /**
     * Normalize mobile number
     *
     * @param string $mobile
     * @return string
     */
    private function normalizeMobile(string $mobile): string
    {
        // Remove all non-numeric characters
        $mobile = preg_replace('/[^0-9]/', '', $mobile);

        // Remove +91 or 91 prefix if present
        if (strlen($mobile) > 10) {
            if (str_starts_with($mobile, '91')) {
                $mobile = substr($mobile, 2);
            }
        }

        return $mobile;
    }

    /**
     * Mask mobile number for display
     *
     * @param string $mobile
     * @return string
     */
    private function maskMobile(string $mobile): string
    {
        if (strlen($mobile) === 10) {
            return substr($mobile, 0, 2) . 'XXXXXX' . substr($mobile, -2);
        }
        return 'XXXXXXXXXX';
    }

    /**
     * Clean expired OTP records (call this periodically)
     *
     * @return int Number of records deleted
     */
    public function cleanExpiredOTPs(): int
    {
        return DB::table('otp_verifications')
            ->where('expires_at', '<', Carbon::now()->subHour())
            ->delete();
    }

    /**
     * Send OTP to mobile number via WhatsApp
     *
     * @param string $mobile Mobile number (10 digits)
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return array
     */
    public function sendWhatsAppOTP(string $mobile, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        try {
            // Normalize mobile number (remove spaces, +91, etc.)
            $mobile = $this->normalizeMobile($mobile);

            // Validate mobile number
            if (!$this->isValidMobile($mobile)) {
                return [
                    'success' => false,
                    'message' => 'Invalid mobile number format. Please enter 10 digit mobile number.'
                ];
            }

            // ═══════════════════════════════════════════════════════════════
            // Google Play Reviewer Test Account - Skip WhatsApp, use fixed OTP
            // ═══════════════════════════════════════════════════════════════
            $testPhone = env('TEST_PHONE', '');
            $testOTP = env('TEST_OTP', '');

            if (!empty($testPhone) && $mobile === $testPhone) {
                Log::info('🧪 Google Play Reviewer Test Account - WhatsApp OTP request (no message sent)', [
                    'mobile' => $this->maskMobile($mobile),
                    'ip' => $ipAddress
                ]);

                // Invalidate previous OTPs for test account
                DB::table('otp_verifications')
                    ->where('mobile', $mobile)
                    ->where('verified', false)
                    ->update(['verified' => true, 'updated_at' => Carbon::now()]);

                // Store fixed test OTP in database
                DB::table('otp_verifications')->insert([
                    'mobile' => $mobile,
                    'otp_code' => (string) $testOTP,
                    'expires_at' => Carbon::now()->addMinutes(60),
                    'verified' => false,
                    'attempts' => 0,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                return [
                    'success' => true,
                    'message' => 'OTP sent to your WhatsApp',
                    'expires_in' => 3600 // 60 minutes in seconds
                ];
            }
            // ═══════════════════════════════════════════════════════════════

            // Invalidate all previous unverified OTPs for this mobile
            DB::table('otp_verifications')
                ->where('mobile', $mobile)
                ->where('verified', false)
                ->update(['verified' => true, 'updated_at' => Carbon::now()]);

            // Check if OTP was sent recently (rate limiting - 1 minute)
            $recentOTP = DB::table('otp_verifications')
                ->where('mobile', $mobile)
                ->where('verified', false)
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

            // Send OTP via Renflair WhatsApp API
            $apiResponse = $this->callRenflairWhatsAppAPI($mobile, $otpCode);

            if (!$apiResponse['success']) {
                return $apiResponse;
            }

            // Store OTP in database
            DB::table('otp_verifications')->insert([
                'mobile' => $mobile,
                'otp_code' => (string) $otpCode,
                'created_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMinutes($this->otpExpiryMinutes),
                'verified' => false,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            Log::info('WhatsApp OTP sent successfully', [
                'mobile' => $this->maskMobile($mobile),
                'expires_in' => $this->otpExpiryMinutes . ' minutes',
                'ip' => $ipAddress,
            ]);

            return [
                'success' => true,
                'message' => "OTP sent to your WhatsApp",
                'expires_in' => $this->otpExpiryMinutes * 60, // in seconds
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp OTP sending failed', [
                'mobile' => $this->maskMobile($mobile),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send WhatsApp OTP. Please try again.'
            ];
        }
    }

    /**
     * Call Renflair WhatsApp API with retry mechanism
     *
     * @param string $mobile
     * @param string $otpCode
     * @return array
     */
    private function callRenflairWhatsAppAPI(string $mobile, string $otpCode): array
    {
        try {
            // Get WhatsApp API key from environment
            $apiKey = env('RENFLAIR_WHATSAPP_API_KEY', '');

            if (empty($apiKey)) {
                Log::error('Renflair WhatsApp API key not configured');
                return [
                    'success' => false,
                    'message' => 'WhatsApp service not configured properly'
                ];
            }

            // Renflair WhatsApp API - Using dedicated WhatsApp endpoint
            $API = $apiKey;
            $COUNTRY = '91'; // Country Code for India
            $PHONE = $mobile;
            $OTP = $otpCode;
            $SENDER = urlencode('BlinkStudy'); // Business name
            $URL = "https://whatsapp.renflair.in/V1.php?API=$API&PHONE=$PHONE&OTP=$OTP&COUNTRY=$COUNTRY&SENDER=$SENDER";

            // DEBUG MODE: Only log OTP in local development, never in production
            if (config('app.env') === 'local' && config('app.debug')) {
                Log::warning('🔐 LOCAL DEV ONLY - WhatsApp OTP for testing', [
                    'mobile' => $this->maskMobile($mobile),
                    'otp' => $otpCode
                ]);
            }

            Log::info('📤 Sending OTP via Renflair WhatsApp API', [
                'mobile' => $this->maskMobile($mobile)
            ]);

            // Retry configuration
            $maxRetries = 2;
            $retryDelay = 1; // seconds
            $lastError = null;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                // Initialize cURL with optimized timeout
                $curl = curl_init($URL);
                curl_setopt($curl, CURLOPT_URL, $URL);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                // SSL verification - enabled in production, disabled only in local dev
                $sslVerify = config('app.env') !== 'local';
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $sslVerify);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : 0);
                // Increased timeout for better reliability (15s total, 8s connect)
                curl_setopt($curl, CURLOPT_TIMEOUT, 15);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 8);
                // DNS cache to speed up subsequent requests
                curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 300);

                $resp = curl_exec($curl);
                $curlError = curl_error($curl);
                $curlErrno = curl_errno($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                // Check for timeout errors specifically
                $isTimeout = in_array($curlErrno, [CURLE_OPERATION_TIMEDOUT, CURLE_COULDNT_CONNECT, 28, 7]);

                // Check for cURL errors
                if ($curlError) {
                    $lastError = $curlError;
                    Log::warning("⚠️ WhatsApp API attempt $attempt failed", [
                        'error' => $curlError,
                        'errno' => $curlErrno,
                        'is_timeout' => $isTimeout
                    ]);

                    // Retry on timeout or connection errors
                    if ($isTimeout && $attempt < $maxRetries) {
                        sleep($retryDelay);
                        continue;
                    }

                    return [
                        'success' => false,
                        'message' => $isTimeout
                            ? 'WhatsApp service is slow. Please try again.'
                            : 'Failed to connect to WhatsApp gateway'
                    ];
                }

                // Log the response for debugging (no sensitive data)
                Log::info('📥 Renflair WhatsApp API Response', [
                    'http_code' => $httpCode,
                    'status' => json_decode($resp, true)['status'] ?? 'unknown',
                    'attempt' => $attempt
                ]);

                // Decode JSON response
                $data = json_decode($resp, true);

                // Check if request was successful (HTTP 200 AND status is SUCCESS)
                if ($httpCode == 200 && isset($data['status']) && $data['status'] === 'SUCCESS') {
                    Log::info('✅ Renflair WhatsApp API Success', ['attempt' => $attempt]);

                    return [
                        'success' => true,
                        'message' => 'OTP sent successfully via WhatsApp'
                    ];
                }

                // If API returned error, don't retry (it's not a timeout issue)
                break;
            }

            // If API call failed - check for specific error messages
            $errorMessage = 'Failed to send WhatsApp OTP. Please try again.';
            if (isset($data['message'])) {
                if (str_contains($data['message'], 'BALANCE')) {
                    $errorMessage = 'API balance exhausted. Please recharge your Renflair account.';
                } else {
                    $errorMessage = $data['message'];
                }
            }

            Log::error('❌ Renflair WhatsApp API Failed', [
                'http_code' => $httpCode ?? 0,
                'error_message' => $errorMessage,
                'last_curl_error' => $lastError
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error('Renflair WhatsApp API exception', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send WhatsApp OTP. Please try again.'
            ];
        }
    }
}
