<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopperWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'total_earned',
        'total_withdrawn',
        'pending_payout',
        'upi_id',
        'bank_account_number',
        'bank_ifsc',
        'bank_name',
        'pan_number',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'pending_payout' => 'decimal:2',
    ];

    protected $hidden = [
        'pan_number',
        'bank_account_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function earnings()
    {
        return $this->hasMany(TopperEarning::class, 'topper_id', 'user_id');
    }

    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class, 'topper_id', 'user_id');
    }

    /**
     * Add earnings to wallet (from completed chat)
     */
    public function addEarnings(float $amount): void
    {
        $this->increment('balance', $amount);
        $this->increment('total_earned', $amount);
    }

    /**
     * Request withdrawal
     */
    public function requestWithdrawal(float $amount, string $method = 'upi'): ?WithdrawalRequest
    {
        if ($this->balance < $amount) {
            return null;
        }

        $payoutDetails = $method === 'upi'
            ? $this->upi_id
            : json_encode([
                'account' => $this->bank_account_number,
                'ifsc' => $this->bank_ifsc,
                'bank' => $this->bank_name,
            ]);

        // Move to pending
        $this->decrement('balance', $amount);
        $this->increment('pending_payout', $amount);

        return WithdrawalRequest::create([
            'topper_id' => $this->user_id,
            'amount' => $amount,
            'status' => 'pending',
            'payout_method' => $method,
            'payout_details' => $payoutDetails,
        ]);
    }

    /**
     * Mark withdrawal as completed
     */
    public function completeWithdrawal(float $amount): void
    {
        $this->decrement('pending_payout', $amount);
        $this->increment('total_withdrawn', $amount);
    }

    /**
     * Cancel withdrawal (return to balance)
     */
    public function cancelWithdrawal(float $amount): void
    {
        $this->decrement('pending_payout', $amount);
        $this->increment('balance', $amount);
    }

    /**
     * Check if UPI is set up
     */
    public function hasUpiSetup(): bool
    {
        return !empty($this->upi_id);
    }

    /**
     * Check if bank is set up
     */
    public function hasBankSetup(): bool
    {
        return !empty($this->bank_account_number) && !empty($this->bank_ifsc);
    }

    /**
     * Get masked UPI ID
     */
    public function getMaskedUpiIdAttribute(): ?string
    {
        if (empty($this->upi_id)) {
            return null;
        }
        $parts = explode('@', $this->upi_id);
        if (count($parts) !== 2) {
            return '****@****';
        }
        return substr($parts[0], 0, 2) . '****@' . $parts[1];
    }

    /**
     * Get masked bank account
     */
    public function getMaskedBankAccountAttribute(): ?string
    {
        if (empty($this->bank_account_number)) {
            return null;
        }
        return '****' . substr($this->bank_account_number, -4);
    }
}
