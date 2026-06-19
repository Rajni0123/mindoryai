<?php

namespace App\Support;

use App\Models\MobileChat;
use App\Models\MobileChatMessage;
use App\Models\MockTest;
use App\Models\QuizAttempt;
use App\Models\QuizCache;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class ResourceAuthorizer
{
    public static function forbidden(string $message = 'You do not have permission to access this resource.'): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $message,
        ], 403));
    }

    public static function ensureAdmin(?User $user): void
    {
        if (!$user || $user->role !== 'admin') {
            self::forbidden('Admin access required.');
        }
    }

    public static function ownedMobileChat(User $user, int|string $chatId): MobileChat
    {
        $chat = MobileChat::where('id', $chatId)->where('user_id', $user->id)->first();

        if (!$chat) {
            self::forbidden('You do not have permission to access this chat.');
        }

        return $chat;
    }

    public static function ownedMobileChatMessage(User $user, int|string $chatId, int|string $messageId): MobileChatMessage
    {
        self::ownedMobileChat($user, $chatId);

        $message = MobileChatMessage::where('id', $messageId)
            ->where('mobile_chat_id', $chatId)
            ->first();

        if (!$message) {
            self::forbidden('You do not have permission to access this message.');
        }

        return $message;
    }

    public static function ownedTransaction(User $user, string $transactionId): Transaction
    {
        $transaction = Transaction::where('transaction_id', $transactionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$transaction) {
            self::forbidden('You do not have permission to verify this payment.');
        }

        return $transaction;
    }

    public static function ownedQuizCache(User $user, int $cacheId): QuizCache
    {
        $cache = QuizCache::where('id', $cacheId)->where('user_id', $user->id)->first();

        if (!$cache) {
            self::forbidden('You do not have permission to access this quiz.');
        }

        return $cache;
    }

    public static function ownedMockTest(User $user, int $mockTestId): MockTest
    {
        $mockTest = MockTest::where('id', $mockTestId)->where('user_id', $user->id)->first();

        if (!$mockTest) {
            self::forbidden('You do not have permission to access this mock test.');
        }

        return $mockTest;
    }

    public static function ownedQuizAttempt(User $user, int $attemptId): QuizAttempt
    {
        $attempt = QuizAttempt::where('id', $attemptId)->where('user_id', $user->id)->first();

        if (!$attempt) {
            self::forbidden('You do not have permission to access this quiz attempt.');
        }

        return $attempt;
    }

    /**
     * @return object Payment order row from payment_orders table
     */
    public static function ownedPaymentOrder(User $user, string $orderId): object
    {
        $order = DB::table('payment_orders')
            ->where('order_id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            self::forbidden('You do not have permission to verify this payment.');
        }

        return $order;
    }

    public static function ownedGatewayTransaction(User $user, string $gatewayOrderId): Transaction
    {
        $transaction = Transaction::where('gateway_order_id', $gatewayOrderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$transaction) {
            self::forbidden('You do not have permission to verify this payment.');
        }

        return $transaction;
    }
}
