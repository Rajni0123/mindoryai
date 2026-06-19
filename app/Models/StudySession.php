<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends Model
{
    protected $table = 'study_sessions';

    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'duration',
        'questions_asked',
        'topics_covered',
        'focus_score',
        'productivity_rating',
        'xp_earned',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'topics_covered' => 'array',
        'focus_score' => 'decimal:1',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Start a new study session
     */
    public static function startSession(int $userId): self
    {
        return self::create([
            'user_id' => $userId,
            'start_time' => now(),
            'duration' => 0,
            'questions_asked' => 0,
            'topics_covered' => [],
            'focus_score' => 0,
            'productivity_rating' => 'medium',
            'xp_earned' => 0,
        ]);
    }

    /**
     * End the current session and calculate metrics
     */
    public function endSession(): void
    {
        $this->end_time = now();
        $this->duration = $this->start_time->diffInMinutes($this->end_time);
        $this->calculateProductivityRating();
        $this->save();
    }

    /**
     * Add a question to the session
     */
    public function addQuestion(string $topic = null): void
    {
        $this->questions_asked++;

        if ($topic) {
            $topics = $this->topics_covered ?? [];
            if (!in_array($topic, $topics)) {
                $topics[] = $topic;
                $this->topics_covered = $topics;
            }
        }

        $this->save();
    }

    /**
     * Calculate focus score based on session activity
     */
    public function calculateFocusScore(): float
    {
        // Focus score based on questions per minute and topic consistency
        $duration = max(1, $this->duration);
        $questionsPerMinute = $this->questions_asked / $duration;

        // Ideal is 0.5-2 questions per minute
        if ($questionsPerMinute >= 0.5 && $questionsPerMinute <= 2) {
            $score = 10;
        } elseif ($questionsPerMinute < 0.5) {
            $score = max(1, $questionsPerMinute * 20);
        } else {
            $score = max(5, 10 - ($questionsPerMinute - 2) * 2);
        }

        // Bonus for topic consistency (fewer topics = more focused)
        $topicCount = count($this->topics_covered ?? []);
        if ($topicCount <= 2) {
            $score = min(10, $score + 1);
        } elseif ($topicCount > 5) {
            $score = max(1, $score - 1);
        }

        $this->focus_score = round($score, 1);
        return $this->focus_score;
    }

    /**
     * Calculate productivity rating
     */
    public function calculateProductivityRating(): void
    {
        $this->calculateFocusScore();

        if ($this->focus_score >= 7 && $this->questions_asked >= 5) {
            $this->productivity_rating = 'high';
        } elseif ($this->focus_score >= 4 || $this->questions_asked >= 3) {
            $this->productivity_rating = 'medium';
        } else {
            $this->productivity_rating = 'low';
        }
    }

    /**
     * Calculate XP earned for this session
     */
    public function calculateXP(): int
    {
        $baseXP = $this->questions_asked * 5;

        // Duration bonus (max 50 XP for 30+ min sessions)
        $durationBonus = min(50, $this->duration * 1.5);

        // Focus bonus
        $focusBonus = $this->focus_score * 3;

        // Productivity multiplier
        $multiplier = match ($this->productivity_rating) {
            'high' => 1.5,
            'medium' => 1.0,
            'low' => 0.7,
        };

        $this->xp_earned = (int) round(($baseXP + $durationBonus + $focusBonus) * $multiplier);
        return $this->xp_earned;
    }

    /**
     * Get active session for user
     */
    public static function getActiveSession(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();
    }

    /**
     * Scope for today's sessions
     */
    public function scopeToday($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->whereDate('start_time', today());
    }

    /**
     * Scope for this week's sessions
     */
    public function scopeThisWeek($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->where('start_time', '>=', now()->startOfWeek());
    }
}
