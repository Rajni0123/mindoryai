<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyBattleAnswer extends Model
{
    protected $fillable = [
        'room_id',
        'participant_id',
        'user_id',
        'question_index',
        'selected_answer',
        'is_correct',
        'response_time_ms',
        'points_earned',
        'bonus_points',
        'answered_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    /**
     * The room this answer belongs to
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(StudyBattleRoom::class, 'room_id');
    }

    /**
     * The participant who submitted this answer
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(StudyBattleParticipant::class, 'participant_id');
    }

    /**
     * The user who submitted this answer
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get total points (base + bonus)
     */
    public function getTotalPointsAttribute(): int
    {
        return $this->points_earned + $this->bonus_points;
    }

    /**
     * Check if this was a timeout (no answer)
     */
    public function isTimeout(): bool
    {
        return $this->selected_answer === null;
    }

    /**
     * Format response time as seconds
     */
    public function getFormattedTimeAttribute(): string
    {
        return number_format($this->response_time_ms / 1000, 2) . 's';
    }

    /**
     * Scope for correct answers
     */
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    /**
     * Scope for wrong answers (including timeouts)
     */
    public function scopeWrong($query)
    {
        return $query->where('is_correct', false);
    }

    /**
     * Scope for timeouts
     */
    public function scopeTimeouts($query)
    {
        return $query->whereNull('selected_answer');
    }

    /**
     * Scope for a specific question
     */
    public function scopeForQuestion($query, int $questionIndex)
    {
        return $query->where('question_index', $questionIndex);
    }
}
