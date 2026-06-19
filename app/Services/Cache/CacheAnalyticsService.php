<?php

namespace App\Services\Cache;

use App\Models\CacheAnalytics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Cache Analytics Service
 * Tracks cache performance metrics and provides reporting
 */
class CacheAnalyticsService
{
    /**
     * Record a cache request (hit or miss)
     * FIXED: Using atomic upsert to prevent race conditions
     */
    public function recordRequest(
        string $sourceType,
        bool $isHit,
        string $reason = 'unknown'
    ): void {
        try {
            $date = now()->toDateString();

            // Build atomic update data
            $incrementFields = [
                'total_requests' => 1,
                'cache_hits' => $isHit ? 1 : 0,
                'cache_misses' => $isHit ? 0 : 1,
                'tokens_saved' => $isHit ? 500 : 0,
                'cost_saved' => $isHit ? 0.00006 : 0,
                'greeting_filtered' => ($reason === 'greeting_filtered') ? 1 : 0,
                'followup_filtered' => ($reason === 'followup_detected') ? 1 : 0,
                'quality_filtered' => ($reason === 'quality_failed') ? 1 : 0,
            ];

            // Use atomic upsert with ON DUPLICATE KEY UPDATE
            // This prevents race conditions by doing everything in a single query
            DB::statement("
                INSERT INTO cache_analytics
                (date, source_type, total_requests, cache_hits, cache_misses,
                 greeting_filtered, followup_filtered, quality_filtered,
                 tokens_saved, cost_saved, avg_lookup_ms, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    total_requests = total_requests + VALUES(total_requests),
                    cache_hits = cache_hits + VALUES(cache_hits),
                    cache_misses = cache_misses + VALUES(cache_misses),
                    greeting_filtered = greeting_filtered + VALUES(greeting_filtered),
                    followup_filtered = followup_filtered + VALUES(followup_filtered),
                    quality_filtered = quality_filtered + VALUES(quality_filtered),
                    tokens_saved = tokens_saved + VALUES(tokens_saved),
                    cost_saved = cost_saved + VALUES(cost_saved),
                    updated_at = NOW()
            ", [
                $date,
                $sourceType,
                $incrementFields['total_requests'],
                $incrementFields['cache_hits'],
                $incrementFields['cache_misses'],
                $incrementFields['greeting_filtered'],
                $incrementFields['followup_filtered'],
                $incrementFields['quality_filtered'],
                $incrementFields['tokens_saved'],
                $incrementFields['cost_saved'],
            ]);
        } catch (\Exception $e) {
            Log::warning("[CacheAnalytics] Failed to record request", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Record lookup time for performance tracking
     */
    public function recordLookupTime(string $sourceType, float $milliseconds): void
    {
        try {
            $date = now()->toDateString();

            $analytics = CacheAnalytics::where('date', $date)
                ->where('source_type', $sourceType)
                ->first();

            if ($analytics) {
                // Calculate running average
                $currentAvg = $analytics->avg_lookup_ms;
                $total = $analytics->total_requests;

                if ($total > 0) {
                    $newAvg = (($currentAvg * ($total - 1)) + $milliseconds) / $total;
                    $analytics->update(['avg_lookup_ms' => round($newAvg, 2)]);
                }
            }
        } catch (\Exception $e) {
            Log::warning("[CacheAnalytics] Failed to record lookup time", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get daily stats for a source type
     */
    public function getDailyStats(string $sourceType, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        $analytics = CacheAnalytics::where('date', $date)
            ->where('source_type', $sourceType)
            ->first();

        if (!$analytics) {
            return [
                'date' => $date,
                'source_type' => $sourceType,
                'total_requests' => 0,
                'cache_hits' => 0,
                'cache_misses' => 0,
                'hit_rate' => 0,
                'greeting_filtered' => 0,
                'followup_filtered' => 0,
                'quality_filtered' => 0,
                'tokens_saved' => 0,
                'cost_saved' => 0,
                'avg_lookup_ms' => 0,
            ];
        }

        $hitRate = $analytics->total_requests > 0
            ? round(($analytics->cache_hits / $analytics->total_requests) * 100, 2)
            : 0;

        return [
            'date' => $analytics->date,
            'source_type' => $analytics->source_type,
            'total_requests' => $analytics->total_requests,
            'cache_hits' => $analytics->cache_hits,
            'cache_misses' => $analytics->cache_misses,
            'hit_rate' => $hitRate,
            'greeting_filtered' => $analytics->greeting_filtered,
            'followup_filtered' => $analytics->followup_filtered,
            'quality_filtered' => $analytics->quality_filtered,
            'tokens_saved' => $analytics->tokens_saved,
            'cost_saved' => round($analytics->cost_saved, 4),
            'avg_lookup_ms' => $analytics->avg_lookup_ms,
        ];
    }

    /**
     * Get stats for all source types
     */
    public function getAllStats(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        $analytics = CacheAnalytics::where('date', $date)->get();

        $stats = [];
        foreach ($analytics as $record) {
            $hitRate = $record->total_requests > 0
                ? round(($record->cache_hits / $record->total_requests) * 100, 2)
                : 0;

            $stats[$record->source_type] = [
                'total_requests' => $record->total_requests,
                'cache_hits' => $record->cache_hits,
                'cache_misses' => $record->cache_misses,
                'hit_rate' => $hitRate,
                'tokens_saved' => $record->tokens_saved,
                'cost_saved' => round($record->cost_saved, 4),
                'avg_lookup_ms' => $record->avg_lookup_ms,
            ];
        }

        return [
            'date' => $date,
            'by_source' => $stats,
            'totals' => $this->calculateTotals($stats),
        ];
    }

    /**
     * Get weekly summary
     */
    public function getWeeklySummary(?string $endDate = null): array
    {
        $endDate = $endDate ? Carbon::parse($endDate) : now();
        $startDate = $endDate->copy()->subDays(6);

        $analytics = CacheAnalytics::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $dailyStats = [];
        $totals = [
            'total_requests' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'tokens_saved' => 0,
            'cost_saved' => 0,
        ];

        // Group by date
        $byDate = $analytics->groupBy('date');
        foreach ($byDate as $date => $records) {
            $dayTotal = [
                'total_requests' => $records->sum('total_requests'),
                'cache_hits' => $records->sum('cache_hits'),
                'cache_misses' => $records->sum('cache_misses'),
                'tokens_saved' => $records->sum('tokens_saved'),
                'cost_saved' => $records->sum('cost_saved'),
            ];

            $dayTotal['hit_rate'] = $dayTotal['total_requests'] > 0
                ? round(($dayTotal['cache_hits'] / $dayTotal['total_requests']) * 100, 2)
                : 0;

            $dailyStats[$date] = $dayTotal;

            $totals['total_requests'] += $dayTotal['total_requests'];
            $totals['cache_hits'] += $dayTotal['cache_hits'];
            $totals['cache_misses'] += $dayTotal['cache_misses'];
            $totals['tokens_saved'] += $dayTotal['tokens_saved'];
            $totals['cost_saved'] += $dayTotal['cost_saved'];
        }

        $totals['hit_rate'] = $totals['total_requests'] > 0
            ? round(($totals['cache_hits'] / $totals['total_requests']) * 100, 2)
            : 0;

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'daily' => $dailyStats,
            'totals' => $totals,
        ];
    }

    /**
     * Get filter breakdown stats
     */
    public function getFilterStats(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        $analytics = CacheAnalytics::where('date', $date)->get();

        $totals = [
            'greeting_filtered' => $analytics->sum('greeting_filtered'),
            'followup_filtered' => $analytics->sum('followup_filtered'),
            'quality_filtered' => $analytics->sum('quality_filtered'),
            'total_filtered' => 0,
        ];

        $totals['total_filtered'] = $totals['greeting_filtered']
            + $totals['followup_filtered']
            + $totals['quality_filtered'];

        $totalMisses = $analytics->sum('cache_misses');

        return [
            'date' => $date,
            'filters' => $totals,
            'filter_percentages' => $totalMisses > 0 ? [
                'greeting' => round(($totals['greeting_filtered'] / $totalMisses) * 100, 2),
                'followup' => round(($totals['followup_filtered'] / $totalMisses) * 100, 2),
                'quality' => round(($totals['quality_filtered'] / $totalMisses) * 100, 2),
            ] : [
                'greeting' => 0,
                'followup' => 0,
                'quality' => 0,
            ],
        ];
    }

    /**
     * Calculate totals from stats array
     */
    private function calculateTotals(array $stats): array
    {
        $totals = [
            'total_requests' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'tokens_saved' => 0,
            'cost_saved' => 0,
        ];

        foreach ($stats as $source) {
            $totals['total_requests'] += $source['total_requests'];
            $totals['cache_hits'] += $source['cache_hits'];
            $totals['cache_misses'] += $source['cache_misses'];
            $totals['tokens_saved'] += $source['tokens_saved'];
            $totals['cost_saved'] += $source['cost_saved'];
        }

        $totals['hit_rate'] = $totals['total_requests'] > 0
            ? round(($totals['cache_hits'] / $totals['total_requests']) * 100, 2)
            : 0;

        return $totals;
    }

    /**
     * Clean old analytics data (keep last 90 days)
     */
    public function cleanOldData(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep)->toDateString();

        return CacheAnalytics::where('date', '<', $cutoffDate)->delete();
    }
}
