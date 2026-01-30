<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'subject',
        'topic',
        'subtopic',
        'year',
        'type',
        'question_text',
        'options',
        'correct_answer',
        'explanation',
        'solution_steps',
        'difficulty',
        'language',
        'tags',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'tags' => 'array',
        'year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function isCorrect(string $answer): bool
    {
        return strtoupper(trim($answer)) === strtoupper(trim($this->correct_answer));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySubject($query, string $subject)
    {
        return $query->where('subject', $subject);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByYear($query, int $year)
    {
        return $query->where('year', $year);
    }
}
