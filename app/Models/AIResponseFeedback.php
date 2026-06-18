<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIResponseFeedback extends Model
{
    protected $table = 'ai_response_feedback';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'question',
        'ai_response',
        'feedback_type',
        'feedback_reason',
        'user_comment',
        'response_rating',
        'was_helpful',
        'topic',
        'subject',
    ];

    protected $casts = [
        'was_helpful' => 'boolean',
        'response_rating' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for positive feedback
     */
    public function scopePositive($query)
    {
        return $query->where('feedback_type', 'like');
    }

    /**
     * Scope for negative feedback
     */
    public function scopeNegative($query)
    {
        return $query->where('feedback_type', 'dislike');
    }

    /**
     * Scope for recent feedback
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for user feedback
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get common feedback reasons
     */
    public static function getCommonReasons(int $days = 30, int $limit = 10): array
    {
        return self::whereNotNull('feedback_reason')
            ->where('feedback_type', 'dislike')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('feedback_reason, COUNT(*) as count')
            ->groupBy('feedback_reason')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('count', 'feedback_reason')
            ->toArray();
    }

    /**
     * Get satisfaction rate for a period
     */
    public static function getSatisfactionRate(int $days = 30): float
    {
        $total = self::where('created_at', '>=', now()->subDays($days))->count();
        if ($total === 0) return 0.5;

        $positive = self::where('created_at', '>=', now()->subDays($days))
            ->where('feedback_type', 'like')
            ->count();

        return round($positive / $total, 2);
    }
}
