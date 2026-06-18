<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicPerformance extends Model
{
    protected $table = 'topic_performance';

    protected $fillable = [
        'user_id',
        'topic',
        'subject',
        'attempts',
        'correct_answers',
        'total_time_spent',
        'difficulty_level',
        'success_rate',
        'last_attempt',
        'first_learned_at',
        'needs_revision',
    ];

    protected $casts = [
        'success_rate' => 'decimal:2',
        'last_attempt' => 'datetime',
        'first_learned_at' => 'datetime',
        'needs_revision' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate and update success rate
     */
    public function updateSuccessRate(): void
    {
        if ($this->attempts > 0) {
            $this->success_rate = ($this->correct_answers / $this->attempts) * 100;
            $this->save();
        }
    }

    /**
     * Check if topic needs revision based on time and performance
     */
    public function checkRevisionNeeded(): bool
    {
        // Needs revision if:
        // 1. Success rate is below 60%
        // 2. Haven't practiced in 7+ days
        // 3. Difficulty is hard and success rate < 70%

        if ($this->success_rate < 60) {
            return true;
        }

        if ($this->last_attempt && $this->last_attempt->diffInDays(now()) > 7) {
            return true;
        }

        if ($this->difficulty_level === 'hard' && $this->success_rate < 70) {
            return true;
        }

        return false;
    }

    /**
     * Get performance status label
     */
    public function getStatusLabel(): string
    {
        if ($this->success_rate >= 80) {
            return 'mastered';
        } elseif ($this->success_rate >= 60) {
            return 'learning';
        } elseif ($this->success_rate >= 40) {
            return 'needs_practice';
        }
        return 'struggling';
    }

    /**
     * Scope for weak topics (low success rate)
     */
    public function scopeWeakTopics($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->where('success_rate', '<', 60)
            ->where('attempts', '>=', 3)
            ->orderBy('success_rate');
    }

    /**
     * Scope for topics needing revision
     */
    public function scopeNeedsRevision($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->where('needs_revision', true)
            ->orderBy('last_attempt');
    }

    /**
     * Scope for strong topics
     */
    public function scopeStrongTopics($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->where('success_rate', '>=', 80)
            ->where('attempts', '>=', 5)
            ->orderByDesc('success_rate');
    }
}
