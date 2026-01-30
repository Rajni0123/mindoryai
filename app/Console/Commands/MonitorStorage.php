<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\ImageAnalysis;

class MonitorStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:monitor {--alert-size=100 : Alert if storage exceeds N MB}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor storage usage and alert if limits are exceeded. Perfect for shared hosting.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $alertSize = (int) $this->option('alert-size');

        $this->info('📊 Storage Usage Report');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Check uploads directory
        $uploadsSize = $this->getDirectorySize('uploads');
        $uploadsCount = $this->getFileCount('uploads');

        // Check logs directory
        $logsSize = $this->getDirectorySize('logs');

        // Check cache directory
        $cacheSize = $this->getDirectorySize('framework/cache');

        // Check total storage
        $totalSize = $uploadsSize + $logsSize + $cacheSize;

        // Database statistics
        $totalImages = ImageAnalysis::count();
        $deletedImages = ImageAnalysis::where('is_deleted', true)->count();
        $activeImages = $totalImages - $deletedImages;

        // Display results
        $this->table(
            ['Directory', 'Size (MB)', 'Files/Records', 'Status'],
            [
                [
                    'Uploads',
                    number_format($uploadsSize, 2),
                    $uploadsCount,
                    $uploadsSize > $alertSize ? '⚠️  WARNING' : '✅ OK'
                ],
                [
                    'Logs',
                    number_format($logsSize, 2),
                    '-',
                    $logsSize > 50 ? '⚠️  WARNING' : '✅ OK'
                ],
                [
                    'Cache',
                    number_format($cacheSize, 2),
                    '-',
                    $cacheSize > 100 ? '⚠️  WARNING' : '✅ OK'
                ],
                [
                    'TOTAL',
                    number_format($totalSize, 2),
                    '-',
                    $totalSize > $alertSize ? '⚠️  WARNING' : '✅ OK'
                ],
            ]
        );

        $this->newLine();

        $this->table(
            ['Database Stats', 'Count'],
            [
                ['Total Image Records', $totalImages],
                ['Active Images', $activeImages],
                ['Deleted Images', $deletedImages],
            ]
        );

        // Recommendations
        $this->newLine();
        $this->info('💡 Recommendations:');

        if ($uploadsSize > $alertSize) {
            $this->warn("→ Uploads directory is {$uploadsSize} MB. Run: php artisan images:cleanup");
        }

        if ($logsSize > 50) {
            $this->warn("→ Logs directory is {$logsSize} MB. Run: php artisan logs:cleanup");
        }

        if ($cacheSize > 100) {
            $this->warn("→ Cache directory is {$cacheSize} MB. Run: php artisan cache:clear");
        }

        if ($activeImages > $uploadsCount) {
            $mismatch = $activeImages - $uploadsCount;
            $this->warn("→ {$mismatch} database record(s) without files. Run: php artisan images:cleanup --force");
        }

        if ($uploadsSize <= $alertSize && $logsSize <= 50 && $cacheSize <= 100) {
            $this->info('✅ All storage directories are within normal limits!');
        }

        return Command::SUCCESS;
    }

    /**
     * Get directory size in MB
     *
     * @param string $directory
     * @return float
     */
    protected function getDirectorySize(string $directory): float
    {
        try {
            if (!Storage::exists($directory)) {
                return 0;
            }

            $files = Storage::allFiles($directory);
            $size = 0;

            foreach ($files as $file) {
                $size += Storage::size($file);
            }

            return $size / 1024 / 1024; // Convert to MB
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get file count in directory
     *
     * @param string $directory
     * @return int
     */
    protected function getFileCount(string $directory): int
    {
        try {
            if (!Storage::exists($directory)) {
                return 0;
            }

            return count(Storage::files($directory));
        } catch (\Exception $e) {
            return 0;
        }
    }
}
