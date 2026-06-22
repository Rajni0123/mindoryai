<?php

namespace App\Http\Controllers;

use App\Services\WebDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebDashboardController extends Controller
{
    public function show(Request $request, WebDashboardService $dashboard): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        try {
            return response()->json($dashboard->build($user));
        } catch (\Throwable $e) {
            Log::error('Web dashboard build failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not load dashboard.',
            ], 500);
        }
    }
}
