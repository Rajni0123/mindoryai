<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoCache extends Model
{
    protected $table = 'video_cache';

    protected $fillable = [
        'content_hash',
        'topic',
        'style',
        'duration_seconds',
        'storyboard_json',
        'script_text',
        'audio_path',
        'video_path',
        'hit_count',
        'is_active',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'hit_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get storyboard as array
     */
    public function getStoryboardAttribute(): array
    {
        return json_decode($this->storyboard_json, true) ?? [];
    }

    /**
     * Set storyboard from array
     */
    public function setStoryboardAttribute(array $storyboard): void
    {
        $this->attributes['storyboard_json'] = json_encode($storyboard);
    }

    /**
     * Scope for active entries
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for style
     */
    public function scopeOfStyle($query, string $style)
    {
        return $query->where('style', $style);
    }

    /**
     * Get most used videos
     */
    public function scopeMostUsed($query, int $limit = 10)
    {
        return $query->orderBy('hit_count', 'desc')->limit($limit);
    }

    /**
     * Search by topic
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where('topic', 'LIKE', "%{$searchTerm}%");
    }
}
