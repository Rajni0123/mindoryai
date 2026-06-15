<?php

namespace App\Services;

use App\Models\StudentWallet;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TokenWalletService
{
    // Token packages (10 tokens = ₹1)
    const PACKAGES = [
        'starter' => ['tokens' => 100, 'price' => 10, 'label' => 'Starter Pack', 'popular' => false, 'discount_percent' => 0],
        'popular' => ['tokens' => 500, 'price' => 49, 'label' => 'Popular Pack', 'popular' => true, 'discount_percent' => 2],
        'value' => ['tokens' => 1000, 'price' => 99, 'label' => 'Value Pack', 'popular' => false, 'discount_percent' => 1],
    ];

    /**
     * Get or create wallet for user
     */
    public function getWallet(User $user): StudentWallet
    {
        return $user->studentWallet ?? StudentWallet::create(['user_id' => $user->id]);
    }

    /**
     * Get wallet balance
     */
    public function getBalance(User $user): int
    {
        return $this->getWallet($user)->token_balance;
    }

    /**
     * Purchase tokens
     */
    public function purchaseTokens(
        User $user,
        string $packageId,
        string $razorpayPaymentId
    ): ?TokenTransaction {
        if (!isset(self::PACKAGES[$packageId])) {
            Log::error('Invalid token package', ['package' => $packageId]);
            return null;
        }

        $package = self::PACKAGES[$packageId];

        try {
            return DB::transaction(function () use ($user, $package, $razorpayPaymentId) {
                $wallet = $this->getWallet($user);

                return $wallet->addTokens(
                    $package['tokens'],
                    "Purchased {$package['label']} ({$package['tokens']} tokens)",
                    $razorpayPaymentId,
                    $package['price']
                );
            });
        } catch (\Exception $e) {
            Log::error('Token purchase failed', [
                'user_id' => $user->id,
                'package' => $packageId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Add bonus tokens
     */
    public function addBonusTokens(User $user, int $amount, string $reason): ?TokenTransaction
    {
        try {
            return DB::transaction(function () use ($user, $amount, $reason) {
                $wallet = $this->getWallet($user);

                $wallet->increment('token_balance', $amount);
                $wallet->increment('total_tokens_purchased', $amount);

                return TokenTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'bonus',
                    'amount' => $amount,
                    'balance_after' => $wallet->token_balance,
                    'description' => "Bonus: {$reason}",
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Bonus token addition failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Spend tokens for chat
     */
    public function spendTokensForChat(
        User $user,
        int $amount,
        int $conversationId
    ): ?TokenTransaction {
        try {
            return DB::transaction(function () use ($user, $amount, $conversationId) {
                $wallet = $this->getWallet($user);

                if (!$wallet->hasSufficientBalance($amount)) {
                    return null;
                }

                return $wallet->spendTokens(
                    $amount,
                    "Chat message in conversation #{$conversationId}",
                    'conversation',
                    $conversationId
                );
            });
        } catch (\Exception $e) {
            Log::error('Token spending failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Refund tokens
     */
    public function refundTokens(
        User $user,
        int $amount,
        string $reason,
        ?int $conversationId = null
    ): ?TokenTransaction {
        try {
            return DB::transaction(function () use ($user, $amount, $reason, $conversationId) {
                $wallet = $this->getWallet($user);

                return $wallet->refundTokens(
                    $amount,
                    "Refund: {$reason}",
                    $conversationId ? 'conversation' : null,
                    $conversationId
                );
            });
        } catch (\Exception $e) {
            Log::error('Token refund failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if user has sufficient balance
     */
    public function hasSufficientBalance(User $user, int $amount): bool
    {
        return $this->getWallet($user)->hasSufficientBalance($amount);
    }

    /**
     * Get transaction history
     */
    public function getTransactionHistory(User $user, int $limit = 20): array
    {
        return TokenTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get wallet summary
     */
    public function getWalletSummary(User $user): array
    {
        $wallet = $this->getWallet($user);

        return [
            'token_balance' => $wallet->token_balance,
            'inr_equivalent' => round($wallet->token_balance / 10, 2),
            'total_purchased' => $wallet->total_tokens_purchased,
            'total_spent' => $wallet->total_tokens_spent,
            'packages' => $this->getPackages(),
        ];
    }

    /**
     * Get available packages
     */
    public function getPackages(): array
    {
        return array_map(function ($package, $id) {
            return [
                'id' => $id,
                'label' => $package['label'],
                'tokens' => $package['tokens'],
                'price' => $package['price'],
                'price_formatted' => '₹' . $package['price'],
                'per_token' => round($package['price'] / $package['tokens'], 3),
                'popular' => $package['popular'] ?? false,
                'discount_percent' => $package['discount_percent'] ?? 0,
            ];
        }, self::PACKAGES, array_keys(self::PACKAGES));
    }
}
