<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Conversation Model
 *
 * Represents a chat conversation thread (like ChatGPT conversations)
 * Each conversation belongs to a user and contains multiple messages
 */
class Conversation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'ai_model_id',
        'last_message_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Relationship: A conversation belongs to a user
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A conversation has many messages
     * Ordered by creation time (oldest first)
     *
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Relationship: A conversation uses an AI model
     *
     * @return BelongsTo
     */
    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class);
    }

    /**
     * Generate a title from the first user message
     * Used when creating new conversations
     *
     * @param string $firstMessage
     * @return string
     */
    public static function generateTitle(string $firstMessage): string
    {
        // Take first 50 characters and add ellipsis if longer
        $title = substr($firstMessage, 0, 50);

        if (strlen($firstMessage) > 50) {
            $title .= '...';
        }

        return $title;
    }

    /**
     * Get all messages formatted for OpenAI API
     * Returns array in format: [{ role: 'user', content: '...' }, ...]
     *
     * @return array
     */
    public function getMessagesForAPI(): array
    {
        return $this->messages()
            ->get()
            ->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                ];
            })
            ->toArray();
    }

    /**
     * Update last_message_at timestamp
     * Called whenever a new message is added
     *
     * @return void
     */
    public function touchLastMessage(): void
    {
        $this->update([
            'last_message_at' => now(),
        ]);
    }

    /**
     * Scope: Get user's conversations ordered by recent activity
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId)
                     ->orderByRaw('COALESCE(last_message_at, created_at) DESC');
    }
}
