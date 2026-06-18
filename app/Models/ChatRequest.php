<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'topper_id',
        'subject',
        'doubt_preview',
        'student_token_balance',
        'status',
        'expires_at',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'student_token_balance' => 'integer',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function topper()
    {
        return $this->belongsTo(User::class, 'topper_id');
    }

    public function conversation()
    {
        return $this->hasOne(ChatConversation::class, 'chat_request_id');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                     ->where('expires_at', '>', now());
    }

    /**
     * Scope for a specific topper, ordered by student token balance (priority inbox)
     */
    public function scopeForTopper($query, int $topperId)
    {
        return $query->where('topper_id', $topperId)
                     ->orderByDesc('student_token_balance');
    }

    /**
     * Accept the request and create conversation
     */
    public function accept(): ?ChatConversation
    {
        if ($this->status !== self::STATUS_PENDING) {
            return null;
        }

        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        return ChatConversation::create([
            'chat_request_id' => $this->id,
            'student_id' => $this->student_id,
            'topper_id' => $this->topper_id,
            'subject' => $this->subject,
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /**
     * Reject the request
     */
    public function reject(?string $reason = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Cancel the request (by student)
     */
    public function cancel(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Check if request is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Mark expired requests
     */
    public static function markExpired(): int
    {
        return static::where('status', self::STATUS_PENDING)
                     ->where('expires_at', '<=', now())
                     ->update(['status' => self::STATUS_EXPIRED]);
    }
}
