<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value
     */
    public static function set($key, $value, $group = 'general')
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("setting_{$key}");

        return $setting;
    }

    /**
     * Get all settings in a group
     */
    public static function getGroup($group)
    {
        return Cache::remember("settings_group_{$group}", 3600, function () use ($group) {
            return self::where('group', $group)->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get multiple settings at once (optimized)
     */
    public static function getMany(array $keys, array $defaults = [])
    {
        $cacheKey = 'settings_many_' . md5(implode('_', $keys));

        return Cache::remember($cacheKey, 3600, function () use ($keys, $defaults) {
            $settings = self::whereIn('key', $keys)->pluck('value', 'key')->toArray();

            // Apply defaults for missing keys
            foreach ($keys as $key) {
                if (!isset($settings[$key]) && isset($defaults[$key])) {
                    $settings[$key] = $defaults[$key];
                }
            }

            return $settings;
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache()
    {
        Cache::flush();
    }
}
