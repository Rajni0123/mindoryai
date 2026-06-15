<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OTPVerification extends Model
{
    use HasFactory;

    protected $table = 'otp_verifications';

    protected $fillable = [
        'mobile',
        'otp_code',
        'expires_at',
        'verified',
        'attempts',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'expires_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * Check if OTP is expired
     */
    public function isExpired(): bool
    {
        return Carbon::now()->isAfter($this->expires_at);
    }

    /**
     * Check if OTP is valid (not expired and not verified)
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->verified;
    }

    /**
     * Mark OTP as verified
     */
    public function markVerified(): void
    {
        $this->update([
            'verified' => true,
        ]);
    }

    /**
     * Scope: Get valid OTP for phone number
     */
    public function scopeValidForPhone($query, string $phoneNumber, string $otpCode)
    {
        return $query->where('mobile', $phoneNumber)
            ->where('otp_code', $otpCode)
            ->where('verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->latest();
    }

    /**
     * Scope: Get valid OTP for email - Not supported with current schema
     */
    public function scopeValidForEmail($query, string $email, string $otpCode)
    {
        // Email OTP not supported with current schema
        return $query->whereRaw('1=0'); // Return empty
    }

    /**
     * Generate a new OTP code (4 digits)
     */
    public static function generateCode(int $length = 4): string
    {
        return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Create OTP for phone number
     */
    public static function createForPhone(string $phoneNumber, int $expiryMinutes = 10): self
    {
        return self::create([
            'mobile' => $phoneNumber,
            'otp_code' => self::generateCode(),
            'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
            'verified' => false,
            'attempts' => 0,
        ]);
    }

    /**
     * Create OTP for email - Not supported with current schema
     */
    public static function createForEmail(string $email, int $expiryMinutes = 10): self
    {
        throw new \Exception('Email OTP not supported with current schema');
    }

    /**
     * Clean up expired OTPs
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', Carbon::now()->subHours(24))->delete();
    }
}
