<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'topper_id',
        'amount',
        'status',
        'payout_method',
        'payout_details',
        'transaction_reference',
        'rejection_reason',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';

    // Payout methods
    const METHOD_UPI = 'upi';
    const METHOD_BANK = 'bank_transfer';

    public function topper()
    {
        return $this->belongsTo(User::class, 'topper_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope for pending requests
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
     * Approve and mark as processing
     */
    public function markProcessing(int $adminId): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
            'processed_by' => $adminId,
        ]);
    }

    /**
     * Complete the withdrawal
     */
    public function complete(string $transactionReference): bool
    {
        $result = $this->update([
            'status' => self::STATUS_COMPLETED,
            'transaction_reference' => $transactionReference,
            'processed_at' => now(),
        ]);

        if ($result) {
            // Update topper wallet
            $wallet = TopperWallet::where('user_id', $this->topper_id)->first();
            if ($wallet) {
                $wallet->completeWithdrawal($this->amount);
            }
        }

        return $result;
    }

    /**
     * Reject the withdrawal
     */
    public function reject(string $reason, int $adminId): bool
    {
        $result = $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'processed_by' => $adminId,
            'processed_at' => now(),
        ]);

        if ($result) {
            // Return amount to topper wallet
            $wallet = TopperWallet::where('user_id', $this->topper_id)->first();
            if ($wallet) {
                $wallet->cancelWithdrawal($this->amount);
            }
        }

        return $result;
    }

    /**
     * Get formatted payout details
     */
    public function getFormattedPayoutDetailsAttribute(): string
    {
        if ($this->payout_method === self::METHOD_UPI) {
            return 'UPI: ' . $this->payout_details;
        }

        $details = json_decode($this->payout_details, true);
        if (!$details) {
            return $this->payout_details;
        }

        return sprintf(
            'Bank: %s | A/C: ****%s | IFSC: %s',
            $details['bank'] ?? 'N/A',
            substr($details['account'] ?? '', -4),
            $details['ifsc'] ?? 'N/A'
        );
    }
}
