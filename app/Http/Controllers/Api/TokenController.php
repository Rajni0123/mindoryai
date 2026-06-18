<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TokenController extends Controller
{
    /**
     * Get all available token packs
     */
    public function getTokenPacks(): JsonResponse
    {
        $packs = DB::table('token_packs')
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(function ($pack) {
                return [
                    'id' => $pack->id,
                    'name' => $pack->name,
                    'slug' => $pack->slug,
                    'tokens' => $pack->tokens,
                    'tokens_formatted' => $this->formatTokens($pack->tokens),
                    'price' => (float) $pack->price,
                    'original_price' => $pack->original_price ? (float) $pack->original_price : null,
                    'discount_percent' => $pack->original_price
                        ? round((1 - $pack->price / $pack->original_price) * 100)
                        : null,
                    'badge' => $pack->badge,
                    'description' => $pack->description,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $packs,
        ]);
    }

    /**
     * Get user's token balance and history
     */
    public function getBalance(Request $request): JsonResponse
    {
        $user = $request->user();

        $balance = $user->token_balance ?? 0;

        // Get recent transactions
        $transactions = DB::table('token_transactions')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'tokens' => $t->tokens,
                    'balance_after' => $t->balance_after,
                    'description' => $t->description,
                    'created_at' => $t->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'balance_formatted' => $this->formatTokens($balance),
                'transactions' => $transactions,
            ],
        ]);
    }

    /**
     * Create order for token purchase (Razorpay)
     */
    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'pack_id' => 'required|exists:token_packs,id',
        ]);

        $user = $request->user();
        $pack = DB::table('token_packs')->where('id', $request->pack_id)->first();

        if (!$pack || !$pack->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Token pack not available.',
            ], 400);
        }

        try {
            // Create Razorpay order
            $razorpayKey = config('services.razorpay.key');
            $razorpaySecret = config('services.razorpay.secret');

            if (!$razorpayKey || !$razorpaySecret) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment gateway not configured.',
                ], 500);
            }

            $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);

            $orderData = [
                'receipt' => 'token_' . $user->id . '_' . time(),
                'amount' => $pack->price * 100, // Amount in paise
                'currency' => 'INR',
                'notes' => [
                    'user_id' => $user->id,
                    'pack_id' => $pack->id,
                    'pack_name' => $pack->name,
                    'tokens' => $pack->tokens,
                ],
            ];

            $razorpayOrder = $api->order->create($orderData);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $razorpayOrder->id,
                    'amount' => $pack->price,
                    'currency' => 'INR',
                    'pack' => [
                        'id' => $pack->id,
                        'name' => $pack->name,
                        'tokens' => $pack->tokens,
                        'tokens_formatted' => $this->formatTokens($pack->tokens),
                    ],
                    'razorpay_key' => $razorpayKey,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Token order creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify payment and credit tokens
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'pack_id' => 'required|exists:token_packs,id',
        ]);

        $user = $request->user();

        try {
            $razorpayKey = config('services.razorpay.key');
            $razorpaySecret = config('services.razorpay.secret');

            $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);

            // Verify signature
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ];

            $api->utility->verifyPaymentSignature($attributes);

            // Get pack details
            $pack = DB::table('token_packs')->where('id', $request->pack_id)->first();

            if (!$pack) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token pack.',
                ], 400);
            }

            // Credit tokens to user
            DB::beginTransaction();

            $newBalance = $user->token_balance + $pack->tokens;

            // Update user balance
            DB::table('users')
                ->where('id', $user->id)
                ->update(['token_balance' => $newBalance]);

            // Record transaction
            DB::table('token_transactions')->insert([
                'user_id' => $user->id,
                'type' => 'purchase',
                'tokens' => $pack->tokens,
                'balance_after' => $newBalance,
                'description' => "Purchased {$pack->name}",
                'payment_id' => $request->razorpay_payment_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            Log::info('Token purchase successful', [
                'user_id' => $user->id,
                'pack' => $pack->name,
                'tokens' => $pack->tokens,
                'payment_id' => $request->razorpay_payment_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tokens credited successfully!',
                'data' => [
                    'tokens_added' => $pack->tokens,
                    'new_balance' => $newBalance,
                    'new_balance_formatted' => $this->formatTokens($newBalance),
                ],
            ]);
        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            Log::error('Token payment verification failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed.',
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Token purchase failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment. Please contact support.',
            ], 500);
        }
    }

    /**
     * Deduct tokens for feature usage (internal use)
     */
    public static function deductTokens(int $userId, int $tokens, string $feature, ?int $referenceId = null): bool
    {
        try {
            $user = DB::table('users')->where('id', $userId)->first();

            if (!$user || $user->token_balance < $tokens) {
                return false;
            }

            $newBalance = $user->token_balance - $tokens;

            DB::beginTransaction();

            DB::table('users')
                ->where('id', $userId)
                ->update(['token_balance' => $newBalance]);

            DB::table('token_transactions')->insert([
                'user_id' => $userId,
                'type' => 'usage',
                'tokens' => -$tokens,
                'balance_after' => $newBalance,
                'description' => "Used for {$feature}",
                'reference_type' => $feature,
                'reference_id' => $referenceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Token deduction failed', [
                'user_id' => $userId,
                'tokens' => $tokens,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if user has enough tokens
     */
    public static function hasEnoughTokens(int $userId, string $feature): array
    {
        $user = DB::table('users')->where('id', $userId)->first();
        $balance = $user->token_balance ?? 0;

        $costKey = "token_cost_{$feature}";
        $cost = (int) DB::table('settings')->where('key', $costKey)->value('value') ?? 100;

        return [
            'has_enough' => $balance >= $cost,
            'balance' => $balance,
            'cost' => $cost,
            'shortfall' => max(0, $cost - $balance),
        ];
    }

    /**
     * Format tokens for display (500K, 1M, etc.)
     */
    private function formatTokens(int $tokens): string
    {
        if ($tokens >= 1000000) {
            return round($tokens / 1000000, 1) . 'M';
        } elseif ($tokens >= 1000) {
            return round($tokens / 1000) . 'K';
        }
        return (string) $tokens;
    }
}
