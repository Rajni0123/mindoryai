<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NcertCache extends Model
{
    protected $table = 'ncert_cache';

    protected $fillable = [
        'content_hash',
        'subject',
        'class_level',
        'book_name',
        'chapter_number',
        'chapter_name',
        'content_type',
        'content',
        'page_numbers',
        'hit_count',
        'is_active',
    ];

    protected $casts = [
        'chapter_number' => 'integer',
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
     * Scope for subject
     */
    public function scopeOfSubject($query, string $subject)
    {
        return $query->where('subject', $subject);
    }

    /**
     * Scope for class level
     */
    public function scopeOfClass($query, string $classLevel)
    {
        return $query->where('class_level', $classLevel);
    }

    /**
     * Scope for content type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    /**
     * Get most accessed content
     */
    public function scopeMostUsed($query, int $limit = 10)
    {
        return $query->orderBy('hit_count', 'desc')->limit($limit);
    }

    /**
     * Search by chapter name or content
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('chapter_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('content', 'LIKE', "%{$searchTerm}%");
        });
    }
}
