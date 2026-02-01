<?php

namespace App\Jobs;

use App\Models\WhiteboardVideo;
use App\Services\WhiteboardVideo\ManimVideoService;
use App\Services\FileStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class CheckManimVideoStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public WhiteboardVideo $video;
    public int $checkCount;

    public $tries = 1;
    public $timeout = 120;

    private static $statusMap = [
        'pending' => 'pending',
        'generating_storyboard' => 'generating_storyboard',
        'generating_audio' => 'generating_assets',
        'generating_assets' => 'generating_assets',
        'stitching_video' => 'stitching_video',
        'completed' => 'completed',
        'failed' => 'failed',
    ];

    public function __construct(WhiteboardVideo $video, ?ManimVideoService $manimService = null, int $checkCount = 0)
    {
        $this->video = $video;
        $this->checkCount = $checkCount;
    }

    public function handle(): void
    {
        // If already completed or failed, stop checking
        if (in_array($this->video->status, ['completed', 'failed'])) {
            return;
        }

        $manimService = new ManimVideoService();
        $status = $manimService->getStatus($this->video->job_id);
        $currentStatus = $status['status'] ?? 'unknown';

        // Map and update local progress
        $mappedStatus = self::$statusMap[$currentStatus] ?? 'processing';
        $this->video->update([
            'status' => $mappedStatus,
            'total_scenes' => $status['total_scenes'] ?? $this->video->total_scenes,
            'processed_scenes' => $status['processed_scenes'] ?? $this->video->processed_scenes,
        ]);

        if ($currentStatus === 'completed') {
            $this->handleCompleted($manimService, $status);
            return;
        }

        if ($currentStatus === 'failed') {
            $this->video->markAsFailed($status['error_message'] ?? 'Manim server reported failure');
            return;
        }

        // Max 50 checks * 30s = 25 minutes total
        if ($this->checkCount >= 50) {
            $this->video->markAsFailed('Manim video generation timed out after 25 minutes');
            return;
        }

        // Re-dispatch for next check in 30 seconds
        self::dispatch($this->video, null, $this->checkCount + 1)
            ->delay(now()->addSeconds(30));
    }

    private function handleCompleted(ManimVideoService $manimService, array $status): void
    {
        $storagePath = "videos/{$this->video->job_id}/final_video.mp4";
        $fullPath = storage_path("app/{$storagePath}");

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $downloaded = $manimService->downloadVideo($this->video->job_id, $fullPath);
        if (!$downloaded) {
            $this->video->markAsFailed('Failed to download video from Manim server');
            return;
        }

        // Download thumbnail
        $thumbPath = storage_path("app/videos/{$this->video->job_id}/thumbnail.jpg");
        $manimService->downloadThumbnail($this->video->job_id, $thumbPath);

        // Upload to cloud storage if active
        $videoUrl = Storage::url($storagePath);
        $thumbnailCloudPath = "videos/{$this->video->job_id}/thumbnail.jpg";
        $cloudUploaded = false;

        $fileStorageService = app(FileStorageService::class);
        if ($fileStorageService->isActive()) {
            try {
                $videoUpload = $fileStorageService->uploadFromPath(
                    $fullPath,
                    "whiteboard-videos/{$this->video->job_id}/final_video.mp4",
                    'video/mp4'
                );
                if ($videoUpload) {
                    $videoUrl = $videoUpload['file_url'];
                    $cloudUploaded = true;
                }

                if (file_exists($thumbPath)) {
                    $thumbUpload = $fileStorageService->uploadFromPath(
                        $thumbPath,
                        "whiteboard-videos/{$this->video->job_id}/thumbnail.jpg",
                        'image/jpeg'
                    );
                    if ($thumbUpload) {
                        $thumbnailCloudPath = $thumbUpload['file_url'];
                    }
                }
            } catch (Exception $e) {
                Log::warning('Cloud upload failed for video, using local: ' . $e->getMessage());
            }
        }

        $this->video->markAsCompleted($storagePath, $videoUrl);

        $totalDuration = (int) ($status['duration_seconds'] ?? 0);
        $existingMeta = $this->video->metadata ?? [];
        $this->video->update([
            'duration_seconds' => $totalDuration,
            'metadata' => array_merge($existingMeta, [
                'thumbnail_path' => $thumbnailCloudPath,
                'pipeline' => 'manim',
                'cloud_uploaded' => $cloudUploaded,
            ]),
        ]);

        // Cleanup on Python server
        $manimService->deleteJob($this->video->job_id);

        // Clean up local files if uploaded to cloud
        if ($cloudUploaded) {
            @unlink($fullPath);
            @unlink($thumbPath);
            @rmdir(dirname($fullPath));
        }

        Log::info('Manim video completed via status check', [
            'video_id' => $this->video->id,
            'duration' => $totalDuration,
            'cloud_uploaded' => $cloudUploaded,
        ]);
    }
}
