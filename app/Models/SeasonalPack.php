<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class SeasonalPack extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'duration_days',
        'plan_id',
        'description',
        'tag',
        'icon',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'price' => 'integer',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Get the plan this pack gives access to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the subscriptions for this seasonal pack.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Scope to get only active packs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only currently available packs (within date range).
     */
    public function scopeAvailable($query)
    {
        $now = Carbon::now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    /**
     * Check if this pack is currently available.
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        return true;
    }

    /**
     * Get the expiry date if purchased now.
     */
    public function getExpiryDate(): Carbon
    {
        return Carbon::now()->addDays($this->duration_days);
    }
}
