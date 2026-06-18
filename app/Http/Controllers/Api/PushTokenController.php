<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use App\Services\ExpoPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushTokenController extends Controller
{
    protected ExpoPushService $pushService;

    public function __construct(ExpoPushService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Save or update push token
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'required|in:android,ios',
        ]);

        $user = Auth::user();
        $token = $request->input('token');
        $platform = $request->input('platform');

        // Validate Expo push token format
        if (!$this->pushService->isValidToken($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid push token format',
            ], 400);
        }

        // Create or update token
        $pushToken = PushToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Push token saved successfully',
            'token_id' => $pushToken->id,
        ]);
    }

    /**
     * Remove push token (on logout)
     */
    public function destroy(Request $request)
    {
        $user = Auth::user();

        // Deactivate all tokens for this user
        PushToken::where('user_id', $user->id)->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Push tokens deactivated',
        ]);
    }

    /**
     * Test push notification (for debugging)
     */
    public function test(Request $request)
    {
        $user = Auth::user();

        $result = $this->pushService->sendFunNotification($user);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? 'Notification sent',
            'result' => $result,
        ]);
    }
}
