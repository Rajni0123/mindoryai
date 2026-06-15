<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Cache\CacheAnalyticsService;
use App\Models\AnswerBank;
use App\Models\FormulaBank;
use App\Models\NcertCache;
use App\Models\VideoCache;
use App\Models\ImageCache;
use App\Models\GreetingPattern;

class CacheStatsCommand extends Command
{
    protected $signature = 'cache:stats
                            {--date= : Specific date (YYYY-MM-DD) for stats}
                            {--weekly : Show weekly summary}
                            {--detailed : Show detailed breakdown}';

    protected $description = 'Display Smart Cache statistics and analytics';

    private CacheAnalyticsService $analytics;

    public function __construct(CacheAnalyticsService $analytics)
    {
        parent::__construct();
        $this->analytics = $analytics;
    }

    public function handle(): int
    {
        $this->info('📊 Smart Cache Statistics');
        $this->newLine();

        // Show cache inventory
        $this->showCacheInventory();

        // Show today's or specified date stats
        $date = $this->option('date') ?? now()->toDateString();

        if ($this->option('weekly')) {
            $this->showWeeklyStats();
        } else {
            $this->showDailyStats($date);
        }

        // Show filter breakdown if detailed
        if ($this->option('detailed')) {
            $this->showFilterBreakdown($date);
            $this->showTopCachedQuestions();
        }

        return 0;
    }

    private function showCacheInventory(): void
    {
        $this->info('📦 Cache Inventory:');
        $this->newLine();

        $inventory = [
            ['Answer Bank', $this->safeCount(AnswerBank::class), $this->safeCount(AnswerBank::class, 'active')],
            ['Formula Bank', $this->safeCount(FormulaBank::class), $this->safeCount(FormulaBank::class, 'active')],
            ['NCERT Cache', $this->safeCount(NcertCache::class), $this->safeCount(NcertCache::class, 'active')],
            ['Video Cache', $this->safeCount(VideoCache::class), $this->safeCount(VideoCache::class, 'active')],
            ['Image Cache', $this->safeCount(ImageCache::class), $this->safeCount(ImageCache::class, 'active')],
            ['Greeting Patterns', $this->safeCount(GreetingPattern::class), $this->safeCount(GreetingPattern::class, 'active')],
        ];

        $this->table(['Cache Type', 'Total', 'Active'], $inventory);
    }

    private function safeCount(string $model, ?string $scope = null): string
    {
        try {
            $query = $model::query();
            if ($scope === 'active') {
                $query->where('is_active', true);
            }
            return (string) $query->count();
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function showDailyStats(string $date): void
    {
        $this->newLine();
        $this->info("📅 Stats for {$date}:");
        $this->newLine();

        $stats = $this->analytics->getAllStats($date);

        if (empty($stats['by_source'])) {
            $this->warn('No data available for this date.');
            return;
        }

        $tableData = [];
        foreach ($stats['by_source'] as $source => $data) {
            $tableData[] = [
                $source,
                $data['total_requests'],
                $data['cache_hits'],
                $data['cache_misses'],
                $data['hit_rate'] . '%',
                number_format($data['tokens_saved']),
                '₹' . number_format($data['cost_saved'], 2),
            ];
        }

        // Add totals row
        $totals = $stats['totals'];
        $tableData[] = [
            '─────────',
            '─────',
            '─────',
            '─────',
            '─────',
            '─────',
            '─────',
        ];
        $tableData[] = [
            'TOTAL',
            $totals['total_requests'],
            $totals['cache_hits'],
            $totals['cache_misses'],
            $totals['hit_rate'] . '%',
            number_format($totals['tokens_saved']),
            '₹' . number_format($totals['cost_saved'], 2),
        ];

        $this->table(
            ['Source', 'Requests', 'Hits', 'Misses', 'Hit Rate', 'Tokens Saved', 'Cost Saved'],
            $tableData
        );
    }

    private function showWeeklyStats(): void
    {
        $this->newLine();
        $this->info('📆 Weekly Summary (Last 7 Days):');
        $this->newLine();

        $summary = $this->analytics->getWeeklySummary();

        $tableData = [];
        foreach ($summary['daily'] as $date => $data) {
            $tableData[] = [
                $date,
                $data['total_requests'],
                $data['cache_hits'],
                $data['hit_rate'] . '%',
                number_format($data['tokens_saved']),
                '₹' . number_format($data['cost_saved'], 2),
            ];
        }

        // Add totals row
        $totals = $summary['totals'];
        $tableData[] = [
            '─────────',
            '─────',
            '─────',
            '─────',
            '─────',
            '─────',
        ];
        $tableData[] = [
            'TOTAL',
            $totals['total_requests'],
            $totals['cache_hits'],
            $totals['hit_rate'] . '%',
            number_format($totals['tokens_saved']),
            '₹' . number_format($totals['cost_saved'], 2),
        ];

        $this->table(
            ['Date', 'Requests', 'Hits', 'Hit Rate', 'Tokens Saved', 'Cost Saved'],
            $tableData
        );
    }

    private function showFilterBreakdown(string $date): void
    {
        $this->newLine();
        $this->info('🔍 Filter Breakdown:');
        $this->newLine();

        $filterStats = $this->analytics->getFilterStats($date);

        $tableData = [
            ['Greeting Filter', $filterStats['filters']['greeting_filtered'], $filterStats['filter_percentages']['greeting'] . '%'],
            ['Follow-up Filter', $filterStats['filters']['followup_filtered'], $filterStats['filter_percentages']['followup'] . '%'],
            ['Quality Gate', $filterStats['filters']['quality_filtered'], $filterStats['filter_percentages']['quality'] . '%'],
            ['─────────', '─────', '─────'],
            ['Total Filtered', $filterStats['filters']['total_filtered'], '100%'],
        ];

        $this->table(['Filter', 'Count', '% of Misses'], $tableData);
    }

    private function showTopCachedQuestions(): void
    {
        $this->newLine();
        $this->info('🔝 Top 10 Most-Used Cached Answers:');
        $this->newLine();

        try {
            $topQuestions = AnswerBank::active()
                ->orderBy('hit_count', 'desc')
                ->limit(10)
                ->get(['id', 'original_question', 'source_type', 'hit_count', 'last_hit_at']);

            if ($topQuestions->isEmpty()) {
                $this->warn('No cached answers yet.');
                return;
            }

            $tableData = [];
            foreach ($topQuestions as $q) {
                $tableData[] = [
                    $q->id,
                    mb_substr($q->original_question, 0, 40) . (mb_strlen($q->original_question) > 40 ? '...' : ''),
                    $q->source_type,
                    $q->hit_count,
                    $q->last_hit_at ? $q->last_hit_at->diffForHumans() : 'Never',
                ];
            }

            $this->table(['ID', 'Question', 'Type', 'Hits', 'Last Hit'], $tableData);
        } catch (\Exception $e) {
            $this->warn('Could not fetch top questions: ' . $e->getMessage());
        }
    }
}
