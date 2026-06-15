<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AnswerBankStats extends Model
{
    protected $table = 'answer_bank_stats';

    protected $fillable = [
        'date',
        'total_queries',
        'cache_hits',
        'api_calls',
        'cost_saved_paisa',
    ];

    protected $casts = [
        'date' => 'date',
        'total_queries' => 'integer',
        'cache_hits' => 'integer',
        'api_calls' => 'integer',
        'cost_saved_paisa' => 'integer',
    ];

    /**
     * Track a query (cache hit or API call)
     */
    public static function track(bool $cacheHit): void
    {
        $today = now()->toDateString();

        $stats = self::firstOrCreate(['date' => $today], [
            'total_queries' => 0,
            'cache_hits' => 0,
            'api_calls' => 0,
            'cost_saved_paisa' => 0,
        ]);

        $stats->increment('total_queries');

        if ($cacheHit) {
            $stats->increment('cache_hits');
            $stats->increment('cost_saved_paisa', 15); // ~15 paisa saved per cached response
        } else {
            $stats->increment('api_calls');
        }
    }

    /**
     * Get summary statistics
     */
    public static function getSummary(): array
    {
        $today = now()->toDateString();
        $todayStats = self::where('date', $today)->first();

        $allTimeStats = self::selectRaw('
            SUM(total_queries) as total_queries,
            SUM(cache_hits) as cache_hits,
            SUM(api_calls) as api_calls,
            SUM(cost_saved_paisa) as cost_saved_paisa
        ')->first();

        $allTimeTotal = $allTimeStats->total_queries ?? 0;
        $allTimeHits = $allTimeStats->cache_hits ?? 0;
        $allTimeCostSaved = $allTimeStats->cost_saved_paisa ?? 0;

        return [
            'today' => [
                'total_queries' => $todayStats->total_queries ?? 0,
                'cache_hits' => $todayStats->cache_hits ?? 0,
                'api_calls' => $todayStats->api_calls ?? 0,
                'hit_rate' => $todayStats && $todayStats->total_queries > 0
                    ? round(($todayStats->cache_hits / $todayStats->total_queries) * 100, 1)
                    : 0,
            ],
            'all_time' => [
                'total_queries' => $allTimeTotal,
                'cache_hits' => $allTimeHits,
                'api_calls' => $allTimeStats->api_calls ?? 0,
                'hit_rate' => $allTimeTotal > 0
                    ? round(($allTimeHits / $allTimeTotal) * 100, 1)
                    : 0,
                'money_saved' => '₹' . number_format($allTimeCostSaved / 100, 2),
            ],
        ];
    }

    /**
     * Get daily stats for charts
     */
    public static function getDailyStats(int $days = 30): array
    {
        return self::where('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get()
            ->map(fn($s) => [
                'date' => $s->date->format('Y-m-d'),
                'queries' => $s->total_queries,
                'hits' => $s->cache_hits,
                'api_calls' => $s->api_calls,
                'hit_rate' => $s->total_queries > 0
                    ? round(($s->cache_hits / $s->total_queries) * 100, 1)
                    : 0,
            ])
            ->toArray();
    }
}
