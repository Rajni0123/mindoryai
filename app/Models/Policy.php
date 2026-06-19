<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    protected $fillable = [
        'key',
        'title',
        'content',
        'is_enabled',
        'order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'content' => 'array',
    ];

    /**
     * Scope to get only enabled policies
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope to get ordered policies
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get policy by key
     */
    public static function getByKey($key)
    {
        return self::where('key', $key)->first();
    }

    /**
     * Clear policy cache
     */
    public static function clearCache()
    {
        \Cache::forget('policies_list');
        \Cache::forget('app_config_cache');
    }

    /**
     * Boot method to clear cache on save/delete
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
