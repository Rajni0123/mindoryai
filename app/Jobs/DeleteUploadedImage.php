<?php

namespace App\Jobs;

use App\Models\ImageAnalysis;
use App\Services\ImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteUploadedImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $analysisId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(int $analysisId)
    {
        $this->analysisId = $analysisId;
    }

    /**
     * Execute the job.
     */
    public function handle(ImageService $imageService): void
    {
        try {
            $analysis = ImageAnalysis::find($this->analysisId);

            if (!$analysis) {
                Log::warning("Image analysis not found for deletion", [
                    'analysis_id' => $this->analysisId
                ]);
                return;
            }

            // Skip if already deleted
            if ($analysis->is_deleted) {
                Log::info("Image already deleted", [
                    'analysis_id' => $this->analysisId
                ]);
                return;
            }

            // Delete the physical file
            $deleted = $imageService->delete($analysis->image_path);

            if ($deleted) {
                // Mark as deleted in database
                $analysis->markAsDeleted();

                Log::info("Image successfully deleted", [
                    'analysis_id' => $this->analysisId,
                    'image_path' => $analysis->image_path
                ]);
            } else {
                Log::warning("Failed to delete image file", [
                    'analysis_id' => $this->analysisId,
                    'image_path' => $analysis->image_path
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Error deleting image: " . $e->getMessage(), [
                'analysis_id' => $this->analysisId,
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            // Optionally re-throw to retry the job
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("DeleteUploadedImage job failed permanently", [
            'analysis_id' => $this->analysisId,
            'exception' => $exception->getMessage()
        ]);
    }
}
