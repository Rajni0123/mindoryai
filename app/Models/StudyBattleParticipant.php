<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyBattleParticipant extends Model
{
    protected $fillable = [
        'room_id',
        'user_id',
        'status',
        'is_host',
        'total_score',
        'correct_answers',
        'wrong_answers',
        'questions_answered',
        'total_time_ms',
        'current_streak',
        'best_streak',
        'last_poll_at',
        'joined_at',
        'finished_at',
        'rank',
    ];

    protected $casts = [
        'is_host' => 'boolean',
        'last_poll_at' => 'datetime',
        'joined_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * The room this participant belongs to
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(StudyBattleRoom::class, 'room_id');
    }

    /**
     * The user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Answers submitted by this participant
     */
    public function answers(): HasMany
    {
        return $this->hasMany(StudyBattleAnswer::class, 'participant_id');
    }

    /**
     * Calculate accuracy percentage
     */
    public function getAccuracyAttribute(): float
    {
        if ($this->questions_answered === 0) {
            return 0;
        }
        return round(($this->correct_answers / $this->questions_answered) * 100, 1);
    }

    /**
     * Calculate average response time in milliseconds
     */
    public function getAverageResponseTimeAttribute(): int
    {
        if ($this->questions_answered === 0) {
            return 0;
        }
        return (int) ($this->total_time_ms / $this->questions_answered);
    }

    /**
     * Format average response time as seconds
     */
    public function getFormattedAvgTimeAttribute(): string
    {
        $avgMs = $this->average_response_time;
        return number_format($avgMs / 1000, 1) . 's';
    }

    /**
     * Check if participant is still active (polling)
     */
    public function isActive(): bool
    {
        if (!$this->last_poll_at) {
            return false;
        }
        // Active if polled within last 10 seconds
        return $this->last_poll_at->diffInSeconds(now()) < 10;
    }

    /**
     * Check if participant has answered current question
     */
    public function hasAnsweredQuestion(int $questionIndex): bool
    {
        return $this->answers()
            ->where('question_index', $questionIndex)
            ->exists();
    }

    /**
     * Scope for active participants
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['joined', 'ready', 'playing']);
    }

    /**
     * Scope for playing participants
     */
    public function scopePlaying($query)
    {
        return $query->where('status', 'playing');
    }

    /**
     * Scope for disconnected detection
     */
    public function scopeDisconnected($query, int $seconds = 10)
    {
        return $query->whereIn('status', ['joined', 'ready', 'playing'])
            ->where('last_poll_at', '<', now()->subSeconds($seconds));
    }
}
