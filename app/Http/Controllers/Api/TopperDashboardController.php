<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TopperEarning;
use App\Models\TopperWallet;
use App\Models\WithdrawalRequest;
use App\Services\TopperConnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TopperDashboardController extends Controller
{
    protected TopperConnectService $topperService;

    public function __construct(TopperConnectService $topperService)
    {
        $this->topperService = $topperService;
    }

    /**
     * Get topper dashboard data
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isVerifiedTopper()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized as topper',
                ], 403);
            }

            $wallet = $user->getOrCreateTopperWallet();
            $pendingRequests = $this->topperService->getPendingRequestsForTopper($user);
            $conversations = $this->topperService->getTopperConversations($user);

            // Get earnings summary
            $thisMonth = TopperEarning::forTopper($user->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('net_amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'profile' => [
                        'name' => $user->name,
                        'exam' => $user->topper_exam,
                        'rank' => $user->topper_rank,
                        'rating' => $user->topper_rating,
                        'total_chats' => $user->topper_total_chats,
                        'is_available' => $user->topper_available,
                    ],
                    'wallet' => [
                        'balance' => $wallet->balance,
                        'pending_payout' => $wallet->pending_payout,
                        'total_earned' => $wallet->total_earned,
                        'total_withdrawn' => $wallet->total_withdrawn,
                        'this_month' => $thisMonth,
                    ],
                    'pending_requests' => $pendingRequests,
                    'active_conversations' => $conversations->where('status', 'active'),
                    'recent_conversations' => $conversations->take(10),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get topper dashboard', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard',
            ], 500);
        }
    }

    /**
     * Get pending chat requests (priority inbox)
     */
    public function getPendingRequests(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isVerifiedTopper()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized as topper',
                ], 403);
            }

            $requests = $this->topperService->getPendingRequestsForTopper($user);

            return response()->json([
                'success' => true,
                'requests' => $requests,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get pending requests', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get requests',
            ], 500);
        }
    }

    /**
     * Accept a chat request
     */
    public function acceptRequest(Request $request, int $requestId): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isVerifiedTopper()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized as topper',
                ], 403);
            }

            $result = $this->topperService->acceptRequest($user, $requestId);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to accept request', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept request',
            ], 500);
        }
    }

    /**
     * Reject a chat request
     */
    public function rejectRequest(Request $request, int $requestId): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:200',
        ]);

        try {
            $user = $request->user();

            if (!$user->isVerifiedTopper()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized as topper',
                ], 403);
            }

            $result = $this->topperService->rejectRequest($user, $requestId, $request->reason);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to reject request', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject request',
            ], 500);
        }
    }

    /**
     * Update availability status
     */
    public function updateAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'available' => 'required|boolean',
        ]);

        try {
            $user = $request->user();

            if (!$user->isVerifiedTopper()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized as topper',
                ], 403);
            }

            $this->topperService->updateTopperAvailability($user, $request->available);

            return response()->json([
                'success' => true,
                'is_available' => $request->available,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update availability', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update availability',
            ], 500);
        }
    }

    /**
     * Get topper's conversations
     */
    public function getConversations(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isVerifiedTopper()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized as topper',
                ], 403);
            }

            $conversations = $this->topperService->getTopperConversations($user);

            return response()->json([
                'success' => true,
                'conversations' => $conversations,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get conversations', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversations',
            ], 500);
        }
    }

    /**
     * Get earnings history
     */
    public function getEarnings(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isVerifiedTopper()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized as topper',
                ], 403);
            }

            $limit = min($request->get('limit', 20), 50);

            $earnings = TopperEarning::forTopper($user->id)
                ->with('student:id,name')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(fn($e) => [
                    'id' => $e->id,
                    'student_name' => $e->student->name,
                    'tokens_received' => $e->tokens_received,
                    'gross_amount' => $e->gross_amount,
                    'platform_fee' => $e->platform_fee,
                    'net_amount' => $e->net_amount,
                    'status' => $e->status,
                    'created_at' => $e->created_at->toISOString(),
                ]);

            // Get summary
            $wallet = $user->getOrCreateTopperWallet();

            return response()->json([
                'success' => true,
                'summary' => [
                    'total_earned' => $wallet->total_earned,
                    'balance' => $wallet->balance,
                    'pending_payout' => $wallet->pending_payout,
                    'total_withdrawn' => $wallet->total_withdrawn,
                ],
                'earnings' => $earnings,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get earnings', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get earnings',
            ], 500);
        }
    }

    /**
     * Get wallet details
     */
    public function getWallet(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isVerifiedTopper()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized as topper',
                ], 403);
            }

            $wallet = $user->getOrCreateTopperWallet();

            return response()->json([
                'success' => true,
                'wallet' => [
                    'balance' => $wallet->balance,
                    'pending_payout' => $wallet->pending_payout,
                    'total_earned' => $wallet->total_earned,
                    'total_withdrawn' => $wallet->total_withdrawn,
                    'upi_id' => $wallet->masked_upi_id,
                    'bank_account' => $wallet->masked_bank_account,
                    'bank_name' => $wallet->bank_name,
                    'has_upi' => $wallet->hasUpiSetup(),
                    'has_bank' => $wallet->hasBankSetup(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get wallet', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get wallet',
            ], 500);
        }
    }

    /**
     * Update payout details
     */
    public function updatePayoutDetails(Request $request): JsonResponse
    {
        $request->validate([
            'upi_id' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:20',
            'bank_ifsc' => 'nullable|string|max:11',
            'bank_name' => 'nullable|string|max:100',
            'pan_number' => 'nullable|string|max:10',
        ]);

        try {
            $user = $request->user();

            if (!$user->isVerifiedTopper()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized as topper',
                ], 403);
            }

            $wallet = $user->getOrCreateTopperWallet();

            $wallet->update($request->only([
                'upi_id',
                'bank_account_number',
                'bank_ifsc',
                'bank_name',
                'pan_number',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Payout details updated',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update payout details', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payout details',
            ], 500);
        }
    }

    /**
     * Request withdrawal
     */
    public function requestWithdrawal(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:100', // Minimum ₹100
            'method' => 'required|in:upi,bank_transfer',
        ]);

        try {
            $user = $request->user();

            if (!$user->isVerifiedTopper()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized as topper',
                ], 403);
            }

            $wallet = $user->getOrCreateTopperWallet();

            // Check if method is set up
            if ($request->method === 'upi' && !$wallet->hasUpiSetup()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please set up your UPI ID first',
                ], 400);
            }

            if ($request->method === 'bank_transfer' && !$wallet->hasBankSetup()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please set up your bank details first',
                ], 400);
            }

            // Check balance
            if ($wallet->balance < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance',
                ], 400);
            }

            $withdrawal = $wallet->requestWithdrawal($request->amount, $request->method);

            if (!$withdrawal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create withdrawal request',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted',
                'withdrawal' => [
                    'id' => $withdrawal->id,
                    'amount' => $withdrawal->amount,
                    'method' => $withdrawal->payout_method,
                    'status' => $withdrawal->status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to request withdrawal', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to request withdrawal',
            ], 500);
        }
    }

    /**
     * Get withdrawal history
     */
    public function getWithdrawals(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isVerifiedTopper()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized as topper',
                ], 403);
            }

            $withdrawals = WithdrawalRequest::forTopper($user->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(fn($w) => [
                    'id' => $w->id,
                    'amount' => $w->amount,
                    'method' => $w->payout_method,
                    'status' => $w->status,
                    'payout_details' => $w->formatted_payout_details,
                    'transaction_reference' => $w->transaction_reference,
                    'rejection_reason' => $w->rejection_reason,
                    'processed_at' => $w->processed_at?->toISOString(),
                    'created_at' => $w->created_at->toISOString(),
                ]);

            return response()->json([
                'success' => true,
                'withdrawals' => $withdrawals,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get withdrawals', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get withdrawals',
            ], 500);
        }
    }

    /**
     * Update topper profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'bio' => 'nullable|string|max:500',
            'specialization' => 'nullable|string|max:100',
            'subjects' => 'nullable|array',
            'subjects.*' => 'string|max:50',
        ]);

        try {
            $user = $request->user();

            if (!$user->isVerifiedTopper()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized as topper',
                ], 403);
            }

            $user->update([
                'topper_bio' => $request->bio,
                'topper_specialization' => $request->specialization,
                'topper_subjects' => $request->subjects,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update profile', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
            ], 500);
        }
    }
}
