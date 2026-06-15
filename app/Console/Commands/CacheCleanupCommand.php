<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AnswerBank;
use App\Models\FormulaBank;
use App\Models\NcertCache;
use App\Models\VideoCache;
use App\Models\ImageCache;
use App\Models\CacheAnalytics;
use App\Services\Cache\CacheAnalyticsService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CacheCleanupCommand extends Command
{
    protected $signature = 'cache:cleanup
                            {--days=90 : Remove entries older than X days with 0 hits}
                            {--analytics=90 : Remove analytics older than X days}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old/unused cache entries and analytics data';

    private CacheAnalyticsService $analytics;

    public function __construct(CacheAnalyticsService $analytics)
    {
        parent::__construct();
        $this->analytics = $analytics;
    }

    public function handle(): int
    {
        $daysThreshold = (int) $this->option('days');
        $analyticsDays = (int) $this->option('analytics');
        $dryRun = $this->option('dry-run');

        $this->info('🧹 Smart Cache Cleanup');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN - No data will be deleted');
            $this->newLine();
        }

        $cutoffDate = Carbon::now()->subDays($daysThreshold);
        $analyticsCutoff = Carbon::now()->subDays($analyticsDays);

        $this->line("Cleaning entries older than {$daysThreshold} days with 0 hits...");
        $this->line("Cleaning analytics older than {$analyticsDays} days...");
        $this->newLine();

        $totalDeleted = 0;

        // Clean Answer Bank
        $count = $this->cleanTable(
            AnswerBank::class,
            'answer_bank',
            $cutoffDate,
            $dryRun
        );
        $totalDeleted += $count;

        // Clean Formula Bank
        $count = $this->cleanTable(
            FormulaBank::class,
            'formula_bank',
            $cutoffDate,
            $dryRun
        );
        $totalDeleted += $count;

        // Clean NCERT Cache
        $count = $this->cleanTable(
            NcertCache::class,
            'ncert_cache',
            $cutoffDate,
            $dryRun
        );
        $totalDeleted += $count;

        // Clean Video Cache
        $count = $this->cleanTable(
            VideoCache::class,
            'video_cache',
            $cutoffDate,
            $dryRun,
            true // has files
        );
        $totalDeleted += $count;

        // Clean Image Cache
        $count = $this->cleanTable(
            ImageCache::class,
            'image_cache',
            $cutoffDate,
            $dryRun,
            true // has files
        );
        $totalDeleted += $count;

        // Clean Analytics
        $this->newLine();
        $this->line('📊 Cleaning old analytics...');
        $analyticsCount = $this->cleanAnalytics($analyticsCutoff, $dryRun);
        $totalDeleted += $analyticsCount;

        // Summary
        $this->newLine();
        $this->info('📋 Summary:');
        $this->line("Total entries " . ($dryRun ? 'would be ' : '') . "deleted: {$totalDeleted}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('Run without --dry-run to actually delete entries.');
        }

        return 0;
    }

    private function cleanTable(
        string $model,
        string $tableName,
        Carbon $cutoffDate,
        bool $dryRun,
        bool $hasFiles = false
    ): int {
        try {
            $query = $model::where('created_at', '<', $cutoffDate)
                ->where('hit_count', 0);

            $count = $query->count();

            if ($count > 0) {
                if ($hasFiles && !$dryRun) {
                    // Delete associated files first
                    $entries = $query->get();
                    foreach ($entries as $entry) {
                        if ($entry->file_path && file_exists(storage_path('app/public/' . $entry->file_path))) {
                            unlink(storage_path('app/public/' . $entry->file_path));
                        }
                    }
                }

                if (!$dryRun) {
                    $query->delete();
                }

                $this->line("  {$tableName}: {$count} entries " . ($dryRun ? 'would be ' : '') . "deleted");
            } else {
                $this->line("  {$tableName}: No entries to clean");
            }

            return $count;
        } catch (\Exception $e) {
            $this->warn("  {$tableName}: Error - " . $e->getMessage());
            return 0;
        }
    }

    private function cleanAnalytics(Carbon $cutoffDate, bool $dryRun): int
    {
        try {
            $count = CacheAnalytics::where('date', '<', $cutoffDate->toDateString())->count();

            if ($count > 0) {
                if (!$dryRun) {
                    CacheAnalytics::where('date', '<', $cutoffDate->toDateString())->delete();
                }
                $this->line("  cache_analytics: {$count} entries " . ($dryRun ? 'would be ' : '') . "deleted");
            } else {
                $this->line("  cache_analytics: No entries to clean");
            }

            return $count;
        } catch (\Exception $e) {
            $this->warn("  cache_analytics: Error - " . $e->getMessage());
            return 0;
        }
    }
}
