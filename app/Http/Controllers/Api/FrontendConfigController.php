<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrontendConfig;
use App\Support\SensitiveConfigFilter;
use Illuminate\Http\JsonResponse;

class FrontendConfigController extends Controller
{
    /**
     * Get public frontend configurations (secrets stripped).
     */
    public function index(): JsonResponse
    {
        try {
            $configs = FrontendConfig::getPublicConfigs();

            return response()->json($configs, 200, [
                'Cache-Control' => 'public, max-age=3600',
                'X-Config-Version' => now()->timestamp,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load configurations',
            ], 500);
        }
    }

    /**
     * Get a single public config value by key.
     */
    public function show(string $key): JsonResponse
    {
        if (SensitiveConfigFilter::isSensitiveKey($key)) {
            return response()->json([
                'error' => 'Configuration not found',
            ], 404);
        }

        try {
            $value = FrontendConfig::getValue($key);

            if ($value === null) {
                return response()->json([
                    'error' => 'Configuration not found',
                ], 404);
            }

            return response()->json([
                'key' => $key,
                'value' => $value,
            ], 200, [
                'Cache-Control' => 'public, max-age=3600',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load configuration',
            ], 500);
        }
    }
}
