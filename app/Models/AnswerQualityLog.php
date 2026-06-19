<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerQualityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'topper_id',
        'ai_detection_score',
        'typing_speed_wpm',
        'seems_copied',
        'seems_ai_generated',
        'analysis_notes',
        'review_status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'ai_detection_score' => 'decimal:2',
        'typing_speed_wpm' => 'integer',
        'seems_copied' => 'boolean',
        'seems_ai_generated' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    // Review statuses
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_FLAGGED = 'flagged';
    const STATUS_BANNED = 'banned';

    public function message()
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }

    public function topper()
    {
        return $this->belongsTo(User::class, 'topper_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for pending reviews
     */
    public function scopePending($query)
    {
        return $query->where('review_status', self::STATUS_PENDING);
    }

    /**
     * Scope for flagged reviews
     */
    public function scopeFlagged($query)
    {
        return $query->where('review_status', self::STATUS_FLAGGED);
    }

    /**
     * Scope for high AI detection scores
     */
    public function scopeHighAiScore($query, float $threshold = 0.7)
    {
        return $query->where('ai_detection_score', '>=', $threshold);
    }

    /**
     * Approve the quality
     */
    public function approve(int $reviewerId): bool
    {
        return $this->update([
            'review_status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Flag for further review
     */
    public function flag(int $reviewerId, ?string $notes = null): bool
    {
        return $this->update([
            'review_status' => self::STATUS_FLAGGED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'analysis_notes' => $notes ?? $this->analysis_notes,
        ]);
    }

    /**
     * Ban (mark as violation)
     */
    public function ban(int $reviewerId, ?string $notes = null): bool
    {
        return $this->update([
            'review_status' => self::STATUS_BANNED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'analysis_notes' => $notes ?? $this->analysis_notes,
        ]);
    }

    /**
     * Create quality log for a message
     */
    public static function analyzeMessage(ChatMessage $message): self
    {
        $log = static::create([
            'message_id' => $message->id,
            'topper_id' => $message->sender_id,
            'ai_detection_score' => $message->ai_detection_score ?? 0,
            'typing_speed_wpm' => $message->typing_speed_wpm,
            'seems_copied' => $message->was_pasted || $message->detectPaste(),
            'seems_ai_generated' => ($message->ai_detection_score ?? 0) > 0.7,
            'review_status' => self::STATUS_PENDING,
        ]);

        // Auto-flag if suspicious
        if ($log->seems_copied || $log->seems_ai_generated || $log->typing_speed_wpm > 150) {
            $log->update(['review_status' => self::STATUS_FLAGGED]);
            $message->flagForReview($message->ai_detection_score);
        }

        return $log;
    }
}
