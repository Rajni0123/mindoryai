<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BadgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    /**
     * GET /api/user/badges
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        BadgeService::syncBadgeAchievements($userId);

        return response()->json(BadgeService::getUserBadges($userId));
    }
}
