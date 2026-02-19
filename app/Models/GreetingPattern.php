<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GreetingPattern extends Model
{
    protected $table = 'greeting_patterns';

    protected $fillable = [
        'pattern',
        'category',
        'language',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active patterns
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for category
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for language
     */
    public function scopeOfLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Get patterns ordered by priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /**
     * Get all active patterns as array
     */
    public static function getActivePatterns(): array
    {
        return static::active()
            ->ordered()
            ->pluck('pattern')
            ->toArray();
    }

    /**
     * Get patterns by category as array
     */
    public static function getPatternsByCategory(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->groupBy('category')
            ->map(function ($patterns) {
                return $patterns->pluck('pattern')->toArray();
            })
            ->toArray();
    }
}
