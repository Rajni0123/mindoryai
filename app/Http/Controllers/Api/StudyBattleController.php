<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StudyBattleService;
use App\Services\UsageLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StudyBattleController extends Controller
{
    protected StudyBattleService $battleService;
    protected UsageLimitService $usageLimitService;

    public function __construct(StudyBattleService $battleService, UsageLimitService $usageLimitService)
    {
        $this->battleService = $battleService;
        $this->usageLimitService = $usageLimitService;
    }

    /**
     * Get bonus battles cache key for user
     */
    private function getBonusBattlesKey(int $userId): string
    {
        return "bonus_study_battles:{$userId}";
    }

    /**
     * Get user's bonus battles count
     */
    private function getBonusBattles(int $userId): int
    {
        return (int) Cache::get($this->getBonusBattlesKey($userId), 0);
    }

    /**
     * Use one bonus battle
     */
    private function useBonusBattle(int $userId): bool
    {
        $key = $this->getBonusBattlesKey($userId);
        $current = $this->getBonusBattles($userId);

        if ($current > 0) {
            Cache::put($key, $current - 1, now()->addDays(30));
            return true;
        }
        return false;
    }

    /**
     * Add bonus battles for user
     */
    private function addBonusBattles(int $userId, int $count): int
    {
        $key = $this->getBonusBattlesKey($userId);
        $current = $this->getBonusBattles($userId);
        $new = $current + $count;
        Cache::put($key, $new, now()->addDays(30));
        return $new;
    }

    /**
     * Create a new battle room
     * POST /api/study-battle/create
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:100',
            'topic' => 'required|string|max:200',
            'subject' => 'nullable|string|max:100',
            'exam_type' => 'nullable|string|max:50',
            'difficulty' => 'nullable|in:easy,medium,hard,mixed',
            'max_players' => 'nullable|integer|min:2|max:4',
            'question_count' => 'nullable|integer|min:5|max:20',
            'time_per_question' => 'nullable|integer|min:10|max:30',
        ]);

        $user = $request->user();
        $useBonus = $request->boolean('use_bonus', false);

        // Check usage limits
        $limitCheck = $this->usageLimitService->canUse($user, 'study_battle');
        $bonusBattles = $this->getBonusBattles($user->id);

        if (!$limitCheck['allowed']) {
            // Check if user wants to use bonus battle
            if ($useBonus && $bonusBattles > 0) {
                // Allow using bonus battle
            } else {
                // Return with topup option
                return response()->json([
                    'success' => false,
                    'message' => $limitCheck['reason'] ?? 'Daily study battle limit reached.',
                    'limit_exhausted' => true,
                    'limit_info' => $limitCheck,
                    'bonus_battles' => $bonusBattles,
                    'can_topup' => true,
                    'topup_packages' => $this->getTokenPackages(),
                    'next_reset' => now()->addDay()->startOfDay()->toISOString(),
                ], 429);
            }
        }

        try {
            $room = $this->battleService->createRoom($user, [
                'title' => $request->title,
                'topic' => $request->topic,
                'subject' => $request->subject,
                'exam_type' => $request->exam_type,
                'difficulty' => $request->difficulty ?? 'medium',
                'max_players' => $request->max_players ?? 4,
                'question_count' => $request->question_count ?? 10,
                'time_per_question' => $request->time_per_question ?? 15,
            ]);

            // Record usage or use bonus battle
            if (!$limitCheck['allowed'] && $useBonus) {
                $this->useBonusBattle($user->id);
            } else {
                $this->usageLimitService->recordUsage($user, 'study_battle');
            }

            return response()->json([
                'success' => true,
                'message' => 'Room created successfully.',
                'data' => [
                    'room_id' => $room->id,
                    'room_code' => $room->room_code,
                    'title' => $room->title,
                    'topic' => $room->topic,
                    'difficulty' => $room->difficulty,
                    'max_players' => $room->max_players,
                    'question_count' => $room->question_count,
                    'time_per_question' => $room->time_per_question,
                    'expires_at' => $room->expires_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create room: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Join a room by code
     * GET /api/study-battle/join/{code}
     */
    public function join(Request $request, string $code): JsonResponse
    {
        $user = $request->user();

        try {
            $result = $this->battleService->joinRoom($user, strtoupper($code));

            return response()->json([
                'success' => true,
                'message' => 'Joined room successfully.',
                'data' => [
                    'room_id' => $result['room']->id,
                    'room_code' => $result['room']->room_code,
                    'title' => $result['room']->title,
                    'topic' => $result['room']->topic,
                    'difficulty' => $result['room']->difficulty,
                    'max_players' => $result['room']->max_players,
                    'question_count' => $result['room']->question_count,
                    'time_per_question' => $result['room']->time_per_question,
                    'host_name' => $result['room']->host->name ?? 'Host',
                    'participant_id' => $result['participant']->id,
                    'is_host' => $result['participant']->is_host,
                ],
            ]);
        } catch (\Exception $e) {
            $statusCode = 400;
            if (str_contains($e->getMessage(), 'not found') || str_contains($e->getMessage(), 'expired')) {
                $statusCode = 404;
            } elseif (str_contains($e->getMessage(), 'full')) {
                $statusCode = 403;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Leave current room
     * POST /api/study-battle/leave
     */
    public function leave(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|integer|exists:study_battle_rooms,id',
        ]);

        $user = $request->user();

        try {
            $this->battleService->leaveRoom($user, $request->room_id);

            return response()->json([
                'success' => true,
                'message' => 'Left room successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark player as ready
     * POST /api/study-battle/ready
     */
    public function ready(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|integer|exists:study_battle_rooms,id',
        ]);

        $user = $request->user();

        try {
            $participant = $this->battleService->markReady($user, $request->room_id);

            return response()->json([
                'success' => true,
                'message' => 'Marked as ready.',
                'data' => [
                    'status' => $participant->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Host starts the battle
     * POST /api/study-battle/start
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|integer|exists:study_battle_rooms,id',
        ]);

        $user = $request->user();

        try {
            $room = $this->battleService->startBattle($user, $request->room_id);

            return response()->json([
                'success' => true,
                'message' => 'Battle started!',
                'data' => [
                    'room_id' => $room->id,
                    'status' => $room->status,
                    'question_count' => $room->total_questions,
                    'time_per_question' => $room->time_per_question,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Poll for current game state
     * GET /api/study-battle/poll/{roomId}
     */
    public function poll(Request $request, int $roomId): JsonResponse
    {
        $user = $request->user();

        try {
            $response = $this->battleService->poll($user, $roomId);

            return response()->json([
                'success' => true,
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Submit an answer
     * POST /api/study-battle/answer
     */
    public function answer(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|integer|exists:study_battle_rooms,id',
            'question_index' => 'required|integer|min:0',
            'answer' => 'required|string|in:A,B,C,D',
        ]);

        $user = $request->user();

        try {
            $result = $this->battleService->submitAnswer(
                $user,
                $request->room_id,
                $request->question_index,
                $request->answer
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get final battle results
     * GET /api/study-battle/results/{roomId}
     */
    public function results(Request $request, int $roomId): JsonResponse
    {
        $user = $request->user();

        try {
            $results = $this->battleService->getResults($user, $roomId);

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user's battle history
     * GET /api/study-battle/history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->query('limit', 20);

        try {
            $history = $this->battleService->getHistory($user, min($limit, 50));

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get global leaderboard
     * GET /api/study-battle/leaderboard
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 20);
        $period = $request->query('period', 'weekly'); // daily, weekly, monthly, all

        try {
            $leaderboard = $this->battleService->getLeaderboard(min($limit, 100), $period);

            return response()->json([
                'success' => true,
                'data' => $leaderboard,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active public rooms (for room browser)
     * GET /api/study-battle/rooms
     */
    public function rooms(Request $request): JsonResponse
    {
        try {
            $rooms = \App\Models\StudyBattleRoom::where('status', 'waiting')
                ->where('expires_at', '>', now())
                ->with(['host:id,name', 'participants' => function ($q) {
                    $q->whereIn('status', ['joined', 'ready']);
                }])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($room) {
                    return [
                        'room_id' => $room->id,
                        'room_code' => $room->room_code,
                        'title' => $room->title,
                        'topic' => $room->topic,
                        'difficulty' => $room->difficulty,
                        'host_name' => $room->host->name ?? 'Host',
                        'current_players' => $room->participants->count(),
                        'max_players' => $room->max_players,
                        'question_count' => $room->question_count,
                        'time_per_question' => $room->time_per_question,
                        'created_at' => $room->created_at->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $rooms,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's bonus battles status
     * GET /api/study-battle/bonus-status
     */
    public function bonusStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $limitCheck = $this->usageLimitService->canUse($user, 'study_battle');
        $bonusBattles = $this->getBonusBattles($user->id);

        // Get today's ad reward count
        $todayAdRewards = (int) Cache::get("study_battle_ad_rewards:{$user->id}:" . now()->toDateString(), 0);
        $maxAdRewardsPerDay = 5;

        return response()->json([
            'success' => true,
            'data' => [
                'daily_limit' => $limitCheck,
                'bonus_battles' => $bonusBattles,
                'can_watch_ad' => $todayAdRewards < $maxAdRewardsPerDay,
                'ads_watched_today' => $todayAdRewards,
                'max_ads_per_day' => $maxAdRewardsPerDay,
                'next_reset' => now()->addDay()->startOfDay()->toISOString(),
            ],
        ]);
    }

    /**
     * Claim ad reward - Get bonus battle after watching ad
     * POST /api/study-battle/ad-reward
     */
    public function adReward(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if ads are enabled
        $adsEnabled = filter_var(\App\Models\DynamicAppConfig::getValue('ads.enabled', false), FILTER_VALIDATE_BOOLEAN);
        if (!$adsEnabled) {
            return response()->json([
                'success' => false,
                'message' => 'Ad rewards are currently disabled.',
            ], 403);
        }

        // Rate limit: max 5 ad rewards per day for study battles
        $todayKey = "study_battle_ad_rewards:{$user->id}:" . now()->toDateString();
        $todayRewards = (int) Cache::get($todayKey, 0);
        $maxAdRewardsPerDay = 5;

        if ($todayRewards >= $maxAdRewardsPerDay) {
            return response()->json([
                'success' => false,
                'message' => 'Daily ad reward limit reached. Try again tomorrow!',
                'ads_watched_today' => $todayRewards,
                'max_ads_per_day' => $maxAdRewardsPerDay,
            ], 429);
        }

        // Grant 1 bonus battle
        $newTotal = $this->addBonusBattles($user->id, 1);

        // Track ad reward count
        Cache::put($todayKey, $todayRewards + 1, now()->endOfDay());

        return response()->json([
            'success' => true,
            'message' => 'You earned 1 bonus battle!',
            'data' => [
                'bonus_battles' => $newTotal,
                'earned' => 1,
                'ads_watched_today' => $todayRewards + 1,
                'ads_remaining_today' => $maxAdRewardsPerDay - ($todayRewards + 1),
            ],
        ]);
    }

    /**
     * Get available token packages for purchase
     */
    private function getTokenPackages(): array
    {
        return [
            [
                'id' => 'battle_5',
                'battles' => 5,
                'price' => 19,
                'currency' => 'INR',
                'label' => '5 Battles',
                'description' => 'Best for trying out',
                'popular' => false,
            ],
            [
                'id' => 'battle_15',
                'battles' => 15,
                'price' => 49,
                'currency' => 'INR',
                'label' => '15 Battles',
                'description' => 'Most Popular',
                'popular' => true,
                'savings' => '14%',
            ],
            [
                'id' => 'battle_50',
                'battles' => 50,
                'price' => 149,
                'currency' => 'INR',
                'label' => '50 Battles',
                'description' => 'Best Value',
                'popular' => false,
                'savings' => '22%',
            ],
        ];
    }

    /**
     * Get token packages for purchase
     * GET /api/study-battle/packages
     */
    public function packages(Request $request): JsonResponse
    {
        $user = $request->user();
        $bonusBattles = $this->getBonusBattles($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'current_balance' => $bonusBattles,
                'packages' => $this->getTokenPackages(),
            ],
        ]);
    }

    /**
     * Create payment order for token purchase
     * POST /api/study-battle/purchase
     */
    public function purchase(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|string|in:battle_5,battle_15,battle_50',
            'gateway' => 'required|string|in:razorpay,cashfree,phonepe',
        ]);

        $user = $request->user();
        $packageId = $request->package_id;

        // Get package details
        $packages = collect($this->getTokenPackages());
        $package = $packages->firstWhere('id', $packageId);

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid package selected.',
            ], 400);
        }

        // Get payment gateway
        $gateway = \App\Models\PaymentGateway::where('name', $request->gateway)
            ->where('is_enabled', true)
            ->first();

        if (!$gateway || !$gateway->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment gateway not available.',
            ], 400);
        }

        try {
            // Create payment order
            $paymentService = app(\App\Services\PaymentService::class);
            $result = $paymentService->createOrder(
                $request->gateway,
                $package['price'],
                'study_battle_tokens',
                $user->id,
                [
                    'package_id' => $packageId,
                    'battles' => $package['battles'],
                    'description' => "Study Battle - {$package['label']}",
                ]
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to create order.',
                ], 400);
            }

            // Store order for verification
            \Illuminate\Support\Facades\DB::table('payment_orders')->insert([
                'order_id' => $result['gateway_order_id'],
                'user_id' => $user->id,
                'plan_id' => null,
                'amount' => $package['price'],
                'currency' => 'INR',
                'billing_cycle' => null,
                'payment_gateway' => $request->gateway,
                'status' => 'pending',
                'metadata' => json_encode([
                    'type' => 'study_battle_tokens',
                    'package_id' => $packageId,
                    'battles' => $package['battles'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $result['gateway_order_id'],
                    'transaction_id' => $result['transaction_id'],
                    'amount' => $package['price'],
                    'currency' => 'INR',
                    'package' => $package,
                    'gateway_data' => $result['gateway_data'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify payment and credit tokens
     * POST /api/study-battle/verify-purchase
     */
    public function verifyPurchase(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|string',
            'payment_id' => 'required|string',
            'signature' => 'nullable|string',
        ]);

        $user = $request->user();

        // Get order
        $order = \Illuminate\Support\Facades\DB::table('payment_orders')
            ->where('order_id', $request->order_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Idempotency check
        if ($order->status === 'completed') {
            $metadata = json_decode($order->metadata, true);
            return response()->json([
                'success' => true,
                'message' => 'Payment already verified.',
                'data' => [
                    'battles_credited' => $metadata['battles'] ?? 0,
                    'already_processed' => true,
                ],
            ]);
        }

        // Verify payment
        $paymentService = app(\App\Services\PaymentService::class);
        $paymentData = ['payment_id' => $request->payment_id];

        if ($order->payment_gateway === 'razorpay' && $request->signature) {
            $paymentData['razorpay_payment_id'] = $request->payment_id;
            $paymentData['razorpay_signature'] = $request->signature;
        }

        // Get transaction
        $transaction = \App\Models\Transaction::where('gateway_order_id', $order->order_id)->first();
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        $result = $paymentService->verifyPayment($transaction->transaction_id, $paymentData);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Payment verification failed.',
            ], 400);
        }

        // Credit tokens
        $metadata = json_decode($order->metadata, true);
        $battles = $metadata['battles'] ?? 0;

        if ($battles > 0) {
            $newBalance = $this->addBonusBattles($user->id, $battles);

            // Update order status
            \Illuminate\Support\Facades\DB::table('payment_orders')
                ->where('order_id', $request->order_id)
                ->update([
                    'status' => 'completed',
                    'payment_id' => $request->payment_id,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => "{$battles} battles added to your account!",
                'data' => [
                    'battles_credited' => $battles,
                    'new_balance' => $newBalance,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid package data.',
        ], 400);
    }
}
