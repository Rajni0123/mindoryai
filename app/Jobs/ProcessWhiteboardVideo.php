<?php

namespace App\Jobs;

use App\Models\WhiteboardVideo;
use App\Services\WhiteboardVideo\GeminiStoryboardService;
use App\Services\WhiteboardVideo\GeminiImageService;
use App\Services\WhiteboardVideo\GoogleTTSService;
use App\Services\WhiteboardVideo\FFmpegVideoService;
use App\Services\WhiteboardVideo\ManimVideoService;
use App\Services\FileStorageService;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\CheckManimVideoStatus;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProcessWhiteboardVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public WhiteboardVideo $video;
    public int $modelId;

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
    public $timeout = 1800; // 30 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(WhiteboardVideo $video, int $modelId = 15)
    {
        $this->video = $video;
        $this->modelId = $modelId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting whiteboard video processing', [
                'video_id' => $this->video->id,
                'job_id' => $this->video->job_id,
            ]);

            // Generate and store topic hash
            $topicHash = WhiteboardVideo::generateTopicHash($this->video->document_content);
            $this->video->update([
                'status' => 'processing',
                'started_at' => now(),
                'topic_hash' => $topicHash,
            ]);

            // Check for cached video with same topic
            $cachedVideo = WhiteboardVideo::where('topic_hash', $topicHash)
                ->where('status', 'completed')
                ->whereNotNull('final_video_url')
                ->where('id', '!=', $this->video->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($cachedVideo) {
                Log::info('Cache HIT - reusing existing whiteboard video', [
                    'video_id' => $this->video->id,
                    'cached_from' => $cachedVideo->id,
                    'topic_hash' => $topicHash,
                ]);

                $this->video->update([
                    'status' => 'completed',
                    'final_video_url' => $cachedVideo->final_video_url,
                    'final_video_path' => $cachedVideo->final_video_path,
                    'storyboard' => $cachedVideo->storyboard,
                    'total_scenes' => $cachedVideo->total_scenes,
                    'processed_scenes' => $cachedVideo->total_scenes,
                    'duration_seconds' => $cachedVideo->duration_seconds,
                    'cached_from_id' => $cachedVideo->id,
                    'completed_at' => now(),
                    'metadata' => array_merge($this->video->metadata ?? [], [
                        'cached' => true,
                        'cached_from_video_id' => $cachedVideo->id,
                        'thumbnail_path' => $cachedVideo->metadata['thumbnail_path'] ?? null,
                        'pipeline' => $cachedVideo->metadata['pipeline'] ?? 'cached',
                        'cloud_uploaded' => $cachedVideo->metadata['cloud_uploaded'] ?? false,
                    ]),
                ]);

                return;
            }

            Log::info('Cache MISS - generating new whiteboard video', [
                'video_id' => $this->video->id,
                'topic_hash' => $topicHash,
            ]);

            // Check which pipeline to use: manim (Python) or php (legacy)
            $pipeline = Setting::get('whiteboard_video_pipeline', 'php');

            if ($pipeline === 'manim') {
                $this->handleManimPipeline();
            } else {
                $this->handlePhpPipeline();
            }

        } catch (Exception $e) {
            $this->handleFailure($e);
        }
    }

    /**
     * Python/Manim pipeline - offload to Manim server
     */
    private function handleManimPipeline(): void
    {
        $manimService = new ManimVideoService();

        // Check server health
        if (!$manimService->isHealthy()) {
            Log::warning('Manim server not available, falling back to PHP pipeline', [
                'video_id' => $this->video->id,
            ]);
            $this->handlePhpPipeline();
            return;
        }

        // Get max scenes from user's plan config
        $usageService = app(\App\Services\UsageLimitService::class);
        $planConfig = $usageService->getPlanConfig($this->video->user);
        $maxVideoSeconds = $planConfig['max_video_length_seconds'] ?? 30;
        // Each scene is ~10 seconds, calculate max scenes (minimum 3)
        $maxScenes = max(3, (int) floor($maxVideoSeconds / 10));

        // Start generation on Python server
        $this->video->updateStatus('generating_storyboard');
        $metadata = $this->video->metadata ?? [];
        $result = $manimService->generateVideo(
            topic: $this->video->document_content,
            title: $this->video->title,
            laravelJobId: $this->video->job_id,
            language: $metadata['language'] ?? 'en',
            voice: $metadata['voice'] ?? null,
            maxScenes: $maxScenes,
        );

        Log::info('Manim server accepted job', [
            'video_id' => $this->video->id,
            'manim_job_id' => $result['job_id'] ?? null,
        ]);

        // Dispatch a delayed check job instead of blocking the queue worker with polling.
        // The Manim server will call back via /api/whiteboard-video/callback when done.
        // As a safety net, re-dispatch this job after 30s to check status once.
        CheckManimVideoStatus::dispatch($this->video, $manimService)
            ->delay(now()->addSeconds(30));
    }

    /**
     * Legacy PHP pipeline - GD images + Google TTS + FFmpeg
     */
    private function handlePhpPipeline(): void
    {
        // Step 1: Generate storyboard from document
        $storyboard = $this->generateStoryboard();

        // Step 2: Generate images for each scene
        $imagePaths = $this->generateImages($storyboard['scenes']);

        // Step 3: Generate audio for each scene
        $audioPaths = $this->generateAudio($storyboard['scenes']);

        // Step 4: Stitch video using FFmpeg
        $finalVideoPath = $this->stitchVideo($storyboard['scenes'], $imagePaths, $audioPaths);

        // Step 5: Generate thumbnail
        $thumbnailPath = $this->generateThumbnail($finalVideoPath);

        // Calculate total duration
        $totalDuration = $this->calculateTotalDuration($storyboard['scenes']);

        // Upload to cloud storage if active
        $videoUrl = Storage::url($finalVideoPath);
        $thumbnailCloudPath = $thumbnailPath;
        $cloudUploaded = false;

        $fileStorageService = app(FileStorageService::class);
        if ($fileStorageService->isActive()) {
            try {
                $localVideoFullPath = storage_path("app/{$finalVideoPath}");
                $videoUpload = $fileStorageService->uploadFromPath(
                    $localVideoFullPath,
                    "whiteboard-videos/{$this->video->job_id}/final_video.mp4",
                    'video/mp4'
                );
                if ($videoUpload) {
                    $videoUrl = $videoUpload['file_url'];
                    $cloudUploaded = true;
                    Log::info('PHP pipeline video uploaded to cloud', ['url' => $videoUrl]);
                }

                // Upload thumbnail
                $localThumbPath = storage_path("app/{$thumbnailPath}");
                if (file_exists($localThumbPath)) {
                    $thumbUpload = $fileStorageService->uploadFromPath(
                        $localThumbPath,
                        "whiteboard-videos/{$this->video->job_id}/thumbnail.jpg",
                        'image/jpeg'
                    );
                    if ($thumbUpload) {
                        $thumbnailCloudPath = $thumbUpload['file_url'];
                    }
                }
            } catch (Exception $e) {
                Log::warning('Cloud upload failed for PHP pipeline video: ' . $e->getMessage());
            }
        }

        // Mark as completed
        $this->video->markAsCompleted($finalVideoPath, $videoUrl);

        // Update metadata
        $videoSizeMb = Storage::exists($finalVideoPath) ? round(Storage::size($finalVideoPath) / 1024 / 1024, 2) : 0;
        $this->video->update([
            'duration_seconds' => $totalDuration,
            'metadata' => [
                'thumbnail_path' => $thumbnailCloudPath,
                'video_size_mb' => $videoSizeMb,
                'pipeline' => 'php',
                'cloud_uploaded' => $cloudUploaded,
            ],
        ]);

        // Clean up
        $this->cleanup();

        Log::info('PHP pipeline video completed', [
            'video_id' => $this->video->id,
            'final_url' => $videoUrl,
            'cloud_uploaded' => $cloudUploaded,
        ]);
    }

    /**
     * Generate storyboard from document
     *
     * @return array
     * @throws Exception
     */
    private function generateStoryboard(): array
    {
        $this->video->updateStatus('generating_storyboard');

        $storyboardService = new GeminiStoryboardService();

        $storyboard = $storyboardService->generateStoryboard($this->video->document_content);

        // Save storyboard to database
        $this->video->update([
            'storyboard' => $storyboard,
            'total_scenes' => count($storyboard['scenes']),
        ]);

        Log::info('Storyboard generated', [
            'video_id' => $this->video->id,
            'total_scenes' => count($storyboard['scenes']),
        ]);

        return $storyboard;
    }

    /**
     * Generate images for all scenes
     *
     * @param array $scenes
     * @return array
     * @throws Exception
     */
    private function generateImages(array $scenes): array
    {
        $this->video->updateStatus('generating_assets');

        $imageService = new GeminiImageService();

        $scenesDirectory = $this->video->getScenesDirectory();

        // Create directory
        Storage::makeDirectory($scenesDirectory);

        $imagePaths = [];

        foreach ($scenes as $index => $scene) {
            try {
                $imagePath = $imageService->generateWhiteboardImage(
                    $scene['visual_description'],
                    $scene['scene_number'],
                    $scenesDirectory
                );

                $imagePaths[$scene['scene_number']] = $imagePath;

                // Update progress
                $this->video->update([
                    'processed_scenes' => $index + 1,
                ]);

                Log::info("Generated image for scene {$scene['scene_number']}", [
                    'video_id' => $this->video->id,
                ]);

            } catch (Exception $e) {
                Log::error("Failed to generate image for scene {$scene['scene_number']}", [
                    'video_id' => $this->video->id,
                    'error' => $e->getMessage(),
                ]);

                // Continue with placeholder
                $imagePaths[$scene['scene_number']] = null;
            }
        }

        return $imagePaths;
    }

    /**
     * Generate audio for all scenes
     *
     * @param array $scenes
     * @return array
     * @throws Exception
     */
    private function generateAudio(array $scenes): array
    {
        $ttsService = new GoogleTTSService();

        $audioDirectory = $this->video->getAudioDirectory();

        // Create directory
        Storage::makeDirectory($audioDirectory);

        $audioPaths = [];

        foreach ($scenes as $scene) {
            try {
                $audioData = $ttsService->generateAudio(
                    $scene['narration'],
                    $scene['scene_number'],
                    $audioDirectory
                );

                $audioPaths[$scene['scene_number']] = $audioData;

                Log::info("Generated audio for scene {$scene['scene_number']}", [
                    'video_id' => $this->video->id,
                ]);

            } catch (Exception $e) {
                Log::error("Failed to generate audio for scene {$scene['scene_number']}", [
                    'video_id' => $this->video->id,
                    'error' => $e->getMessage(),
                ]);

                // Use scene duration as fallback
                $audioPaths[$scene['scene_number']] = [
                    'path' => null,
                    'duration' => $scene['duration'] ?? 10,
                ];
            }
        }

        return $audioPaths;
    }

    /**
     * Stitch video using FFmpeg
     *
     * @param array $scenes
     * @param array $imagePaths
     * @param array $audioPaths
     * @return string
     * @throws Exception
     */
    private function stitchVideo(array $scenes, array $imagePaths, array $audioPaths): string
    {
        $this->video->updateStatus('stitching_video');

        $ffmpegService = new FFmpegVideoService();

        $outputDirectory = $this->video->getStorageDirectory();

        $finalVideoPath = $ffmpegService->stitchVideo(
            $scenes,
            $imagePaths,
            $audioPaths,
            $outputDirectory,
            $this->video->job_id
        );

        Log::info('Video stitched successfully', [
            'video_id' => $this->video->id,
            'path' => $finalVideoPath,
        ]);

        return $finalVideoPath;
    }

    /**
     * Generate video thumbnail
     *
     * @param string $videoPath
     * @return string
     * @throws Exception
     */
    private function generateThumbnail(string $videoPath): string
    {
        $ffmpegService = new FFmpegVideoService();

        $thumbnailPath = $this->video->getStorageDirectory() . '/thumbnail.jpg';

        $ffmpegService->generateThumbnail($videoPath, $thumbnailPath);

        return $thumbnailPath;
    }

    /**
     * Calculate total duration from scenes
     *
     * @param array $scenes
     * @return int
     */
    private function calculateTotalDuration(array $scenes): int
    {
        $totalDuration = 0;

        foreach ($scenes as $scene) {
            $totalDuration += (int) ($scene['duration'] ?? 10);
        }

        return $totalDuration;
    }

    /**
     * Deduct credits for video generation (1 second = 1 credit)
     *
     * @param int $durationSeconds
     */
    private function deductCreditsForVideo(int $durationSeconds): void
    {
        try {
            $creditService = app(\App\Services\CreditService::class);

            $result = $creditService->deductCredits(
                user: $this->video->user,
                action: 'whiteboard_video_generation',
                customCost: $durationSeconds, // 1 second = 1 credit
                description: "Whiteboard video generation: {$durationSeconds} seconds",
                referenceType: \App\Models\WhiteboardVideo::class,
                referenceId: $this->video->id
            );

            if ($result['success']) {
                Log::info('Credits deducted for video', [
                    'video_id' => $this->video->id,
                    'duration' => $durationSeconds,
                    'credits_deducted' => $durationSeconds,
                    'new_balance' => $result['new_balance'],
                ]);
            } else {
                Log::warning('Failed to deduct credits for video', [
                    'video_id' => $this->video->id,
                    'message' => $result['message'],
                ]);
            }

        } catch (Exception $e) {
            Log::error('Error deducting credits for video', [
                'video_id' => $this->video->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clean up temporary files
     */
    private function cleanup(): void
    {
        try {
            $ffmpegService = new FFmpegVideoService();
            $ffmpegService->cleanup($this->video->getStorageDirectory());

            Log::info('Cleanup completed', [
                'video_id' => $this->video->id,
            ]);

        } catch (Exception $e) {
            Log::warning('Cleanup failed', [
                'video_id' => $this->video->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure
     *
     * @param Exception $exception
     */
    private function handleFailure(Exception $exception): void
    {
        Log::error('Whiteboard video processing failed', [
            'video_id' => $this->video->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->video->markAsFailed($exception->getMessage());

        // Optionally: Send notification to user
        // event(new VideoProcessingFailed($this->video));
    }

    /**
     * Handle job failure event
     *
     * @param Exception $exception
     */
    public function failed(Exception $exception): void
    {
        $this->handleFailure($exception);
    }
}
