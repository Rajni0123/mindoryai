<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizCache extends Model
{
    protected $table = 'quiz_cache'; // Override default plural table name

    protected $fillable = [
        'image_hash',
        'quiz_data',
        'quiz_type',
        'difficulty',
        'subject',
        'topics',
        'question_count',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'quiz_data' => 'array',
        'topics' => 'array',
        'difficulty' => 'integer',
        'question_count' => 'integer',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    /**
     * Increment usage count and update last_used_at timestamp
     */
    public function markAsUsed()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Find quiz by image hash
     */
    public static function findByImageHash(string $hash)
    {
        return static::where('image_hash', $hash)->first();
    }

    /**
     * Scope for filtering by quiz type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('quiz_type', $type);
    }

    /**
     * Scope for filtering by subject
     */
    public function scopeOfSubject($query, string $subject)
    {
        return $query->where('subject', $subject);
    }
}
