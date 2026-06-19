<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicFeedbackAnalytics extends Model
{
    protected $table = 'topic_feedback_analytics';

    protected $fillable = [
        'user_id',
        'topic',
        'subject',
        'total_questions',
        'liked_responses',
        'disliked_responses',
        'too_complex_count',
        'too_simple_count',
        'wrong_answer_count',
        'missing_steps_count',
        'satisfaction_rate',
        'last_feedback_at',
    ];

    protected $casts = [
        'satisfaction_rate' => 'decimal:2',
        'last_feedback_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update satisfaction rate
     */
    public function updateSatisfactionRate(): void
    {
        if ($this->total_questions > 0) {
            $this->satisfaction_rate = round(
                ($this->liked_responses / $this->total_questions) * 100,
                2
            );
            $this->save();
        }
    }

    /**
     * Get most common issue for this topic
     */
    public function getMostCommonIssue(): ?string
    {
        $issues = [
            'too_complex' => $this->too_complex_count,
            'too_simple' => $this->too_simple_count,
            'wrong_answer' => $this->wrong_answer_count,
            'missing_steps' => $this->missing_steps_count,
        ];

        $max = max($issues);
        if ($max === 0) return null;

        return array_search($max, $issues);
    }

    /**
     * Check if user struggles with this topic
     */
    public function isStrugglingTopic(): bool
    {
        return $this->satisfaction_rate < 50 && $this->total_questions >= 3;
    }

    /**
     * Scope for struggling topics
     */
    public function scopeStrugglingTopics($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->where('satisfaction_rate', '<', 50)
            ->where('total_questions', '>=', 3)
            ->orderBy('satisfaction_rate');
    }

    /**
     * Scope for strong topics
     */
    public function scopeStrongTopics($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->where('satisfaction_rate', '>=', 80)
            ->where('total_questions', '>=', 3)
            ->orderByDesc('satisfaction_rate');
    }

    /**
     * Get global topic analytics
     */
    public static function getGlobalTopicStats(): array
    {
        return self::selectRaw('
                topic,
                SUM(total_questions) as total_questions,
                SUM(liked_responses) as total_likes,
                SUM(disliked_responses) as total_dislikes,
                AVG(satisfaction_rate) as avg_satisfaction,
                SUM(too_complex_count) as total_too_complex,
                SUM(wrong_answer_count) as total_wrong
            ')
            ->groupBy('topic')
            ->havingRaw('SUM(total_questions) >= 10')
            ->orderByDesc('total_questions')
            ->limit(20)
            ->get()
            ->toArray();
    }
}
