<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyChallenge extends Model
{
    protected $fillable = [
        'challenge_date',
        'title',
        'subject',
        'difficulty',
        'questions',
        'time_limit_seconds',
        'reward_credits',
        'is_active',
        'is_ai_generated',
    ];

    protected $casts = [
        'challenge_date' => 'date',
        'questions' => 'array',
        'is_active' => 'boolean',
        'is_ai_generated' => 'boolean',
        'time_limit_seconds' => 'integer',
        'reward_credits' => 'integer',
    ];

    public function attempts()
    {
        return $this->hasMany(DailyChallengeAttempt::class);
    }

    public static function getToday()
    {
        return static::where('challenge_date', today())
            ->where('is_active', true)
            ->first();
    }

    public function getParticipantsCount(): int
    {
        return $this->attempts()->where('completed', true)->count();
    }

    public function getAverageScore(): float
    {
        return $this->attempts()->where('completed', true)->avg('score') ?? 0;
    }
}
