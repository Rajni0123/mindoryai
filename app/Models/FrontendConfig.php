<?php

namespace App\Models;

use App\Support\SensitiveConfigFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FrontendConfig extends Model
{
    protected $fillable = [
        'config_key',
        'config_value',
        'value_type',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Cache key for frontend configs
     */
    const CACHE_KEY = 'frontend_configs_all';
    const CACHE_DURATION = 3600; // 1 hour

    /**
     * Get all active configs as key-value pairs
     * Cached for performance
     */
    public static function getAllConfigs(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
                $configs = self::where('is_active', true)->get();
                $result = [];

                foreach ($configs as $config) {
                    $result[$config->config_key] = $config->getParsedValue();
                }

                return $result;
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('FrontendConfig: Could not load configs', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get single config value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $configs = self::getAllConfigs();
        return $configs[$key] ?? $default;
    }

    /**
     * Public-safe configs only (no API keys, secrets, or passwords).
     */
    public static function getPublicConfigs(): array
    {
        return SensitiveConfigFilter::filterArray(self::getAllConfigs());
    }

    /**
     * Set config value
     */
    public static function setValue(string $key, $value, string $type = 'string', ?string $description = null): self
    {
        // Convert null to empty string to avoid NOT NULL constraint error
        $configValue = $value ?? '';

        $config = self::updateOrCreate(
            ['config_key' => $key],
            [
                'config_value' => is_array($configValue) || is_object($configValue) ? json_encode($configValue) : $configValue,
                'value_type' => $type,
                'description' => $description,
                'is_active' => true
            ]
        );

        // Clear cache
        self::clearCache();

        return $config;
    }

    /**
     * Parse value based on type
     */
    public function getParsedValue()
    {
        switch ($this->value_type) {
            case 'boolean':
                return filter_var($this->config_value, FILTER_VALIDATE_BOOLEAN);

            case 'number':
                return is_numeric($this->config_value)
                    ? (strpos($this->config_value, '.') !== false ? (float) $this->config_value : (int) $this->config_value)
                    : $this->config_value;

            case 'json':
                $decoded = json_decode($this->config_value, true);
                return $decoded !== null ? $decoded : $this->config_value;

            case 'string':
            default:
                return $this->config_value;
        }
    }

    /**
     * Clear config cache
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        // Also clear OTP service settings cache since it depends on FrontendConfig
        Cache::forget('otp_service_settings');
    }

    /**
     * Boot method - clear cache on save/delete
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            self::clearCache();

            // Clear OTP service static cache if auth settings changed
            if (str_starts_with($model->config_key ?? '', 'auth.')) {
                \App\Services\RenflairOTPService::clearSettingsCache();
            }
        });

        static::deleted(function () {
            self::clearCache();
        });
    }
}
