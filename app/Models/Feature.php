<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Feature extends Model
{
    protected $fillable = [
        'title',
        'description',
        'icon',
        'color',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get active features ordered by order column (with caching)
     */
    public static function getActiveFeatures()
    {
        return Cache::remember('active_features', 3600, function () {
            return self::where('is_active', true)
                ->orderBy('order')
                ->get();
        });
    }

    /**
     * Clear features cache when model is saved or deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('active_features');
        });

        static::deleted(function () {
            Cache::forget('active_features');
        });
    }
}
