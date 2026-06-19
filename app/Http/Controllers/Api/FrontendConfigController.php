<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrontendConfig;
use Illuminate\Http\JsonResponse;

class FrontendConfigController extends Controller
{
    /**
     * Get all active frontend configurations
     * This endpoint is publicly accessible and cached
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $configs = FrontendConfig::getAllConfigs();

            return response()->json($configs, 200, [
                'Cache-Control' => 'public, max-age=3600', // Cache for 1 hour
                'X-Config-Version' => now()->timestamp
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load configurations'
            ], 500);
        }
    }

    /**
     * Get specific config value by key
     *
     * @param string $key
     * @return JsonResponse
     */
    public function show(string $key): JsonResponse
    {
        try {
            $value = FrontendConfig::getValue($key);

            if ($value === null) {
                return response()->json([
                    'error' => 'Configuration not found'
                ], 404);
            }

            return response()->json([
                'key' => $key,
                'value' => $value
            ], 200, [
                'Cache-Control' => 'public, max-age=3600'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load configuration'
            ], 500);
        }
    }
}
