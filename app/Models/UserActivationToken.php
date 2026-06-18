<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserActivationToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_hash',
        'raw_token',
        'user_id',
        'plan_id',
        'is_used',
        'is_active',
        'expires_at',
        'activated_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with UserPlan
     */
    public function plan()
    {
        return $this->belongsTo(UserPlan::class, 'plan_id');
    }

    /**
     * Generate a new activation token
     */
    public static function generateToken($planId, $expiryDays = null)
    {
        $rawToken = Str::random(32);
        $tokenHash = hash('sha256', $rawToken);

        $token = self::create([
            'token_hash' => $tokenHash,
            'raw_token' => $rawToken,
            'plan_id' => $planId,
            'is_used' => false,
            'is_active' => true,
            'expires_at' => $expiryDays ? now()->addDays($expiryDays) : null,
        ]);

        return $token;
    }

    /**
     * Verify and activate token for a user
     */
    public static function verifyAndActivate($rawToken, $userId)
    {
        $tokenHash = hash('sha256', $rawToken);

        $token = self::where('token_hash', $tokenHash)
            ->where('is_used', false)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$token) {
            return false;
        }

        // Mark token as used and associate with user
        $token->update([
            'is_used' => true,
            'user_id' => $userId,
            'activated_at' => now(),
            'raw_token' => null, // Remove raw token for security
        ]);

        return $token;
    }

    /**
     * Check if token is valid
     */
    public function isValid()
    {
        if ($this->is_used || !$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        return true;
    }
}
