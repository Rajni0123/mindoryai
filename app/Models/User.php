<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['role'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',
        'mobile_verified_at',
        'student_class',
        'token_limit',
        'tokens_used',
        'is_active',
        'can_use_gpt4',
        'can_use_claude',
        'can_use_deepseek',
        'can_use_grok',
        'last_token_reset',
        'plan_id',
        'plan_expires_at',
        'token_activated',
        'token_activated_at',
        // 2FA fields
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        // Security fields
        'failed_login_attempts',
        'locked_until',
        'last_login_ip',
        'last_login_user_agent',
        'last_login_at',
        'last_activity_at',
        // Profile completion
        'profile_completed',
        'profile_completed_at',
        // Referral system
        'referral_code',
        'referred_by',
        'referral_applied_at',
        'referral_reward_given',
        'referral_count',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'credits',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'can_use_gpt4' => 'boolean',
            'can_use_claude' => 'boolean',
            'can_use_deepseek' => 'boolean',
            'can_use_grok' => 'boolean',
            'last_token_reset' => 'datetime',
            'plan_expires_at' => 'datetime',
            'token_activated' => 'boolean',
            'token_activated_at' => 'datetime',
            // 2FA casts
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            // Security casts
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            // Profile completion casts
            'profile_completed' => 'boolean',
            'profile_completed_at' => 'datetime',
            // Referral system casts
            'referral_applied_at' => 'datetime',
            'referral_reward_given' => 'boolean',
            'referral_count' => 'integer',
        ];
    }

    /**
     * Check if user has completed their profile
     */
    public function hasCompletedProfile(): bool
    {
        return $this->profile_completed === true;
    }

    /**
     * Mark user's profile as completed
     */
    public function markProfileCompleted(): void
    {
        $this->update([
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);
    }

    /**
     * Check if user has remaining tokens
     */
    public function hasTokensRemaining(): bool
    {
        return $this->tokens_used < $this->token_limit;
    }

    /**
     * Get remaining tokens
     */
    public function getRemainingTokens(): int
    {
        return max(0, $this->token_limit - $this->tokens_used);
    }

    /**
     * Get token usage percentage
     */
    public function getTokenUsagePercentage(): float
    {
        if ($this->token_limit == 0) {
            return 100;
        }
        return round(($this->tokens_used / $this->token_limit) * 100, 2);
    }

    /**
     * Relationship with activation tokens
     */
    public function activationTokens()
    {
        return $this->hasMany(UserActivationToken::class);
    }


    /**
     * Relationship with IP whitelist
     */
    public function ipWhitelist()
    {
        return $this->hasMany(UserIpWhitelist::class);
    }

    /**
     * Relationship with usage logs
     */
    public function usageLogs()
    {
        return $this->hasMany(UsageLog::class);
    }

    /**
     * Relationship with user plan
     */
    public function userPlan()
    {
        return $this->belongsTo(UserPlan::class, 'plan_id');
    }

    /**
     * Alias for userPlan - for clarity
     */
    public function plan()
    {
        return $this->belongsTo(UserPlan::class, 'plan_id');
    }

    /**
     * Relationship with payments
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if user's IP is whitelisted
     */
    public function isIpWhitelisted($ipAddress)
    {
        return UserIpWhitelist::isWhitelistedForUser($this->id, $ipAddress);
    }

    /**
     * Check if user can access AI features
     */
    public function canAccessAI()
    {
        // User must be active
        if (!$this->is_active) {
            return false;
        }

        // User must have tokens remaining
        if (!$this->hasTokensRemaining()) {
            return false;
        }

        return true;
    }

    /**
     * Consume tokens
     */
    public function consumeTokens($amount)
    {
        $this->tokens_used += $amount;
        $this->save();

        // If limit reached, deactivate user
        if (!$this->hasTokensRemaining()) {
            $this->update(['is_active' => false]);
        }
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is regular user
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if user account is locked
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Lock user account for specified minutes
     */
    public function lockAccount(int $minutes = 15): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Unlock user account
     */
    public function unlockAccount(): void
    {
        $this->update([
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);
    }

    /**
     * Increment failed login attempts
     */
    public function incrementLoginAttempts(): void
    {
        $this->increment('failed_login_attempts');

        // Lock account after 5 failed attempts
        if ($this->failed_login_attempts >= 5) {
            $this->lockAccount(15); // Lock for 15 minutes
        }
    }

    /**
     * Reset failed login attempts
     */
    public function resetLoginAttempts(): void
    {
        $this->update(['failed_login_attempts' => 0]);
    }

    /**
     * Update last login information
     */
    public function updateLastLogin(Request $request): void
    {
        $this->update([
            'last_login_ip' => $request->ip(),
            'last_login_user_agent' => $request->userAgent(),
            'last_login_at' => now(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Update last activity timestamp
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Check if user session has expired (15 minutes of inactivity)
     */
    public function hasSessionExpired(): bool
    {
        if (!$this->last_activity_at) {
            return false;
        }

        return $this->last_activity_at->diffInMinutes(now()) > 15;
    }

    /**
     * Get user's used credits (messages sent)
     *
     * @return int
     */
    public function getCreditsAttribute(): int
    {
        try {
            // Use CreditService to get balance (will initialize if needed)
            $creditService = app(\App\Services\CreditService::class);
            $availableCredits = $creditService->getBalance($this);

            // If unlimited mode, return 0 (they haven't used any credits)
            if ($availableCredits >= 999999) {
                return 0;
            }

            // Get total tokens from plan
            $totalTokens = 50; // Default for free users
            if ($this->plan_id) {
                $plan = $this->plan; // Use relationship instead of DB query
                if ($plan && $plan->message_tokens) {
                    $totalTokens = $plan->message_tokens;
                }
            }

            // Calculate used credits
            $usedCredits = $totalTokens - $availableCredits;

            // Return minimum of used credits (can't be negative)
            return max(0, $usedCredits);
        } catch (\Exception $e) {
            // Log error but don't fail - return 0 as fallback
            \Log::error('Error getting credits attribute', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 0;
        }
    }

    /**
     * Get user's credit information
     */
    public function userCredits()
    {
        return $this->hasOne(UserCredit::class);
    }

    /**
     * Get user's subscription
     */
    public function subscription()
    {
        return $this->hasOne(UserSubscription::class)->latest();
    }

    /**
     * Get the user who referred this user
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Get users referred by this user
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * Generate a unique referral code for this user
     */
    public function generateReferralCode(): string
    {
        if ($this->referral_code) {
            return $this->referral_code;
        }

        do {
            // Generate a random 8-character code
            $code = strtoupper(substr(str_replace(['0', 'O', '1', 'I'], '', uniqid('', true)), 0, 8));
            // Or use a more readable format like MINDO-XXXX
            $code = 'MINDO' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (User::where('referral_code', $code)->exists());

        $this->update(['referral_code' => $code]);
        return $code;
    }

    /**
     * Check if user has a referral code
     */
    public function hasReferralCode(): bool
    {
        return !empty($this->referral_code);
    }

    /**
     * Apply a referral code to this user
     */
    public function applyReferralCode(string $code): bool
    {
        // Prevent self-referral
        if ($this->referral_code === $code) {
            return false;
        }

        // Find referrer by code
        $referrer = User::where('referral_code', $code)->first();

        if (!$referrer) {
            return false;
        }

        // Update user with referral info
        $this->update([
            'referred_by' => $referrer->id,
            'referral_applied_at' => now(),
        ]);

        // Increment referrer's referral count
        $referrer->increment('referral_count');

        // Give rewards (3 credits each)
        try {
            $creditService = app(\App\Services\CreditService::class);

            // Give 3 credits to the referrer
            $creditService->addCredits(
                $referrer,
                3,
                'referral_reward',
                "Referral bonus: User #{$this->id} used your code",
                null,
                $this->id
            );

            // Give 3 credits to the new user
            $creditService->addCredits(
                $this,
                3,
                'referral_signup_bonus',
                "Signup bonus: Used referral code from {$referrer->name}",
                null,
                $referrer->id
            );

            // Mark reward as given
            $referrer->update(['referral_reward_given' => true]);

            \Log::info('Referral code applied successfully', [
                'referrer_id' => $referrer->id,
                'new_user_id' => $this->id,
                'code' => $code,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to give referral rewards', [
                'error' => $e->getMessage(),
                'referrer_id' => $referrer->id,
                'new_user_id' => $this->id,
            ]);
            return false;
        }
    }

    /**
     * Get referral stats
     */
    public function getReferralStats(): array
    {
        return [
            'referral_code' => $this->referral_code,
            'referral_count' => $this->referral_count,
            'total_earned' => $this->referral_count * 3, // 3 credits per referral
            'has_applied_code' => !empty($this->referred_by),
        ];
    }
}
