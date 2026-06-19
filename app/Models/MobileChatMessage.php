<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileChatMessage extends Model
{
    protected $fillable = [
        'mobile_chat_id',
        'sender',
        'content',
        'image',
        'image_url',
        'is_image_generation',
        'metadata',
    ];

    protected $casts = [
        'image' => 'array',
        'metadata' => 'array',
        'is_image_generation' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the chat that owns the message.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(MobileChat::class, 'mobile_chat_id');
    }
}
