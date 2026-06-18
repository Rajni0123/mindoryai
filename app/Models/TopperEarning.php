<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopperEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'topper_id',
        'conversation_id',
        'student_id',
        'tokens_received',
        'gross_amount',
        'platform_fee',
        'net_amount',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'tokens_received' => 'integer',
        'gross_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_PAID = 'paid';
    const STATUS_DISPUTED = 'disputed';

    public function topper()
    {
        return $this->belongsTo(User::class, 'topper_id');
    }

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Scope for pending earnings
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for a specific topper
     */
    public function scopeForTopper($query, int $topperId)
    {
        return $query->where('topper_id', $topperId);
    }

    /**
     * Mark as processed
     */
    public function markProcessed(): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as paid
     */
    public function markPaid(): bool
    {
        return $this->update([
            'status' => self::STATUS_PAID,
        ]);
    }

    /**
     * Mark as disputed
     */
    public function markDisputed(): bool
    {
        return $this->update([
            'status' => self::STATUS_DISPUTED,
        ]);
    }

    /**
     * Get earnings summary for topper
     */
    public static function getSummaryForTopper(int $topperId): array
    {
        $earnings = static::forTopper($topperId);

        return [
            'total_earned' => $earnings->sum('net_amount'),
            'pending_amount' => $earnings->pending()->sum('net_amount'),
            'total_conversations' => $earnings->count(),
            'total_tokens' => $earnings->sum('tokens_received'),
            'this_month' => $earnings->whereMonth('created_at', now()->month)
                                     ->whereYear('created_at', now()->year)
                                     ->sum('net_amount'),
        ];
    }
}
