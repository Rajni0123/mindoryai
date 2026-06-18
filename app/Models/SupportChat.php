<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'priority',
        'last_message_at',
        'last_admin_reply_at',
        'assigned_admin_id',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'last_admin_reply_at' => 'datetime',
    ];

    /**
     * Get the user who owns this chat
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the assigned admin
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    /**
     * Get all messages in this chat
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get unread messages count for admin
     */
    public function unreadMessagesCount(): int
    {
        return $this->messages()
            ->where('sender_type', 'user')
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get unread messages count for user
     */
    public function unreadUserMessagesCount(): int
    {
        return $this->messages()
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get or create support chat for a user
     */
    public static function getOrCreateForUser(User $user): self
    {
        $chat = self::where('user_id', $user->id)
            ->where('status', '!=', 'closed')
            ->first();

        if (!$chat) {
            $chat = self::create([
                'user_id' => $user->id,
                'status' => 'open',
                'priority' => 'high', // Ultimate users get high priority
            ]);
        }

        return $chat;
    }

    /**
     * Scope for open chats
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope for pending chats (awaiting admin reply)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope ordered by priority and last message
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal')")
            ->orderBy('last_message_at', 'desc');
    }
}
