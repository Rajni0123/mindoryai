<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message Model
 *
 * Represents individual messages within a conversation
 * Each message has a role (user/assistant) and belongs to a conversation
 */
class Message extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tokens',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Relationship: A message belongs to a conversation
     *
     * @return BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Check if this message is from a user
     *
     * @return bool
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if this message is from an assistant
     *
     * @return bool
     */
    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }

    /**
     * Estimate token count for a message
     * Simple estimation: ~4 characters per token
     *
     * @param string $content
     * @return int
     */
    public static function estimateTokens(string $content): int
    {
        return (int) (strlen($content) / 4);
    }

    /**
     * Boot method to add event listeners
     * Updates conversation's last_message_at when message is created
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            // Update parent conversation's last message timestamp
            $message->conversation->touchLastMessage();
        });
    }
}
