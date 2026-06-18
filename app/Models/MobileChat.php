<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileChat extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'ai_model_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the chat.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages for the chat.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(MobileChatMessage::class, 'mobile_chat_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Generate title from first message (ChatGPT-style)
     * Uses first 3-6 meaningful words
     */
    public static function generateTitleFromMessage(string $message): string
    {
        // Remove extra whitespace and trim
        $message = trim(preg_replace('/\s+/', ' ', $message));

        // Remove common punctuation
        $message = str_replace(['?', '!', '.', ',', ';', ':'], '', $message);

        // Split into words
        $words = explode(' ', $message);

        // Take first 3-6 words (or fewer if message is shorter)
        $wordCount = min(6, count($words), max(3, count($words)));
        $titleWords = array_slice($words, 0, $wordCount);

        // Join with spaces and capitalize first letter
        $title = implode(' ', $titleWords);
        $title = ucfirst($title);

        // Fallback if empty
        if (empty($title)) {
            return 'New Chat';
        }

        // Truncate if too long (max 50 chars)
        if (strlen($title) > 50) {
            $title = substr($title, 0, 47) . '...';
        }

        return $title;
    }
}
