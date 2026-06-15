<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class HomepageSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'order'
    ];

    // Get setting value by key
    public static function getValue($key, $default = null)
    {
        return Cache::remember("homepage_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    // Get all settings grouped
    public static function getAllGrouped()
    {
        return Cache::remember('homepage_settings_grouped', 3600, function () {
            return self::orderBy('group')->orderBy('order')->get()->groupBy('group');
        });
    }

    // Get all settings as key-value pairs (optimized for landing page)
    public static function getAllCached()
    {
        return Cache::remember('homepage_settings_all', 3600, function () {
            return self::all()->pluck('value', 'key')->toArray();
        });
    }

    // Clear cache - ONLY clear homepage setting keys, not entire cache!
    public static function clearCache()
    {
        // Clear specific cache keys instead of flushing everything
        Cache::forget('homepage_settings_grouped');
        Cache::forget('homepage_settings_all');

        // Clear individual setting caches
        $keys = self::pluck('key')->toArray();
        foreach ($keys as $key) {
            Cache::forget("homepage_setting_{$key}");
        }
    }

    // Boot method to clear cache on save/delete
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            // Clear specific caches only
            Cache::forget('homepage_settings_grouped');
            Cache::forget('homepage_settings_all');
            Cache::forget("homepage_setting_{$model->key}");
        });

        static::deleted(function ($model) {
            Cache::forget('homepage_settings_grouped');
            Cache::forget('homepage_settings_all');
            Cache::forget("homepage_setting_{$model->key}");
        });
    }
}
