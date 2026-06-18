<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CacheAnalytics extends Model
{
    protected $table = 'cache_analytics';

    protected $fillable = [
        'date',
        'source_type',
        'total_requests',
        'cache_hits',
        'cache_misses',
        'greeting_filtered',
        'followup_filtered',
        'quality_filtered',
        'tokens_saved',
        'cost_saved',
        'avg_lookup_ms',
    ];

    protected $casts = [
        'date' => 'date',
        'total_requests' => 'integer',
        'cache_hits' => 'integer',
        'cache_misses' => 'integer',
        'greeting_filtered' => 'integer',
        'followup_filtered' => 'integer',
        'quality_filtered' => 'integer',
        'tokens_saved' => 'integer',
        'cost_saved' => 'decimal:6',
        'avg_lookup_ms' => 'decimal:2',
    ];

    /**
     * Get hit rate percentage
     */
    public function getHitRateAttribute(): float
    {
        if ($this->total_requests == 0) {
            return 0;
        }
        return round(($this->cache_hits / $this->total_requests) * 100, 2);
    }

    /**
     * Get total filtered count
     */
    public function getTotalFilteredAttribute(): int
    {
        return $this->greeting_filtered + $this->followup_filtered + $this->quality_filtered;
    }

    /**
     * Scope for date
     */
    public function scopeForDate($query, string $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope for date range
     */
    public function scopeForDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for source type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('source_type', $type);
    }

    /**
     * Get today's stats
     */
    public function scopeToday($query)
    {
        return $query->where('date', now()->toDateString());
    }

    /**
     * Get this week's stats
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
        ]);
    }
}
