<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_annual',
        'is_active',
        'is_popular',
        'is_recommended',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'integer',
        'price_annual' => 'integer',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'is_recommended' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the features for this plan.
     */
    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * Get the seasonal packs that give access to this plan.
     */
    public function seasonalPacks(): HasMany
    {
        return $this->hasMany(SeasonalPack::class);
    }

    /**
     * Get the subscriptions for this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Scope to get only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order plans by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get a feature limit for this plan.
     */
    public function getFeatureLimit(string $featureSlug): ?PlanFeature
    {
        return $this->features()->where('feature_slug', $featureSlug)->first();
    }

    /**
     * Check if this plan is higher tier than another.
     */
    public function isHigherThan(Plan $other): bool
    {
        return $this->sort_order > $other->sort_order;
    }

    /**
     * Get the next plan (upgrade option).
     */
    public function getNextPlan(): ?Plan
    {
        return static::active()
            ->where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order')
            ->first();
    }
}
