<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_request_id',
        'student_id',
        'topper_id',
        'subject',
        'status',
        'tokens_used',
        'message_count',
        'started_at',
        'ended_at',
        'student_rating',
        'student_feedback',
        'is_resolved',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'message_count' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'student_rating' => 'integer',
        'is_resolved' => 'boolean',
    ];

    // Statuses
    const STATUS_ACTIVE = 'active';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';
    const STATUS_DISPUTED = 'disputed';

    public function chatRequest()
    {
        return $this->belongsTo(ChatRequest::class, 'chat_request_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function topper()
    {
        return $this->belongsTo(User::class, 'topper_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function earnings()
    {
        return $this->hasOne(TopperEarning::class, 'conversation_id');
    }

    public function publicDoubt()
    {
        return $this->hasOne(PublicDoubt::class, 'conversation_id');
    }

    /**
     * Scope for active conversations
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for a specific user (student or topper)
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('student_id', $userId)
              ->orWhere('topper_id', $userId);
        });
    }

    /**
     * Add message to conversation
     */
    public function addMessage(int $senderId, string $senderType, string $content, array $metadata = []): ChatMessage
    {
        $tokensCharged = 0;

        // Only charge tokens for student messages
        if ($senderType === 'student') {
            $tokensCharged = $this->calculateTokensForMessage($content);
            $this->increment('tokens_used', $tokensCharged);
        }

        $this->increment('message_count');

        return ChatMessage::create([
            'conversation_id' => $this->id,
            'sender_id' => $senderId,
            'sender_type' => $senderType,
            'content' => $content,
            'message_type' => $metadata['type'] ?? 'text',
            'image_url' => $metadata['image_url'] ?? null,
            'tokens_charged' => $tokensCharged,
            'typing_time_ms' => $metadata['typing_time_ms'] ?? null,
            'char_count' => strlen($content),
            'was_pasted' => $metadata['was_pasted'] ?? false,
        ]);
    }

    /**
     * Calculate tokens for a message based on length
     * 1 token per 100 characters, minimum 1 token
     */
    private function calculateTokensForMessage(string $content): int
    {
        $length = strlen($content);
        return max(1, (int) ceil($length / 100));
    }

    /**
     * Mark conversation as resolved
     */
    public function markResolved(int $rating = null, string $feedback = null): bool
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'is_resolved' => true,
            'ended_at' => now(),
            'student_rating' => $rating,
            'student_feedback' => $feedback,
        ]);

        // Calculate and create topper earnings
        $this->createTopperEarnings();

        // Update topper rating
        if ($rating) {
            $this->updateTopperRating($rating);
        }

        return true;
    }

    /**
     * Create topper earnings record
     */
    private function createTopperEarnings(): void
    {
        $tokensReceived = $this->tokens_used;
        $grossAmount = $tokensReceived / 10; // 10 tokens = ₹1
        $platformFee = $grossAmount * 0.30; // 30% platform cut
        $netAmount = $grossAmount - $platformFee; // 70% to topper

        TopperEarning::create([
            'topper_id' => $this->topper_id,
            'conversation_id' => $this->id,
            'student_id' => $this->student_id,
            'tokens_received' => $tokensReceived,
            'gross_amount' => $grossAmount,
            'platform_fee' => $platformFee,
            'net_amount' => $netAmount,
            'status' => 'pending',
        ]);

        // Add to topper wallet
        $wallet = TopperWallet::firstOrCreate(['user_id' => $this->topper_id]);
        $wallet->addEarnings($netAmount);
    }

    /**
     * Update topper's overall rating
     */
    private function updateTopperRating(int $newRating): void
    {
        $topper = $this->topper;
        $totalChats = $topper->topper_total_chats + 1;

        // Calculate new average rating
        $currentRating = $topper->topper_rating;
        $newAverage = (($currentRating * ($totalChats - 1)) + $newRating) / $totalChats;

        $topper->update([
            'topper_rating' => round($newAverage, 2),
            'topper_total_chats' => $totalChats,
        ]);
    }

    /**
     * Close conversation without resolution
     */
    public function close(): bool
    {
        return $this->update([
            'status' => self::STATUS_CLOSED,
            'ended_at' => now(),
        ]);
    }

    /**
     * Check if conversation is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->ended_at) {
            return null;
        }
        return $this->started_at->diffInMinutes($this->ended_at);
    }
}
