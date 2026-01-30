<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mobile_chat_id',
        'title',
        'exam',
        'subject',
        'topic',
        'difficulty_level',
        'language',
        'duration_minutes',
        'total_questions',
        'correct_answers',
        'wrong_answers',
        'skipped_questions',
        'score',
        'time_taken_seconds',
        'status',
        'started_at',
        'completed_at',
        'questions_data',
        'answers_data',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'total_questions' => 'integer',
        'correct_answers' => 'integer',
        'wrong_answers' => 'integer',
        'skipped_questions' => 'integer',
        'duration_minutes' => 'integer',
        'time_taken_seconds' => 'integer',
        'questions_data' => 'array',
        'answers_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the quiz attempt
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the associated mobile chat
     */
    public function mobileChat()
    {
        return $this->belongsTo(MobileChat::class);
    }

    /**
     * Scope to get only completed attempts
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get attempts by date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Get formatted time taken
     */
    public function getFormattedTimeTakenAttribute()
    {
        if (!$this->time_taken_seconds) {
            return 'N/A';
        }

        $minutes = floor($this->time_taken_seconds / 60);
        $seconds = $this->time_taken_seconds % 60;

        return sprintf('%dm %ds', $minutes, $seconds);
    }

    /**
     * Get accuracy percentage
     */
    public function getAccuracyAttribute()
    {
        if ($this->total_questions == 0) {
            return 0;
        }

        return round(($this->correct_answers / $this->total_questions) * 100, 2);
    }
}
