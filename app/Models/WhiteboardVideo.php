<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteboardVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'title',
        'document_path',
        'document_content',
        'status',
        'storyboard',
        'final_video_url',
        'final_video_path',
        'total_scenes',
        'processed_scenes',
        'duration_seconds',
        'error_message',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'storyboard' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the video
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if video is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if video is processing
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, [
            'processing',
            'generating_storyboard',
            'generating_assets',
            'stitching_video'
        ]);
    }

    /**
     * Check if video has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get progress percentage based on current stage.
     *
     * Stages:  pending(0-5) → generating_storyboard(5-15) → generating_assets(15-70) → stitching_video(70-90) → completed(100)
     * Within generating_assets, scene progress fills from 15% to 70%.
     */
    public function getProgressPercentage(): int
    {
        return match ($this->status) {
            'pending'                => 2,
            'processing'             => 5,
            'generating_storyboard'  => 10,
            'generating_assets'      => $this->getAssetProgress(),
            'stitching_video'        => 85,
            'completed'              => 100,
            'failed'                 => $this->processed_scenes > 0 ? $this->getAssetProgress() : 0,
            default                  => 0,
        };
    }

    /**
     * Asset generation progress: maps scene count to 15-70% range.
     */
    private function getAssetProgress(): int
    {
        if ($this->total_scenes <= 0) {
            return 20;
        }
        $ratio = min(1.0, $this->processed_scenes / $this->total_scenes);
        return (int) (15 + ($ratio * 55)); // 15% to 70%
    }

    /**
     * Mark video as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark video as completed
     */
    public function markAsCompleted(string $videoPath, string $videoUrl): void
    {
        $this->update([
            'status' => 'completed',
            'final_video_path' => $videoPath,
            'final_video_url' => $videoUrl,
            'completed_at' => now(),
        ]);
    }

    /**
     * Update status
     */
    public function updateStatus(string $status): void
    {
        $this->update(['status' => $status]);
    }

    /**
     * Get storage directory path for this video
     */
    public function getStorageDirectory(): string
    {
        return "videos/{$this->job_id}";
    }

    /**
     * Get assets directory path
     */
    public function getAssetsDirectory(): string
    {
        return "{$this->getStorageDirectory()}/assets";
    }

    /**
     * Get scenes directory path
     */
    public function getScenesDirectory(): string
    {
        return "{$this->getAssetsDirectory()}/scenes";
    }

    /**
     * Get audio directory path
     */
    public function getAudioDirectory(): string
    {
        return "{$this->getAssetsDirectory()}/audio";
    }

    /**
     * Scope to get videos by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get recent videos
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
