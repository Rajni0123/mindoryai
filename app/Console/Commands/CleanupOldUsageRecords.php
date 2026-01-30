<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupOldUsageRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credits:cleanup-usage {--days=90 : Delete usage records older than N days (default: 90)} {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old daily usage records to keep database lean. Default: keeps last 90 days.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $force = $this->option('force');

        $this->info("🧹 Starting cleanup of daily usage records older than {$days} day(s)...");

        try {
            $cutoffDate = now()->subDays($days)->format('Y-m-d');

            // Count records to be deleted
            $count = DB::table('daily_usage_limits')
                ->where('usage_date', '<', $cutoffDate)
                ->count();

            if ($count === 0) {
                $this->info('✅ No old usage records found. Database is clean!');
                return Command::SUCCESS;
            }

            $this->info("Found {$count} usage record(s) older than {$cutoffDate}.");

            if (!$force && !$this->confirm('Do you want to proceed with deletion?', true)) {
                $this->warn('Cleanup cancelled.');
                return Command::FAILURE;
            }

            // Delete old records
            $deleted = DB::table('daily_usage_limits')
                ->where('usage_date', '<', $cutoffDate)
                ->delete();

            $this->newLine();
            $this->info("✅ Cleanup completed!");
            $this->line("✓ Deleted {$deleted} old usage record(s)");

            // Log the cleanup
            Log::info('Daily usage records cleanup completed', [
                'records_deleted' => $deleted,
                'cutoff_date' => $cutoffDate,
                'days_threshold' => $days,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Cleanup failed: ' . $e->getMessage());
            Log::error('Usage records cleanup command failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
