<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnswerBank extends Model
{
    protected $table = 'answer_bank';

    protected $fillable = [
        'question_hash',
        'original_question',
        'normalized_question',
        'answer',
        'source_type',
        'subject',
        'topic',
        'class_level',
        'exam_type',
        'language',
        'hit_count',
        'last_hit_at',
        'expires_at',
        'token_count_saved',
        'cost_saved',
        'is_active',
    ];

    protected $casts = [
        'hit_count' => 'integer',
        'token_count_saved' => 'integer',
        'cost_saved' => 'decimal:6',
        'is_active' => 'boolean',
        'last_hit_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope for active entries
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for valid (not expired) entries with TTL enforcement
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check if entry has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Scope for source type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('source_type', $type);
    }

    /**
     * Scope for subject
     */
    public function scopeOfSubject($query, string $subject)
    {
        return $query->where('subject', $subject);
    }

    /**
     * Scope for exam type
     */
    public function scopeOfExamType($query, string $examType)
    {
        return $query->where('exam_type', $examType);
    }

    /**
     * Get most used entries
     */
    public function scopeMostUsed($query, int $limit = 10)
    {
        return $query->orderBy('hit_count', 'desc')->limit($limit);
    }

    /**
     * Search by question
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('original_question', 'LIKE', "%{$searchTerm}%")
              ->orWhere('normalized_question', 'LIKE', "%{$searchTerm}%");
        });
    }
}
