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
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('homepage_settings')) {
                return collect();
            }

            return Cache::remember('homepage_settings_grouped', 3600, function () {
                return self::orderBy('group')->orderBy('order')->get()->groupBy('group');
            });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public static function getAllCached()
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('homepage_settings')) {
                return [];
            }

            return Cache::remember('homepage_settings_all', 3600, function () {
                return self::all()->pluck('value', 'key')->toArray();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    // Clear cache - ONLY clear homepage setting keys, not entire cache!
    public static function clearCache()
    {
        Cache::forget('homepage_settings_grouped');
        Cache::forget('homepage_settings_all');

        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('homepage_settings')) {
                return;
            }

            $keys = self::pluck('key')->toArray();
            foreach ($keys as $key) {
                Cache::forget("homepage_setting_{$key}");
            }
        } catch (\Throwable $e) {
            // Ignore cache cleanup failures when table is unavailable.
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
