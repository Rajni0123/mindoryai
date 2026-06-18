<?php

namespace App\Console\Commands;

use App\Models\ImageAnalysis;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOldImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:cleanup {--minutes=1 : Delete images older than N minutes} {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete uploaded images older than specified minutes (default: 1 minute). Perfect for shared hosting cron jobs.';

    /**
     * Execute the console command.
     */
    public function handle(ImageService $imageService): int
    {
        $minutes = (int) $this->option('minutes');
        $force = $this->option('force');

        $this->info("🧹 Starting cleanup of images older than {$minutes} minute(s)...");

        try {
            // Find images older than specified minutes that haven't been deleted
            $cutoffTime = now()->subMinutes($minutes);

            $oldImages = ImageAnalysis::where('created_at', '<', $cutoffTime)
                ->where('is_deleted', false)
                ->get();

            if ($oldImages->isEmpty()) {
                $this->info('✅ No old images found. Storage is clean!');
                return Command::SUCCESS;
            }

            $count = $oldImages->count();
            $this->info("Found {$count} image(s) to delete.");

            if (!$force && !$this->confirm('Do you want to proceed with deletion?', true)) {
                $this->warn('Cleanup cancelled.');
                return Command::FAILURE;
            }

            $deleted = 0;
            $failed = 0;

            foreach ($oldImages as $analysis) {
                try {
                    // Delete the physical file
                    if ($imageService->delete($analysis->image_path)) {
                        // Mark as deleted in database
                        $analysis->markAsDeleted();
                        $deleted++;

                        $this->line("✓ Deleted: {$analysis->image_path}");
                    } else {
                        $failed++;
                        $this->error("✗ Failed to delete: {$analysis->image_path}");
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $this->error("✗ Error deleting {$analysis->image_path}: " . $e->getMessage());
                    Log::error("Image cleanup error: " . $e->getMessage(), [
                        'analysis_id' => $analysis->id,
                        'image_path' => $analysis->image_path
                    ]);
                }
            }

            $this->newLine();
            $this->info("✅ Cleanup completed!");
            $this->table(
                ['Status', 'Count'],
                [
                    ['Deleted', $deleted],
                    ['Failed', $failed],
                    ['Total Processed', $count]
                ]
            );

            // Also clean up orphaned files (files without database records)
            $this->info("\n🔍 Checking for orphaned files...");
            $this->cleanupOrphanedFiles($minutes);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Cleanup failed: ' . $e->getMessage());
            Log::error('Image cleanup command failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Clean up orphaned image files that don't have database records
     *
     * @param int $minutes
     * @return void
     */
    protected function cleanupOrphanedFiles(int $minutes): void
    {
        try {
            $uploadsPath = 'uploads';

            if (!Storage::exists($uploadsPath)) {
                $this->warn("Uploads directory doesn't exist. Skipping orphaned files cleanup.");
                return;
            }

            $allFiles = Storage::files($uploadsPath);
            $cutoffTime = now()->subMinutes($minutes)->timestamp;
            $orphanedCount = 0;

            foreach ($allFiles as $file) {
                // Check if file is old enough
                $fileTime = Storage::lastModified($file);

                if ($fileTime < $cutoffTime) {
                    // Check if file exists in database
                    $existsInDb = ImageAnalysis::where('image_path', $file)->exists();

                    if (!$existsInDb) {
                        // Orphaned file - delete it
                        Storage::delete($file);
                        $orphanedCount++;
                        $this->line("✓ Deleted orphaned file: {$file}");
                    }
                }
            }

            if ($orphanedCount > 0) {
                $this->info("✅ Cleaned up {$orphanedCount} orphaned file(s).");
            } else {
                $this->info("✅ No orphaned files found.");
            }

        } catch (\Exception $e) {
            $this->error("Error cleaning orphaned files: " . $e->getMessage());
            Log::error('Orphaned files cleanup error: ' . $e->getMessage());
        }
    }
}
