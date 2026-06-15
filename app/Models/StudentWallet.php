<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token_balance',
        'total_tokens_purchased',
        'total_tokens_spent',
    ];

    protected $casts = [
        'token_balance' => 'integer',
        'total_tokens_purchased' => 'integer',
        'total_tokens_spent' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(TokenTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Add tokens to wallet
     */
    public function addTokens(int $amount, string $description, ?string $razorpayPaymentId = null, ?float $inrAmount = null): TokenTransaction
    {
        $this->increment('token_balance', $amount);
        $this->increment('total_tokens_purchased', $amount);

        return TokenTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'purchase',
            'amount' => $amount,
            'balance_after' => $this->token_balance,
            'description' => $description,
            'razorpay_payment_id' => $razorpayPaymentId,
            'inr_amount' => $inrAmount,
        ]);
    }

    /**
     * Spend tokens from wallet
     */
    public function spendTokens(int $amount, string $description, ?string $referenceType = null, ?int $referenceId = null): ?TokenTransaction
    {
        if ($this->token_balance < $amount) {
            return null;
        }

        $this->decrement('token_balance', $amount);
        $this->increment('total_tokens_spent', $amount);

        return TokenTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'spend',
            'amount' => -$amount,
            'balance_after' => $this->token_balance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    /**
     * Refund tokens to wallet
     */
    public function refundTokens(int $amount, string $description, ?string $referenceType = null, ?int $referenceId = null): TokenTransaction
    {
        $this->increment('token_balance', $amount);
        $this->decrement('total_tokens_spent', $amount);

        return TokenTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'refund',
            'amount' => $amount,
            'balance_after' => $this->token_balance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    /**
     * Check if wallet has sufficient balance
     */
    public function hasSufficientBalance(int $amount): bool
    {
        return $this->token_balance >= $amount;
    }
}
