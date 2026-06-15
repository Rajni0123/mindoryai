<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'razorpay_payment_id',
        'inr_amount',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'inr_amount' => 'decimal:2',
    ];

    // Transaction types
    const TYPE_PURCHASE = 'purchase';
    const TYPE_SPEND = 'spend';
    const TYPE_REFUND = 'refund';
    const TYPE_BONUS = 'bonus';
    const TYPE_EXPIRED = 'expired';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for purchases
     */
    public function scopePurchases($query)
    {
        return $query->where('type', self::TYPE_PURCHASE);
    }

    /**
     * Scope for spending
     */
    public function scopeSpending($query)
    {
        return $query->where('type', self::TYPE_SPEND);
    }

    /**
     * Scope for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Check if this is a credit (positive) transaction
     */
    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if this is a debit (negative) transaction
     */
    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Get formatted amount with sign
     */
    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->amount >= 0 ? '+' : '';
        return $sign . $this->amount . ' tokens';
    }
}
