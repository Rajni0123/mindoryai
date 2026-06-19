<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TrialAutopayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrialController extends Controller
{
    public function __construct(
        protected TrialAutopayService $trialService
    ) {}

    /**
     * GET /trial/offer — eligibility + pricing copy for paywall UI.
     */
    public function offer(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            ...$this->trialService->getOffer($request->user()),
        ]);
    }

    /**
     * GET /trial/status — current trial / autopay state.
     */
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            ...$this->trialService->getStatus($request->user()),
        ]);
    }

    /**
     * POST /trial/start — create Razorpay subscription (₹1 + UPI autopay mandate).
     */
    public function start(Request $request): JsonResponse
    {
        try {
            $data = $this->trialService->startTrial($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Complete payment to start your ₹1 trial.',
                ...$data,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Trial start failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not start trial. Please try again.',
            ], 500);
        }
    }

    /**
     * POST /trial/verify — after Razorpay checkout success on mobile.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'razorpay_subscription_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        try {
            $status = $this->trialService->verifySubscriptionAuth(
                $request->user(),
                $validated['razorpay_subscription_id'],
                $validated['razorpay_payment_id'],
                $validated['razorpay_signature']
            );

            return response()->json([
                'success' => true,
                'message' => 'Trial activated! Enjoy Lite for ' . config('trial.days') . ' days.',
                'trial' => $status,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Trial verify failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed. If payment succeeded, access will activate shortly.',
            ], 500);
        }
    }

    /**
     * POST /trial/cancel — cancel UPI autopay mandate.
     */
    public function cancel(Request $request): JsonResponse
    {
        $cancelled = $this->trialService->cancelAutopay($request->user());

        if (! $cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'No active trial or autopay subscription found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Autopay cancelled. You can use remaining access until expiry.',
        ]);
    }
}
