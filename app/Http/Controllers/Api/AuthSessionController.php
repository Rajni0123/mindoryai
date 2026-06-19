<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthSessionController extends Controller
{
    /**
     * Rotate Sanctum API token (mobile / bearer clients).
     * Web SPAs should rely on httpOnly session cookies instead of this endpoint.
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->isLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Account is locked due to repeated failed login attempts.',
            ], 423);
        }

        $accessToken = AuthTokenService::refreshAccessToken($user);
        $expiresInMinutes = AuthTokenService::expirationMinutes();

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed.',
            'token' => $accessToken->plainTextToken,
            'expires_in_minutes' => $expiresInMinutes,
        ]);
    }
}
