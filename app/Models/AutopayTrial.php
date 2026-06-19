<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutopayTrial extends Model
{
    public const STATUS_PENDING = 'pending_auth';
    public const STATUS_TRIAL = 'trial_active';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_HALTED = 'halted';

    protected $fillable = [
        'user_id',
        'razorpay_subscription_id',
        'status',
        'plan_slug',
        'trial_price',
        'renewal_price',
        'trial_starts_at',
        'trial_ends_at',
        'next_billing_at',
        'cancelled_at',
        'meta',
    ];

    protected $casts = [
        'trial_starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTrialActive(): bool
    {
        return $this->status === self::STATUS_TRIAL
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }
}
