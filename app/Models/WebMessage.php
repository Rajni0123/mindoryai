<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tokens_used',
        'attachments',
        'metadata',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the conversation that owns the message
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WebConversation::class, 'conversation_id');
    }

    /**
     * Scope for user messages
     */
    public function scopeUserMessages($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope for assistant messages
     */
    public function scopeAssistantMessages($query)
    {
        return $query->where('role', 'assistant');
    }
}
