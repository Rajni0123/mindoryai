<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhiteboardVideo;
use App\Models\Setting;
use App\Jobs\ProcessWhiteboardVideo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhiteboardVideoController extends Controller
{
    /**
     * Create a new whiteboard video from document
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        // Check if whiteboard video feature is enabled
        $isEnabled = Setting::get('whiteboard_video_enabled', '1');
        if ($isEnabled !== '1') {
            return response()->json([
                'success' => false,
                'message' => 'Whiteboard video feature is currently disabled',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'document_content' => 'required|string|min:3',
            'document_path' => 'nullable|string',
            'language' => 'nullable|string|max:10',
            'voice' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $language = $request->language ?? 'en';
            $topicText = trim($request->document_content);

            // ── TOPIC CACHE: check if a completed video for same topic+language exists ──
            $normalizedTopic = mb_strtolower(trim($topicText));
            $cachedVideo = WhiteboardVideo::where('status', 'completed')
                ->whereNotNull('final_video_path')
                ->whereRaw("LOWER(TRIM(document_content)) = ?", [$normalizedTopic])
                ->latest('completed_at')
                ->first();

            if ($cachedVideo && $cachedVideo->final_video_path && Storage::exists($cachedVideo->final_video_path)) {
                // Clone the cached video record for this user
                $video = WhiteboardVideo::create([
                    'user_id' => auth()->id(),
                    'job_id' => Str::uuid(),
                    'title' => $request->title ?? $cachedVideo->title,
                    'document_content' => $topicText,
                    'status' => 'completed',
                    'storyboard' => $cachedVideo->storyboard,
                    'final_video_path' => $cachedVideo->final_video_path,
                    'final_video_url' => $cachedVideo->final_video_url,
                    'total_scenes' => $cachedVideo->total_scenes,
                    'processed_scenes' => $cachedVideo->processed_scenes,
                    'duration_seconds' => $cachedVideo->duration_seconds,
                    'metadata' => [
                        'language' => $language,
                        'voice' => $request->voice ?? null,
                        'cached_from' => $cachedVideo->id,
                    ],
                    'started_at' => now(),
                    'completed_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Video ready (cached)',
                    'data' => [
                        'video_id' => $video->id,
                        'job_id' => $video->job_id,
                        'status' => 'completed',
                        'cached' => true,
                        'duration_seconds' => $video->duration_seconds,
                    ],
                ], 201);
            }

            // ── No cache hit: generate new video ──
            // Get feature-specific model ID (highest priority)
            $featureModelId = Setting::where('key', 'feature_model_whiteboard')->value('value');

            // If no feature-specific model, fallback to old whiteboard setting
            if ($featureModelId) {
                $modelId = (int) $featureModelId;
            } else {
                $modelId = (int) Setting::get('whiteboard_video_model_id', '15');
            }

            // Create video record
            $video = WhiteboardVideo::create([
                'user_id' => auth()->id(),
                'job_id' => Str::uuid(),
                'title' => $request->title ?? 'Whiteboard Video',
                'document_path' => $request->document_path,
                'document_content' => $topicText,
                'status' => 'pending',
                'metadata' => [
                    'language' => $language,
                    'voice' => $request->voice ?? null,
                ],
            ]);

            // Dispatch job to process video with model ID
            ProcessWhiteboardVideo::dispatch($video, $modelId);

            return response()->json([
                'success' => true,
                'message' => 'Video generation started successfully',
                'data' => [
                    'video_id' => $video->id,
                    'job_id' => $video->job_id,
                    'status' => $video->status,
                    'model_id' => $modelId,
                    'cached' => false,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start video generation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get video status and details
     *
     * @param string $jobId
     * @return JsonResponse
     */
    public function status(string $jobId): JsonResponse
    {
        $video = WhiteboardVideo::where('job_id', $jobId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $video->id,
                'job_id' => $video->job_id,
                'title' => $video->title,
                'status' => $video->status,
                'progress_percentage' => $video->getProgressPercentage(),
                'total_scenes' => $video->total_scenes,
                'processed_scenes' => $video->processed_scenes,
                'duration_seconds' => $video->duration_seconds,
                'final_video_url' => $video->status === 'completed'
                    ? url("/api/whiteboard-video/stream/{$video->job_id}")
                    : null,
                'thumbnail_url' => $video->metadata['thumbnail_path'] ?? null,
                'error_message' => $video->error_message,
                'started_at' => $video->started_at,
                'completed_at' => $video->completed_at,
                'created_at' => $video->created_at,
            ],
        ]);
    }

    /**
     * List user's videos
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $status = $request->input('status');

        $query = WhiteboardVideo::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $videos = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $videos->map(function ($video) {
                return [
                    'id' => $video->id,
                    'job_id' => $video->job_id,
                    'title' => $video->title,
                    'status' => $video->status,
                    'progress_percentage' => $video->getProgressPercentage(),
                    'total_scenes' => $video->total_scenes,
                    'duration_seconds' => $video->duration_seconds,
                    'final_video_url' => $video->status === 'completed'
                        ? url("/api/whiteboard-video/stream/{$video->job_id}")
                        : null,
                    'thumbnail_url' => $video->metadata['thumbnail_path'] ?? null,
                    'created_at' => $video->created_at,
                    'completed_at' => $video->completed_at,
                ];
            }),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
            ],
        ]);
    }

    /**
     * Delete a video
     *
     * @param string $jobId
     * @return JsonResponse
     */
    public function delete(string $jobId): JsonResponse
    {
        $video = WhiteboardVideo::where('job_id', $jobId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found',
            ], 404);
        }

        // Delete video files from storage
        if ($video->final_video_path) {
            \Storage::delete($video->final_video_path);
        }

        // Delete assets directory
        \Storage::deleteDirectory($video->getStorageDirectory());

        // Delete database record
        $video->delete();

        return response()->json([
            'success' => true,
            'message' => 'Video deleted successfully',
        ]);
    }

    /**
     * Stream video file for playback
     * Accepts auth via Bearer token header or ?token= query param
     */
    public function stream(Request $request, string $jobId)
    {
        // Support token via query parameter for video players that can't send headers
        $user = auth()->user();
        if (!$user && $request->query('token')) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->query('token'));
            $user = $token?->tokenable;
        }

        if (!$user) {
            abort(401, 'Unauthorized');
        }

        $video = WhiteboardVideo::where('job_id', $jobId)
            ->where('user_id', $user->id)
            ->first();

        if (!$video || !$video->final_video_path) {
            abort(404, 'Video not found');
        }

        // If video URL is a cloud URL, redirect to it
        if ($video->final_video_url && str_starts_with($video->final_video_url, 'http')) {
            return redirect($video->final_video_url);
        }

        // Fallback: stream from local storage
        $path = storage_path("app/{$video->final_video_path}");

        if (!file_exists($path)) {
            abort(404, 'Video file not found');
        }

        $size = filesize($path);
        $headers = [
            'Content-Type' => 'video/mp4',
            'Content-Length' => $size,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=3600',
        ];

        // Support range requests for video seeking
        if (request()->hasHeader('Range')) {
            $range = request()->header('Range');
            preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
            $start = (int) $matches[1];
            $end = !empty($matches[2]) ? (int) $matches[2] : $size - 1;

            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
            $headers['Content-Length'] = $end - $start + 1;

            return response()->stream(function () use ($path, $start, $end) {
                $stream = fopen($path, 'rb');
                fseek($stream, $start);
                $remaining = $end - $start + 1;
                while ($remaining > 0 && !feof($stream)) {
                    $chunk = fread($stream, min(8192, $remaining));
                    echo $chunk;
                    $remaining -= strlen($chunk);
                }
                fclose($stream);
            }, 206, $headers);
        }

        return response()->file($path, $headers);
    }

    /**
     * Get storyboard preview before generation
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function previewStoryboard(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_content' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $storyboardService = new \App\Services\WhiteboardVideo\GeminiStoryboardService();

            $storyboard = $storyboardService->generateStoryboard($request->document_content);

            return response()->json([
                'success' => true,
                'data' => [
                    'storyboard' => $storyboard,
                    'total_scenes' => count($storyboard['scenes']),
                    'estimated_duration' => $storyboardService->calculateTotalDuration($storyboard),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate storyboard preview',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Callback from Python/Manim server when video processing completes
     */
    public function manimCallback(Request $request): JsonResponse
    {
        // Verify callback secret
        $expectedSecret = Setting::get('manim_callback_secret', '');
        if ($expectedSecret && $request->input('secret') !== $expectedSecret) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $jobId = $request->input('job_id');
        $status = $request->input('status');

        $video = WhiteboardVideo::where('job_id', $jobId)->first();
        if (!$video) {
            return response()->json(['error' => 'Video not found'], 404);
        }

        if ($status === 'completed') {
            $video->update([
                'status' => 'completed',
                'duration_seconds' => $request->input('duration_seconds'),
                'completed_at' => now(),
            ]);
        } elseif ($status === 'failed') {
            $video->markAsFailed($request->input('error_message', 'Manim server error'));
        }

        return response()->json(['success' => true]);
    }
}
