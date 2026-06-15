<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'seasonal_pack_id',
        'billing_cycle',
        'status',
        'razorpay_subscription_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'start_date',
        'end_date',
        'starts_at',
        'expires_at',
        'cancelled_at',
        'credits_included',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TRIAL = 'trial';

    // Billing cycle constants
    public const CYCLE_MONTHLY = 'monthly';
    public const CYCLE_ANNUAL = 'annual';
    public const CYCLE_SEASONAL = 'seasonal';

    /**
     * Get the user that owns this subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan for this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the seasonal pack (if applicable).
     */
    public function seasonalPack(): BelongsTo
    {
        return $this->belongsTo(SeasonalPack::class);
    }

    /**
     * Scope to get only active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Check if this subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->expires_at->gt(Carbon::now());
    }

    /**
     * Check if this subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->lt(Carbon::now());
    }

    /**
     * Get days remaining in subscription.
     */
    public function getDaysRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        return Carbon::now()->diffInDays($this->expires_at, false);
    }

    /**
     * Cancel this subscription.
     */
    public function cancel(): bool
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = Carbon::now();
        return $this->save();
    }

    /**
     * Mark as expired.
     */
    public function expire(): bool
    {
        $this->status = self::STATUS_EXPIRED;
        return $this->save();
    }

    /**
     * Get remaining days attribute (for backward compatibility)
     */
    public function getRemainingDaysAttribute(): int
    {
        return $this->getDaysRemaining();
    }
}
