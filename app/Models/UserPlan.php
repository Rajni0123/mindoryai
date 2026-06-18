<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class UserPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_period',
        'billing_description',
        'message_tokens',
        'image_credits',
        'api_calls',
        'image_uploads',
        'can_use_gpt4',
        'can_use_claude',
        'can_use_deepseek',
        'can_use_grok',
        'validity_days',
        'is_active',
        'order',
        'features',
        'unlimited_credits',
    ];

    protected $casts = [
        'can_use_gpt4' => 'boolean',
        'can_use_claude' => 'boolean',
        'can_use_deepseek' => 'boolean',
        'can_use_grok' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'features' => 'array',
        'unlimited_credits' => 'boolean',
    ];

    /**
     * Relationship with activation tokens
     */
    public function activationTokens()
    {
        return $this->hasMany(UserActivationToken::class, 'plan_id');
    }

    /**
     * Get plan by slug
     */
    public static function getBySlug($slug)
    {
        return self::where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get a daily limit value from the features JSON.
     * Returns null if not set, -1 means unlimited.
     */
    public function getDailyLimit(string $featureType): ?int
    {
        $value = data_get($this->features, "daily_limits.{$featureType}");
        return $value !== null ? (int) $value : null;
    }

    /**
     * Check if a feature has unlimited daily usage (-1).
     */
    public function isFeatureUnlimited(string $featureType): bool
    {
        return $this->getDailyLimit($featureType) === -1;
    }

    /**
     * Get video quality setting (sd, hd, 4k).
     */
    public function getVideoQuality(): string
    {
        return data_get($this->features, 'video_quality', 'sd');
    }

    /**
     * Get maximum allowed devices for this plan.
     */
    public function getDeviceLimit(): int
    {
        return (int) data_get($this->features, 'device_limit', 1);
    }

    /**
     * Get analytics tier (basic, standard, advanced).
     */
    public function getAnalyticsTier(): string
    {
        return data_get($this->features, 'analytics_tier', 'basic');
    }

    /**
     * Clear caches when model is saved or deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            // Clear pricing plans cache (used by website)
            Cache::forget('active_pricing_plans');
            // Clear app config cache (used by mobile app)
            Cache::forget('app_config_cache');
        });

        static::deleted(function () {
            Cache::forget('active_pricing_plans');
            Cache::forget('app_config_cache');
        });
    }
}
