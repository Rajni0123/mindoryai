<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\PublicDoubt;
use App\Models\User;
use App\Services\TopperConnectService;
use App\Services\TokenWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TopperConnectController extends Controller
{
    protected TopperConnectService $topperService;
    protected TokenWalletService $walletService;

    public function __construct(
        TopperConnectService $topperService,
        TokenWalletService $walletService
    ) {
        $this->topperService = $topperService;
        $this->walletService = $walletService;
    }

    /**
     * Get available toppers
     */
    public function getToppers(Request $request): JsonResponse
    {
        try {
            $subject = $request->get('subject');
            $toppers = $this->topperService->getAvailableToppers($subject);

            return response()->json([
                'success' => true,
                'toppers' => $toppers,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get toppers', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available toppers',
            ], 500);
        }
    }

    /**
     * Get featured toppers
     */
    public function getFeaturedToppers(): JsonResponse
    {
        try {
            $toppers = $this->topperService->getFeaturedToppers();

            return response()->json([
                'success' => true,
                'toppers' => $toppers,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get featured toppers', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get featured toppers',
            ], 500);
        }
    }

    /**
     * Get topper profile
     */
    public function getTopperProfile(int $topperId): JsonResponse
    {
        try {
            $topper = User::where('id', $topperId)
                ->where('is_verified_topper', true)
                ->first();

            if (!$topper) {
                return response()->json([
                    'success' => false,
                    'message' => 'Topper not found',
                ], 404);
            }

            // Get recent resolved doubts by this topper
            $recentDoubts = PublicDoubt::where('topper_id', $topperId)
                ->approved()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn($d) => [
                    'subject' => $d->subject,
                    'question' => substr($d->question, 0, 100) . '...',
                    'upvotes' => $d->upvotes,
                ]);

            return response()->json([
                'success' => true,
                'topper' => [
                    'id' => $topper->id,
                    'name' => $topper->name,
                    'exam' => $topper->topper_exam,
                    'rank' => $topper->topper_rank,
                    'rating' => $topper->topper_rating,
                    'total_chats' => $topper->topper_total_chats,
                    'specialization' => $topper->topper_specialization,
                    'subjects' => $topper->topper_subjects,
                    'bio' => $topper->topper_bio,
                    'is_available' => $topper->topper_available,
                    'recent_doubts' => $recentDoubts,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get topper profile', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get topper profile',
            ], 500);
        }
    }

    /**
     * Create chat request
     */
    public function createRequest(Request $request): JsonResponse
    {
        $request->validate([
            'topper_id' => 'required|integer|exists:users,id',
            'subject' => 'required|string|max:100',
            'doubt_preview' => 'required|string|min:10|max:500',
        ]);

        try {
            $result = $this->topperService->createChatRequest(
                $request->user(),
                $request->topper_id,
                $request->subject,
                $request->doubt_preview
            );

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to create chat request', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create chat request',
            ], 500);
        }
    }

    /**
     * Cancel chat request (student)
     */
    public function cancelRequest(Request $request, int $requestId): JsonResponse
    {
        try {
            $chatRequest = $request->user()->chatRequestsAsStudent()
                ->where('id', $requestId)
                ->where('status', 'pending')
                ->first();

            if (!$chatRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found or cannot be cancelled',
                ], 404);
            }

            $chatRequest->cancel();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel request', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel request',
            ], 500);
        }
    }

    /**
     * Get student's pending requests
     */
    public function getMyRequests(Request $request): JsonResponse
    {
        try {
            $requests = $request->user()->chatRequestsAsStudent()
                ->whereIn('status', ['pending', 'accepted'])
                ->with('topper:id,name,topper_rating')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn($r) => [
                    'id' => $r->id,
                    'topper' => [
                        'id' => $r->topper->id,
                        'name' => $r->topper->name,
                        'rating' => $r->topper->topper_rating,
                    ],
                    'subject' => $r->subject,
                    'status' => $r->status,
                    'expires_at' => $r->expires_at?->toISOString(),
                    'created_at' => $r->created_at->toISOString(),
                ]);

            return response()->json([
                'success' => true,
                'requests' => $requests,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get requests', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get requests',
            ], 500);
        }
    }

    /**
     * Get student's conversations
     */
    public function getMyConversations(Request $request): JsonResponse
    {
        try {
            $conversations = $this->topperService->getStudentConversations($request->user());

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
     * Get public doubts feed
     */
    public function getPublicDoubts(Request $request): JsonResponse
    {
        try {
            $subject = $request->get('subject');
            $sort = $request->get('sort', 'recent');
            $limit = min($request->get('limit', 20), 50);

            $doubts = $this->topperService->getPublicDoubts($subject, $sort, $limit);

            return response()->json([
                'success' => true,
                'doubts' => $doubts,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get public doubts', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get public doubts',
            ], 500);
        }
    }

    /**
     * Get single public doubt
     */
    public function getPublicDoubt(int $doubtId): JsonResponse
    {
        try {
            $doubt = PublicDoubt::approved()
                ->with(['topper:id,name,topper_rating,topper_exam'])
                ->find($doubtId);

            if (!$doubt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doubt not found',
                ], 404);
            }

            // Increment views
            $doubt->incrementViews();

            return response()->json([
                'success' => true,
                'doubt' => [
                    'id' => $doubt->id,
                    'subject' => $doubt->subject,
                    'topic' => $doubt->topic,
                    'question' => $doubt->question,
                    'answer' => $doubt->answer,
                    'image_url' => $doubt->image_url,
                    'upvotes' => $doubt->upvotes,
                    'views' => $doubt->views,
                    'topper' => [
                        'id' => $doubt->topper->id,
                        'name' => $doubt->topper->name,
                        'rating' => $doubt->topper->topper_rating,
                        'exam' => $doubt->topper->topper_exam,
                    ],
                    'tags' => $doubt->tags,
                    'created_at' => $doubt->created_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get public doubt', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get doubt',
            ], 500);
        }
    }

    /**
     * Upvote a public doubt
     */
    public function upvoteDoubt(Request $request, int $doubtId): JsonResponse
    {
        try {
            $doubt = PublicDoubt::approved()->find($doubtId);

            if (!$doubt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doubt not found',
                ], 404);
            }

            $doubt->upvote();

            return response()->json([
                'success' => true,
                'upvotes' => $doubt->upvotes,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upvote doubt', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to upvote',
            ], 500);
        }
    }

    /**
     * Search public doubts
     */
    public function searchDoubts(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:3|max:200',
        ]);

        try {
            $doubts = $this->topperService->searchPublicDoubts($request->q);

            return response()->json([
                'success' => true,
                'doubts' => $doubts,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to search doubts', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
            ], 500);
        }
    }

    /**
     * Get connect home screen data
     */
    public function getHomeData(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $wallet = $this->walletService->getWallet($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'token_balance' => $wallet->token_balance,
                    'featured_toppers' => $this->topperService->getFeaturedToppers(3),
                    'available_toppers' => $this->topperService->getAvailableToppers(null, 10),
                    'trending_doubts' => $this->topperService->getPublicDoubts(null, 'trending', 5),
                    'active_conversations' => $this->topperService->getStudentConversations($user)
                        ->where('status', 'active')
                        ->take(3),
                    'pricing' => [
                        'info' => '10 tokens = ₹1',
                        'minimum_required' => 10,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get home data', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load data',
            ], 500);
        }
    }
}
