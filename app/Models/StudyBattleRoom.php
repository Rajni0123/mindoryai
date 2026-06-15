<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyBattleRoom extends Model
{
    protected $fillable = [
        'room_code',
        'host_user_id',
        'title',
        'topic',
        'subject',
        'exam_type',
        'difficulty',
        'status',
        'max_players',
        'min_players',
        'question_count',
        'time_per_question',
        'current_question_index',
        'question_started_at',
        'battle_started_at',
        'battle_ended_at',
        'expires_at',
        'questions_data',
        'battle_settings',
    ];

    protected $casts = [
        'questions_data' => 'array',
        'battle_settings' => 'array',
        'question_started_at' => 'datetime',
        'battle_started_at' => 'datetime',
        'battle_ended_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_host' => 'boolean',
    ];

    /**
     * Room host
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    /**
     * All participants in this room
     */
    public function participants(): HasMany
    {
        return $this->hasMany(StudyBattleParticipant::class, 'room_id');
    }

    /**
     * All answers submitted in this room
     */
    public function answers(): HasMany
    {
        return $this->hasMany(StudyBattleAnswer::class, 'room_id');
    }

    /**
     * Get count of active participants
     */
    public function getActiveParticipantsCountAttribute(): int
    {
        return $this->participants()
            ->whereIn('status', ['joined', 'ready', 'playing'])
            ->count();
    }

    /**
     * Check if room can be started
     */
    public function canStart(): bool
    {
        if ($this->status !== 'waiting') {
            return false;
        }

        $activeCount = $this->active_participants_count;
        return $activeCount >= $this->min_players;
    }

    /**
     * Check if room is full
     */
    public function isFull(): bool
    {
        return $this->active_participants_count >= $this->max_players;
    }

    /**
     * Get current question from questions_data
     */
    public function getCurrentQuestion(): ?array
    {
        $questions = $this->questions_data['questions'] ?? [];
        return $questions[$this->current_question_index] ?? null;
    }

    /**
     * Get total questions count
     */
    public function getTotalQuestionsAttribute(): int
    {
        return count($this->questions_data['questions'] ?? []);
    }

    /**
     * Check if battle has more questions
     */
    public function hasMoreQuestions(): bool
    {
        return $this->current_question_index < $this->total_questions - 1;
    }

    /**
     * Scope for active rooms
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'starting', 'in_progress']);
    }

    /**
     * Scope for waiting rooms
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    /**
     * Scope for expired rooms
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'waiting')
            ->where('expires_at', '<', now());
    }
}
