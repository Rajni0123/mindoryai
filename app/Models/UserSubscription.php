<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'start_date',
        'end_date',
        'auto_renew',
        'credits_included',
        'features',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'auto_renew' => 'boolean',
        'features' => 'array',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan for this subscription
     */
    public function plan()
    {
        return $this->belongsTo(PricingPlan::class, 'plan_id');
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               (!$this->end_date || $this->end_date->isFuture());
    }

    /**
     * Get remaining days
     */
    public function getRemainingDaysAttribute(): int
    {
        if (!$this->end_date) {
            return -1; // Unlimited
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }
}
