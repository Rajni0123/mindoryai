<?php

namespace App\Services;

use App\Models\PaymentGateway;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Create payment order
     */
    public function createOrder(string $gateway, float $amount, string $purpose, int $userId, array $metadata = []): array
    {
        $paymentGateway = PaymentGateway::where('name', $gateway)
            ->where('is_enabled', true)
            ->first();

        if (!$paymentGateway || !$paymentGateway->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Payment gateway not available or not configured.'
            ];
        }

        // Create transaction record
        $transaction = Transaction::create([
            'user_id' => $userId,
            'transaction_id' => Transaction::generateTransactionId(),
            'payment_gateway' => $gateway,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => 'pending',
            'purpose' => $purpose,
            'metadata' => $metadata,
        ]);

        try {
            // Check if mock mode (test mode without real API credentials)
            if ($paymentGateway->isMockMode()) {
                $order = $this->createMockOrder($paymentGateway, $transaction);
            } else {
                $order = match ($gateway) {
                    'razorpay' => $this->createRazorpayOrder($paymentGateway, $transaction),
                    'cashfree' => $this->createCashfreeOrder($paymentGateway, $transaction),
                    'phonepe' => $this->createPhonepeOrder($paymentGateway, $transaction),
                    default => throw new \Exception('Unsupported payment gateway'),
                };
            }

            return [
                'success' => true,
                'transaction_id' => $transaction->transaction_id,
                'gateway_order_id' => $order['order_id'],
                'gateway_data' => $order,
            ];
        } catch (\Exception $e) {
            Log::error('Payment order creation failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            $transaction->markAsFailed(['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to create payment order: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment
     */
    public function verifyPayment(string $transactionId, array $paymentData): array
    {
        $transaction = Transaction::where('transaction_id', $transactionId)->first();

        if (!$transaction) {
            return ['success' => false, 'message' => 'Transaction not found'];
        }

        $paymentGateway = PaymentGateway::where('name', $transaction->payment_gateway)->first();

        try {
            // Check if mock mode (test mode without real API credentials)
            if ($paymentGateway->isMockMode()) {
                $verified = $this->verifyMockPayment($paymentGateway, $transaction, $paymentData);
            } else {
                $verified = match ($transaction->payment_gateway) {
                    'razorpay' => $this->verifyRazorpayPayment($paymentGateway, $transaction, $paymentData),
                    'cashfree' => $this->verifyCashfreePayment($paymentGateway, $transaction, $paymentData),
                    'phonepe' => $this->verifyPhonepePayment($paymentGateway, $transaction, $paymentData),
                    default => false,
                };
            }

            if ($verified) {
                $transaction->markAsCompleted($paymentData['payment_id'] ?? '', $paymentData);
                return ['success' => true, 'message' => 'Payment verified successfully'];
            }

            return ['success' => false, 'message' => 'Payment verification failed'];
        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Verification error: ' . $e->getMessage()];
        }
    }

    /**
     * Razorpay - Create Order
     */
    protected function createRazorpayOrder(PaymentGateway $gateway, Transaction $transaction): array
    {
        $response = Http::withBasicAuth($gateway->api_key, $gateway->api_secret)
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $transaction->amount * 100, // Convert to paise
                'currency' => $transaction->currency,
                'receipt' => $transaction->transaction_id,
            ]);

        $data = $response->json();

        $transaction->update(['gateway_order_id' => $data['id']]);

        return [
            'order_id' => $data['id'],
            'key' => $gateway->api_key,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'name' => config('app.name'),
        ];
    }

    /**
     * Razorpay - Verify Payment
     */
    protected function verifyRazorpayPayment(PaymentGateway $gateway, Transaction $transaction, array $paymentData): bool
    {
        $signature = hash_hmac(
            'sha256',
            $transaction->gateway_order_id . '|' . $paymentData['razorpay_payment_id'],
            $gateway->api_secret
        );

        return $signature === $paymentData['razorpay_signature'];
    }

    /**
     * Cashfree - Create Order
     */
    protected function createCashfreeOrder(PaymentGateway $gateway, Transaction $transaction): array
    {
        $baseUrl = $gateway->is_test_mode
            ? 'https://sandbox.cashfree.com/pg'
            : 'https://api.cashfree.com/pg';

        $response = Http::withHeaders([
            'x-client-id' => $gateway->api_key,
            'x-client-secret' => $gateway->api_secret,
            'x-api-version' => '2023-08-01',
        ])->post($baseUrl . '/orders', [
            'order_id' => $transaction->transaction_id,
            'order_amount' => $transaction->amount,
            'order_currency' => $transaction->currency,
            'customer_details' => [
                'customer_id' => 'user_' . $transaction->user_id,
                'customer_phone' => '9999999999',
            ],
        ]);

        $data = $response->json();

        $transaction->update(['gateway_order_id' => $data['order_id']]);

        return [
            'order_id' => $data['order_id'],
            'payment_session_id' => $data['payment_session_id'],
            'order_token' => $data['order_token'] ?? null,
        ];
    }

    /**
     * Cashfree - Verify Payment
     */
    protected function verifyCashfreePayment(PaymentGateway $gateway, Transaction $transaction, array $paymentData): bool
    {
        $baseUrl = $gateway->is_test_mode
            ? 'https://sandbox.cashfree.com/pg'
            : 'https://api.cashfree.com/pg';

        $response = Http::withHeaders([
            'x-client-id' => $gateway->api_key,
            'x-client-secret' => $gateway->api_secret,
            'x-api-version' => '2023-08-01',
        ])->get($baseUrl . '/orders/' . $transaction->gateway_order_id);

        $data = $response->json();

        return isset($data['order_status']) && $data['order_status'] === 'PAID';
    }

    /**
     * PhonePe - Create Order
     */
    protected function createPhonepeOrder(PaymentGateway $gateway, Transaction $transaction): array
    {
        $baseUrl = $gateway->is_test_mode
            ? 'https://api-preprod.phonepe.com/apis/pg-sandbox'
            : 'https://api.phonepe.com/apis/hermes';

        $payload = [
            'merchantId' => $gateway->merchant_id,
            'merchantTransactionId' => $transaction->transaction_id,
            'merchantUserId' => 'user_' . $transaction->user_id,
            'amount' => $transaction->amount * 100, // Convert to paise
            'redirectUrl' => url('/payment/callback/phonepe'),
            'redirectMode' => 'POST',
            'callbackUrl' => url('/payment/webhook/phonepe'),
            'paymentInstrument' => [
                'type' => 'PAY_PAGE'
            ],
        ];

        $base64Payload = base64_encode(json_encode($payload));
        $checksum = hash('sha256', $base64Payload . '/pg/v1/pay' . $gateway->api_secret) . '###1';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-VERIFY' => $checksum,
        ])->post($baseUrl . '/pg/v1/pay', [
            'request' => $base64Payload,
        ]);

        $data = $response->json();

        if (isset($data['data']['instrumentResponse']['redirectInfo']['url'])) {
            return [
                'order_id' => $transaction->transaction_id,
                'payment_url' => $data['data']['instrumentResponse']['redirectInfo']['url'],
            ];
        }

        throw new \Exception('PhonePe order creation failed');
    }

    /**
     * PhonePe - Verify Payment
     */
    protected function verifyPhonepePayment(PaymentGateway $gateway, Transaction $transaction, array $paymentData): bool
    {
        $baseUrl = $gateway->is_test_mode
            ? 'https://api-preprod.phonepe.com/apis/pg-sandbox'
            : 'https://api.phonepe.com/apis/hermes';

        $checksum = hash('sha256', '/pg/v1/status/' . $gateway->merchant_id . '/' . $transaction->transaction_id . $gateway->api_secret) . '###1';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-VERIFY' => $checksum,
            'X-MERCHANT-ID' => $gateway->merchant_id,
        ])->get($baseUrl . '/pg/v1/status/' . $gateway->merchant_id . '/' . $transaction->transaction_id);

        $data = $response->json();

        return isset($data['code']) && $data['code'] === 'PAYMENT_SUCCESS';
    }

    /**
     * Mock Order - For testing without real API credentials
     * This will be bypassed when real API keys are added
     */
    protected function createMockOrder(PaymentGateway $gateway, Transaction $transaction): array
    {
        $mockOrderId = 'MOCK_' . strtoupper($gateway->name) . '_' . time() . '_' . rand(1000, 9999);

        $transaction->update(['gateway_order_id' => $mockOrderId]);

        Log::info('Mock payment order created', [
            'gateway' => $gateway->name,
            'order_id' => $mockOrderId,
            'amount' => $transaction->amount,
            'transaction_id' => $transaction->transaction_id,
        ]);

        return [
            'order_id' => $mockOrderId,
            'key' => 'mock_test_key',
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'name' => config('app.name'),
            'is_mock' => true,
            'mock_message' => 'Test mode - No real payment will be processed',
            // For PhonePe style response
            'payment_url' => url('/payment/mock-checkout?order_id=' . $mockOrderId . '&amount=' . $transaction->amount),
            // For Cashfree style response
            'payment_session_id' => 'mock_session_' . $mockOrderId,
        ];
    }

    /**
     * Mock Verify - For testing without real API credentials
     * Always returns true in mock mode for testing
     */
    protected function verifyMockPayment(PaymentGateway $gateway, Transaction $transaction, array $paymentData): bool
    {
        Log::info('Mock payment verification', [
            'gateway' => $gateway->name,
            'transaction_id' => $transaction->transaction_id,
            'payment_data' => $paymentData,
        ]);

        // In mock mode, always verify successfully for testing
        return true;
    }
}
