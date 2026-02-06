<?php

namespace App\Services;

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

    public function __construct()
    {
        $this->apiKey = config('services.renflair.api_key');

        // Get OTP settings from database config (admin panel)
        $this->otpLength = (int) \App\Models\FrontendConfig::getValue('auth.otp.length', 4);
        $this->otpExpiryMinutes = (int) \App\Models\FrontendConfig::getValue('auth.otp.expiry_minutes', 5);
        $this->maxAttempts = (int) \App\Models\FrontendConfig::getValue('auth.otp.max_attempts', 3);
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
                'mobile' => $mobile,
                'ip' => $ipAddress,
                'otp_code' => $otpCode,
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
                'mobile' => $mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

            Log::info('OTP Verification Attempt', [
                'mobile' => $mobile,
                'otp_entered' => $otpCode,
                'otp_length' => strlen($otpCode)
            ]);

            // Get latest OTP for this mobile (verified or unverified)
            // This allows retry if user creation failed after OTP verification
            $otpRecord = DB::table('otp_verifications')
                ->where('mobile', $mobile)
                ->orderBy('created_at', 'desc')
                ->first();

            // Check all OTPs for this mobile for debugging
            $allOTPs = DB::table('otp_verifications')
                ->where('mobile', $mobile)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            Log::info('All OTPs for mobile', [
                'mobile' => $mobile,
                'count' => $allOTPs->count(),
                'otps' => $allOTPs->map(function($otp) {
                    return [
                        'id' => $otp->id,
                        'otp_code' => $otp->otp_code,
                        'verified' => $otp->verified,
                        'attempts' => $otp->attempts,
                        'created_at' => $otp->created_at,
                        'expires_at' => $otp->expires_at
                    ];
                })
            ]);

            Log::info('Latest Unverified OTP Record', [
                'found' => $otpRecord ? 'yes' : 'no',
                'record' => $otpRecord
            ]);

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

            Log::info('Comparing OTPs', [
                'stored_otp' => $storedOTP,
                'entered_otp' => $enteredOTP,
                'stored_type' => gettype($storedOTP),
                'entered_type' => gettype($enteredOTP),
                'stored_length' => strlen($storedOTP),
                'entered_length' => strlen($enteredOTP),
                'match' => $storedOTP === $enteredOTP,
                'attempts_before' => $otpRecord->attempts
            ]);

            if ($storedOTP !== $enteredOTP) {
                $remainingAttempts = $this->maxAttempts - ($otpRecord->attempts + 1);
                Log::warning('Invalid OTP entered', [
                    'mobile' => $mobile,
                    'attempts' => $otpRecord->attempts + 1,
                    'remaining' => $remainingAttempts,
                    'stored_otp' => $storedOTP,
                    'entered_otp' => $enteredOTP
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

            Log::info('OTP verified successfully', ['mobile' => $mobile]);

            return [
                'success' => true,
                'message' => 'OTP verified successfully!'
            ];

        } catch (\Exception $e) {
            Log::error('OTP verification failed', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Verification failed. Please try again.'
            ];
        }
    }

    /**
     * Call Renflair API to send OTP
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
                Log::info('🔐 SMS DISABLED - OTP Code (for testing)', [
                    'mobile' => $mobile,
                    'OTP' => $otpCode,
                    'message' => 'SMS is disabled. Set OTP_SMS_ENABLED=true in .env to enable actual SMS'
                ]);

                // Simulate successful API response for testing
                return [
                    'success' => true,
                    'message' => 'OTP logged in console (SMS disabled for testing)'
                ];
            }

            // PRODUCTION MODE: Send via Renflair API using cURL
            // Renflair V1 API - GET request with query parameters
            $API = $this->apiKey;
            $PHONE = $mobile;
            $OTP = $otpCode;
            $URL = "https://sms.renflair.in/V1.php?API=$API&PHONE=$PHONE&OTP=$OTP";

            // 🔍 DEBUG MODE: Always log OTP when APP_DEBUG=true (helps with testing SMS delivery issues)
            if (config('app.debug')) {
                Log::warning('🔐 DEBUG MODE - OTP Code for Testing', [
                    'mobile' => $mobile,
                    'OTP' => $otpCode,
                    'API_URL' => $URL,
                    'message' => 'Use this OTP if SMS is not delivered. Set APP_DEBUG=false in production!'
                ]);
            }

            Log::info('📤 Sending OTP via Renflair cURL API', [
                'url' => $URL,
                'mobile' => $mobile,
                'otp' => $otpCode,
            ]);

            // Initialize cURL
            $curl = curl_init($URL);
            curl_setopt($curl, CURLOPT_URL, $URL);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Skip SSL verification (for Windows/local dev)
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);

            $resp = curl_exec($curl);
            $curlError = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            // Check for cURL errors
            if ($curlError) {
                Log::error('❌ cURL Error', [
                    'error' => $curlError,
                    'mobile' => $mobile
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to connect to SMS gateway'
                ];
            }

            // Log the response for debugging
            Log::info('📥 Renflair API Response', [
                'http_code' => $httpCode,
                'response' => $resp,
                'mobile' => $mobile
            ]);

            // Decode JSON response
            $data = json_decode($resp, true);

            // Check if request was successful (HTTP 200 AND status is SUCCESS)
            if ($httpCode == 200 && isset($data['status']) && $data['status'] === 'SUCCESS') {
                Log::info('✅ Renflair API Success', [
                    'response' => $data,
                    'mobile' => $mobile
                ]);

                return [
                    'success' => true,
                    'message' => 'OTP sent successfully via SMS'
                ];
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

            Log::error('❌ Renflair API Failed', [
                'http_code' => $httpCode,
                'response' => $resp,
                'mobile' => $mobile,
                'error_message' => $errorMessage
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
                'mobile' => $mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send WhatsApp OTP. Please try again.'
            ];
        }
    }

    /**
     * Call Renflair WhatsApp API
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

            // 🔍 DEBUG MODE: Always log OTP when APP_DEBUG=true
            if (config('app.debug')) {
                Log::warning('🔐 DEBUG MODE - WhatsApp OTP Code for Testing', [
                    'mobile' => $mobile,
                    'OTP' => $otpCode,
                    'API_URL' => $URL,
                    'message' => 'Use this OTP if WhatsApp is not delivered. Set APP_DEBUG=false in production!'
                ]);
            }

            Log::info('📤 Sending WhatsApp OTP via Renflair cURL API', [
                'url' => $URL,
                'mobile' => $mobile,
                'otp' => $otpCode,
                'country' => $COUNTRY
            ]);

            // Initialize cURL
            $curl = curl_init($URL);
            curl_setopt($curl, CURLOPT_URL, $URL);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Skip SSL verification
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);

            $resp = curl_exec($curl);
            $curlError = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            // Check for cURL errors
            if ($curlError) {
                Log::error('❌ cURL Error', [
                    'error' => $curlError,
                    'mobile' => $mobile,
                    'type' => 'whatsapp'
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to connect to WhatsApp gateway'
                ];
            }

            // Log the response for debugging
            Log::info('📥 Renflair WhatsApp API Response', [
                'http_code' => $httpCode,
                'response' => $resp,
                'mobile' => $mobile
            ]);

            // Decode JSON response
            $data = json_decode($resp, true);

            // Check if request was successful (HTTP 200 AND status is SUCCESS)
            if ($httpCode == 200 && isset($data['status']) && $data['status'] === 'SUCCESS') {
                Log::info('✅ Renflair WhatsApp API Success', [
                    'response' => $data,
                    'mobile' => $mobile
                ]);

                return [
                    'success' => true,
                    'message' => 'OTP sent successfully via WhatsApp'
                ];
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
                'http_code' => $httpCode,
                'response' => $resp,
                'mobile' => $mobile,
                'error_message' => $errorMessage
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error('Renflair WhatsApp API exception', [
                'error' => $e->getMessage(),
                'mobile' => $mobile,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send WhatsApp OTP. Please try again.'
            ];
        }
    }
}
