<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageCache extends Model
{
    protected $table = 'image_cache';

    protected $fillable = [
        'image_hash',
        'topic',
        'style',
        'frame_number',
        'file_path',
        'file_size',
        'hit_count',
        'is_active',
    ];

    protected $casts = [
        'frame_number' => 'integer',
        'file_size' => 'integer',
        'hit_count' => 'integer',
        'is_active' => 'boolean',
    ];

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
     * Get most used images
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

    /**
     * Get file URL
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
