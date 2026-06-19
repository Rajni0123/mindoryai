<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCredit extends Model
{
    protected $table = 'user_credits';

    protected $fillable = [
        'user_id',
        'available_credits',
        'total_earned',
        'total_spent',
        'unlimited_mode',
        'unlimited_until',
        'last_earned_at',
        'last_spent_at',
    ];

    protected $casts = [
        'available_credits' => 'integer',
        'total_earned' => 'integer',
        'total_spent' => 'integer',
        'unlimited_mode' => 'boolean',
        'unlimited_until' => 'datetime',
        'last_earned_at' => 'datetime',
        'last_spent_at' => 'datetime',
    ];

    /**
     * Get the user that owns the credits
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user has unlimited mode
     */
    public function hasUnlimitedMode(): bool
    {
        if (!$this->unlimited_mode) {
            return false;
        }

        // Check if unlimited mode has expired
        if ($this->unlimited_until && $this->unlimited_until->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get transactions for this user's credits
     */
    public function transactions()
    {
        return $this->hasMany(\App\Models\CreditTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Get daily usage records
     */
    public function dailyUsage()
    {
        return $this->hasMany(\App\Models\DailyUsageLimit::class, 'user_id', 'user_id');
    }
}
