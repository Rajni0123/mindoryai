<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyChallengeAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'daily_challenge_id',
        'score',
        'total_questions',
        'time_taken_seconds',
        'answers',
        'completed',
        'credits_earned',
    ];

    protected $casts = [
        'answers' => 'array',
        'completed' => 'boolean',
        'score' => 'integer',
        'total_questions' => 'integer',
        'time_taken_seconds' => 'integer',
        'credits_earned' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function challenge()
    {
        return $this->belongsTo(DailyChallenge::class, 'daily_challenge_id');
    }
}
