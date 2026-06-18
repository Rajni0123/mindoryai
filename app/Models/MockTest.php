<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MockTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exam_id',
        'title',
        'question_ids',
        'total_questions',
        'duration_minutes',
        'config',
        'status',
        'score',
        'correct_answers',
        'wrong_answers',
        'unanswered',
        'time_taken_seconds',
        'answers',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'question_ids' => 'array',
        'config' => 'array',
        'answers' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'user_id' => 'integer',
        'exam_id' => 'integer',
        'total_questions' => 'integer',
        'duration_minutes' => 'integer',
        'score' => 'integer',
        'correct_answers' => 'integer',
        'wrong_answers' => 'integer',
        'unanswered' => 'integer',
        'time_taken_seconds' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getAccuracyAttribute(): float
    {
        if (!$this->total_questions || $this->total_questions === 0) {
            return 0;
        }
        return round(($this->correct_answers / $this->total_questions) * 100, 1);
    }

    public function getFormattedTimeTakenAttribute(): string
    {
        $secs = max(0, (int) abs($this->time_taken_seconds ?? 0));
        $minutes = intdiv($secs, 60);
        $seconds = $secs % 60;
        return "{$minutes}m {$seconds}s";
    }
}
