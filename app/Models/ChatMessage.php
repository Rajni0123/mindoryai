<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'content',
        'image_url',
        'message_type',
        'tokens_charged',
        'is_read',
        'read_at',
        'typing_time_ms',
        'char_count',
        'was_pasted',
        'ai_detection_score',
        'flagged_for_review',
    ];

    protected $casts = [
        'tokens_charged' => 'integer',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'typing_time_ms' => 'integer',
        'char_count' => 'integer',
        'was_pasted' => 'boolean',
        'ai_detection_score' => 'decimal:2',
        'flagged_for_review' => 'boolean',
    ];

    // Message types
    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_LATEX = 'latex';
    const TYPE_VOICE = 'voice';

    // Sender types
    const SENDER_STUDENT = 'student';
    const SENDER_TOPPER = 'topper';

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function qualityLog(): HasOne
    {
        return $this->hasOne(AnswerQualityLog::class, 'message_id');
    }

    public function cheatAttempts()
    {
        return $this->hasMany(CheatAttempt::class, 'message_id');
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Flag message for review
     */
    public function flagForReview(float $aiScore = null): bool
    {
        return $this->update([
            'flagged_for_review' => true,
            'ai_detection_score' => $aiScore,
        ]);
    }

    /**
     * Check if message appears to be pasted
     */
    public function detectPaste(): bool
    {
        // If typing time is suspiciously low for the character count
        if ($this->typing_time_ms && $this->char_count) {
            $expectedMinTime = $this->char_count * 50; // 50ms per character is very fast typing

            if ($this->typing_time_ms < $expectedMinTime * 0.3) {
                return true;
            }
        }

        return $this->was_pasted;
    }

    /**
     * Calculate words per minute based on typing time
     */
    public function getTypingSpeedWpmAttribute(): ?int
    {
        if (!$this->typing_time_ms || !$this->char_count) {
            return null;
        }

        // Assuming average word length of 5 characters
        $wordCount = $this->char_count / 5;
        $minutesTaken = $this->typing_time_ms / 60000;

        if ($minutesTaken <= 0) {
            return null;
        }

        return (int) round($wordCount / $minutesTaken);
    }

    /**
     * Check if typing speed is suspicious (too fast for human)
     */
    public function hasAnomalousTypingSpeed(): bool
    {
        $wpm = $this->typing_speed_wpm;

        if ($wpm === null) {
            return false;
        }

        // Average typing speed is 40 WPM, professional is 75 WPM
        // Anything above 150 WPM is suspicious
        return $wpm > 150;
    }

    /**
     * Scope for unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for flagged messages
     */
    public function scopeFlagged($query)
    {
        return $query->where('flagged_for_review', true);
    }

    /**
     * Scope for messages from topper
     */
    public function scopeFromTopper($query)
    {
        return $query->where('sender_type', self::SENDER_TOPPER);
    }

    /**
     * Scope for messages from student
     */
    public function scopeFromStudent($query)
    {
        return $query->where('sender_type', self::SENDER_STUDENT);
    }
}
