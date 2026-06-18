<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AccessCode extends Model
{
    protected $fillable = [
        'code',
        'plan',
        'usage_limit',
        'usage_count',
        'expires_at',
        'is_active',
        'description',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Verify if code is valid
     */
    public static function verify(string $code): bool
    {
        $accessCode = self::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$accessCode) {
            return false;
        }

        // Check if expired
        if ($accessCode->expires_at && $accessCode->expires_at->isPast()) {
            return false;
        }

        // Check usage limit
        if ($accessCode->usage_limit && $accessCode->usage_count >= $accessCode->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Use the access code
     */
    public function use(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get plan details
     */
    public function getPlanDetails(): array
    {
        return match($this->plan) {
            'basic' => [
                'name' => 'Basic Plan',
                'features' => ['Text Questions', 'Image Analysis', '50 requests/day'],
            ],
            'premium' => [
                'name' => 'Premium Plan',
                'features' => ['Text Questions', 'Image Analysis', 'Unlimited requests', 'Priority Support'],
            ],
            'lifetime' => [
                'name' => 'Lifetime Access',
                'features' => ['Everything', 'Unlimited forever', 'Premium Support'],
            ],
            default => [
                'name' => 'Unknown Plan',
                'features' => [],
            ],
        };
    }
}
