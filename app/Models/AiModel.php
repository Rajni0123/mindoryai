<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AiModel extends Model
{
    use HasFactory;

    /**
     * Cache TTL for AI models (5 minutes)
     */
    const CACHE_TTL = 300;

    protected $fillable = [
        'name',
        'provider',
        'model_identifier',
        'api_key',
        'api_url',
        'description',
        'icon',
        'color',
        'is_active',
        'supports_vision',
        'max_tokens',
        'temperature',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supports_vision' => 'boolean',
        'temperature' => 'float',
        'max_tokens' => 'integer',
    ];

    /**
     * Get only active AI models - CACHED
     */
    public static function active()
    {
        return Cache::remember('ai_models_active', self::CACHE_TTL, function () {
            return self::where('is_active', true)
                       ->orderBy('order')
                       ->get();
        });
    }

    /**
     * Get AI models that support vision - CACHED
     */
    public static function visionEnabled()
    {
        return Cache::remember('ai_models_vision', self::CACHE_TTL, function () {
            return self::where('is_active', true)
                       ->where('supports_vision', true)
                       ->orderBy('order')
                       ->get();
        });
    }

    /**
     * Get active model by provider - CACHED
     */
    public static function getByProvider(string $provider)
    {
        return Cache::remember("ai_model_provider_{$provider}", self::CACHE_TTL, function () use ($provider) {
            return self::where('provider', $provider)
                       ->where('is_active', true)
                       ->orderBy('order')
                       ->first();
        });
    }

    /**
     * Clear AI model caches
     */
    public static function clearCache(): void
    {
        Cache::forget('ai_models_active');
        Cache::forget('ai_models_vision');
        // Clear provider caches
        foreach (['openai', 'google', 'anthropic', 'deepseek', 'groq'] as $provider) {
            Cache::forget("ai_model_provider_{$provider}");
        }
    }

    /**
     * Boot method - clear cache on save/delete
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }
}
