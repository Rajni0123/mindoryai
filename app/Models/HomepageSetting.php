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

    // Clear cache
    public static function clearCache()
    {
        Cache::flush();
    }

    // Boot method to clear cache on save/delete
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
