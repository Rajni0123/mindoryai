<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebConversation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'is_pinned',
        'is_archived',
        'tags',
        'settings',
        'message_count',
        'last_message_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_archived' => 'boolean',
        'tags' => 'array',
        'settings' => 'array',
        'last_message_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the conversation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages for the conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(WebMessage::class, 'conversation_id');
    }

    /**
     * Scope for non-deleted conversations
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope for non-archived conversations
     */
    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope for pinned conversations
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }
}
