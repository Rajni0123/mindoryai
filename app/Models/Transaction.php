<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'payment_gateway',
        'gateway_order_id',
        'gateway_payment_id',
        'amount',
        'currency',
        'status',
        'purpose',
        'metadata',
        'gateway_response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate unique transaction ID
     */
    public static function generateTransactionId(): string
    {
        return 'TXN_' . strtoupper(uniqid()) . '_' . time();
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(string $gatewayPaymentId, array $response = []): void
    {
        $this->update([
            'status' => 'completed',
            'gateway_payment_id' => $gatewayPaymentId,
            'gateway_response' => json_encode($response),
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(array $response = []): void
    {
        $this->update([
            'status' => 'failed',
            'gateway_response' => json_encode($response),
        ]);
    }
}
