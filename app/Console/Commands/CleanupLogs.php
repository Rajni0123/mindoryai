<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CleanupLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup {--keep=7 : Keep logs from last N days} {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old log files to save disk space on shared hosting.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $keepDays = (int) $this->option('keep');
        $force = $this->option('force');

        $this->info("🧹 Cleaning up log files older than {$keepDays} day(s)...");

        try {
            $logPath = storage_path('logs');

            if (!File::exists($logPath)) {
                $this->warn('Logs directory does not exist.');
                return Command::SUCCESS;
            }

            $files = File::files($logPath);
            $cutoffTime = now()->subDays($keepDays)->timestamp;

            $filesToDelete = [];
            $totalSize = 0;

            foreach ($files as $file) {
                if ($file->getMTime() < $cutoffTime) {
                    $filesToDelete[] = $file;
                    $totalSize += $file->getSize();
                }
            }

            if (empty($filesToDelete)) {
                $this->info('✅ No old log files found. All logs are recent!');
                return Command::SUCCESS;
            }

            $count = count($filesToDelete);
            $sizeMB = number_format($totalSize / 1024 / 1024, 2);

            $this->info("Found {$count} log file(s) to delete ({$sizeMB} MB)");

            if (!$force && !$this->confirm('Do you want to proceed with deletion?', true)) {
                $this->warn('Cleanup cancelled.');
                return Command::FAILURE;
            }

            $deleted = 0;
            $failed = 0;

            foreach ($filesToDelete as $file) {
                try {
                    File::delete($file);
                    $deleted++;
                    $this->line("✓ Deleted: " . $file->getFilename());
                } catch (\Exception $e) {
                    $failed++;
                    $this->error("✗ Failed: " . $file->getFilename());
                }
            }

            $this->newLine();
            $this->info("✅ Cleanup completed!");
            $this->table(
                ['Status', 'Count'],
                [
                    ['Deleted', $deleted],
                    ['Failed', $failed],
                    ['Space Freed', "{$sizeMB} MB"]
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Log cleanup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
