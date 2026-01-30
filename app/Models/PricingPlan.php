<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PricingPlan extends Model
{
    protected $table = 'user_plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_period',
        'billing_description',
        'features',
        'order',
        'is_active',
        'unlimited_credits',
        'message_tokens',
        'image_credits',
        'can_use_gpt4',
        'can_use_claude',
        'can_use_deepseek',
        'can_use_grok',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'unlimited_credits' => 'boolean',
        'can_use_gpt4' => 'boolean',
        'can_use_claude' => 'boolean',
        'can_use_deepseek' => 'boolean',
        'can_use_grok' => 'boolean',
    ];

    /**
     * Get active pricing plans ordered by order column (with caching)
     */
    public static function getActivePlans()
    {
        return Cache::remember('active_pricing_plans', 3600, function () {
            return self::where('is_active', true)
                ->orderBy('order')
                ->get();
        });
    }

    /**
     * Accessor for is_popular (from features json)
     */
    public function getIsPopularAttribute()
    {
        $features = $this->features ?? [];
        return $features['popular'] ?? false;
    }

    /**
     * Accessor for period (alias for billing_period)
     */
    public function getPeriodAttribute()
    {
        return $this->billing_period ?? 'month';
    }

    /**
     * Clear pricing plans cache when model is saved or deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('active_pricing_plans');
        });

        static::deleted(function () {
            Cache::forget('active_pricing_plans');
        });
    }
}
