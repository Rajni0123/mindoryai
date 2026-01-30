<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReferralController extends Controller
{
    /**
     * Get user's referral code and stats
     */
    public function getReferralInfo(Request $request)
    {
        $user = auth()->user();

        // Generate referral code if user doesn't have one
        if (!$user->hasReferralCode()) {
            $referralCode = $user->generateReferralCode();
        } else {
            $referralCode = $user->referral_code;
        }

        $stats = $user->getReferralStats();

        // Get list of referred users
        $referredUsers = $user->referrals()
            ->select('id', 'name', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name ?? 'Anonymous',
                    'joined_at' => $user->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code' => $referralCode,
                'referral_link' => $this->generateReferralLink($referralCode),
                'stats' => $stats,
                'referred_users' => $referredUsers,
                'reward_per_referral' => 3,
                'currency' => 'credits',
            ]
        ]);
    }

    /**
     * Validate a referral code
     */
    public function validateReferralCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid referral code format',
                'errors' => $validator->errors(),
            ], 422);
        }

        $code = $request->input('referral_code');
        $user = auth()->user();

        // Check if user already applied a referral code
        if (!empty($user->referred_by)) {
            return response()->json([
                'success' => false,
                'message' => 'You have already used a referral code',
            ], 400);
        }

        // Prevent self-referral
        if ($user->referral_code === $code) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot use your own referral code',
            ], 400);
        }

        // Check if referral code exists
        $referrer = \App\Models\User::where('referral_code', $code)->first();

        if (!$referrer) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid referral code',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'valid' => true,
                'referrer_name' => $referrer->name ?? 'A friend',
                'reward' => '3 credits bonus',
            ]
        ]);
    }

    /**
     * Apply a referral code to the current user
     */
    public function applyReferralCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid referral code format',
                'errors' => $validator->errors(),
            ], 422);
        }

        $code = $request->input('referral_code');
        $user = auth()->user();

        // Check if user already applied a referral code
        if (!empty($user->referred_by)) {
            return response()->json([
                'success' => false,
                'message' => 'You have already used a referral code',
            ], 400);
        }

        // Apply the referral code
        $result = $user->applyReferralCode($code);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply referral code. Please check the code and try again.',
            ], 400);
        }

        // Get referrer info
        $referrer = \App\Models\User::find($user->referred_by);

        return response()->json([
            'success' => true,
            'message' => 'Referral code applied successfully! You received 3 credits.',
            'data' => [
                'referrer_name' => $referrer->name ?? 'A friend',
                'credits_earned' => 3,
                'referrer_earned' => 3,
            ]
        ]);
    }

    /**
     * Get referral leaderboard
     */
    public function getLeaderboard(Request $request)
    {
        $topReferrers = \App\Models\User::query()
            ->whereNotNull('referral_code')
            ->where('referral_count', '>', 0)
            ->orderBy('referral_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name ?? 'Anonymous',
                    'referral_count' => $user->referral_count,
                    'total_earned' => $user->referral_count * 3,
                ];
            });

        // Get current user's rank
        $user = auth()->user();
        $userRank = \App\Models\User::where('referral_count', '>', $user->referral_count)->count() + 1;

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $topReferrers,
                'user_rank' => $userRank,
                'user_referrals' => $user->referral_count,
            ]
        ]);
    }

    /**
     * Generate referral link
     */
    private function generateReferralLink(string $code): string
    {
        // You can customize this based on your app URL structure
        return url('/referral/' . $code);
    }

    /**
     * Share referral code (returns shareable text/link)
     */
    public function shareReferral(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasReferralCode()) {
            $referralCode = $user->generateReferralCode();
        } else {
            $referralCode = $user->referral_code;
        }

        $referralLink = $this->generateReferralLink($referralCode);

        $shareMessages = [
            'whatsapp' => "Hey! I'm using Mindory AI for my studies. It's an amazing AI tutor that helps with homework, notes, and quiz preparation. Sign up using my referral code {$referralCode} and get 3 FREE credits! 🎓\n\nDownload now: {$referralLink}",
            'telegram' => "🚀 Mindory AI - Your Personal AI Tutor!\n\nGet 3 FREE credits when you sign up using my referral code: {$referralCode}\n\nLink: {$referralLink}",
            'default' => "Join Mindory AI - Your Personal AI Tutor! Use my referral code {$referralCode} to get 3 FREE credits. Sign up now: {$referralLink}",
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code' => $referralCode,
                'referral_link' => $referralLink,
                'share_messages' => $shareMessages,
            ]
        ]);
    }
}
