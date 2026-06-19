<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DynamicAppConfig;
use App\Support\ApiValidator;
use App\Support\ResourceAuthorizer;
use App\Support\SensitiveConfigFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class DynamicAppController extends Controller
{
    /**
     * Get all public dynamic app configurations
     * This endpoint is called by mobile app on startup
     */
    public function getConfigs(Request $request)
    {
        $appVersion = $request->header('X-App-Version') ?? $request->input('app_version', '1.0.0');
        $platform = $request->header('X-Platform') ?? $request->input('platform', 'android');

        ApiValidator::validateRoute($request, [
            'app_version' => $appVersion,
            'platform' => $platform,
        ], [
            'app_version' => ['required', 'string', 'max:20', 'regex:/^[0-9]+(\.[0-9]+)*$/'],
            'platform' => 'required|string|in:' . implode(',', config('api-validation.platforms', ['android', 'ios'])),
        ]);

        // Get all public configs
        $configs = DynamicAppConfig::public()
            ->get()
            ->filter(fn ($config) => !SensitiveConfigFilter::isSensitiveKey($config->key))
            ->map(function ($config) {
                $value = DynamicAppConfig::decodeValue($config->value, $config->type);

                // Convert relative URLs to absolute for mobile app
                if (is_string($value) && !str_starts_with($value, 'http') &&
                    (str_contains($config->key, 'url') || str_contains($config->key, 'logo') || str_contains($config->key, 'icon'))) {
                    // If it's a relative path, convert to absolute URL
                    $value = asset(ltrim($value, '/'));
                }

                return [
                    'key' => $config->key,
                    'value' => $value,
                    'type' => $config->type,
                    'label' => $config->label,
                    'category' => $config->category,
                    'version' => $config->version,
                    'updated_at' => $config->updated_at->toIso8601String(),
                ];
            })
            ->keyBy('key')
            ->toArray();

        // Check for app updates
        $updateInfo = $this->checkForUpdates($appVersion, $platform, $configs);

        return response()->json([
            'success' => true,
            'configs' => $configs,
            'update_info' => $updateInfo,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Check for app updates
     */
    protected function checkForUpdates(string $currentVersion, string $platform, array $configs): array
    {
        $latestVersion = $configs['app.latest_version']['value'] ?? '1.0.0';
        $minSupportedVersion = $configs['app.min_supported_version']['value'] ?? '1.0.0';
        $forceUpdate = $configs['app.force_update']['value'] ?? false;
        $updateMessage = $configs['app.update_message']['value'] ?? 'A new version is available.';

        // Compare versions
        $updateAvailable = version_compare($latestVersion, $currentVersion, '>');
        $isSupported = version_compare($currentVersion, $minSupportedVersion, '>=');
        $mustUpdate = !$isSupported || $forceUpdate;

        // Get update URL based on platform
        $updateUrlKey = $platform === 'ios' ? 'app.update_url_ios' : 'app.update_url_android';
        $updateUrl = $configs[$updateUrlKey]['value'] ?? null;

        return [
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'min_supported_version' => $minSupportedVersion,
            'update_available' => $updateAvailable,
            'force_update' => $mustUpdate,
            'update_url' => $updateUrl,
            'message' => $updateAvailable ? $updateMessage : null,
        ];
    }

    /**
     * Get app icon information
     * Mobile app calls this to check if icon needs updating
     */
    public function getIconInfo(Request $request)
    {
        $iconVersion = DynamicAppConfig::getValue('app.icon_version', 1);
        $iconUrl = DynamicAppConfig::getValue('app.icon_url', asset('mobile-app/assets/icon.png'));

        return response()->json([
            'success' => true,
            'icon_url' => $iconUrl,
            'icon_version' => $iconVersion,
            'last_updated' => DynamicAppConfig::where('key', 'app.icon_version')
                ->value('updated_at'),
        ]);
    }

    /**
     * Upload new app icon (Admin only)
     */
    public function uploadIcon(Request $request)
    {
        ResourceAuthorizer::ensureAdmin($request->user());

        $validator = Validator::make($request->all(), [
            'icon' => 'required|image|mimes:png,jpg,jpeg|max:1024', // Max 1MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Store the icon in public directory
            $file = $request->file('icon');
            $filename = 'app_icon_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/mobile'), $filename);
            $url = '/uploads/mobile/' . $filename;

            // Update config
            DynamicAppConfig::setValue('app.icon_url', $url);
            DynamicAppConfig::setValue('app.icon_version',
                DynamicAppConfig::getValue('app.icon_version', 0) + 1
            );

            return response()->json([
                'success' => true,
                'message' => 'App icon updated successfully',
                'data' => [
                    'icon_url' => $url,
                    'icon_version' => DynamicAppConfig::getValue('app.icon_version'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload icon: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update multiple configs (Admin only)
     */
    public function updateConfigs(Request $request)
    {
        ResourceAuthorizer::ensureAdmin($request->user());

        $validated = $this->validateApi($request, [
            'configs' => 'required|array|min:1|max:100',
        ]);

        $configs = $validated['configs'];

        foreach (array_keys($configs) as $key) {
            if (!preg_match('/^[a-z][a-z0-9_.]{0,99}$/', (string) $key)) {
                ApiValidator::throwResponse(['configs' => ["Invalid config key: {$key}"]]);
            }
        }

        try {
            $updated = [];

            foreach ($configs as $key => $value) {
                $config = DynamicAppConfig::where('key', $key)->first();

                if ($config) {
                    $config->value = DynamicAppConfig::encodeValue($value, $config->type);
                    $config->increment('version');
                    $config->save();

                    $updated[] = [
                        'key' => $key,
                        'value' => DynamicAppConfig::decodeValue($config->value, $config->type),
                        'version' => $config->version,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($updated) . ' configs updated successfully',
                'updated' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update configs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all configs (Admin only)
     */
    public function getAllConfigs()
    {
        ResourceAuthorizer::ensureAdmin(auth()->user());

        $configs = DynamicAppConfig::orderBy('category')
            ->orderBy('label')
            ->get()
            ->groupBy('category');

        return response()->json([
            'success' => true,
            'configs' => $configs,
        ]);
    }

    /**
     * Send test push notification (Admin only)
     */
    public function sendTestNotification(Request $request)
    {
        ResourceAuthorizer::ensureAdmin($request->user());

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // In a real implementation, this would integrate with Firebase Cloud Messaging
        // or OneSignal to send actual push notifications

        return response()->json([
            'success' => true,
            'message' => 'Test notification queued for delivery',
            'data' => [
                'title' => $request->title,
                'message' => $request->message,
                'target_user' => $request->user_id ?? 'all_users',
            ],
        ]);
    }

    /**
     * Trigger force update notification for all users
     */
    public function triggerUpdate(Request $request)
    {
        ResourceAuthorizer::ensureAdmin($request->user());

        $validator = Validator::make($request->all(), [
            'force_update' => 'required|boolean',
            'update_message' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update force_update config
        DynamicAppConfig::setValue('app.force_update', $request->force_update);

        if ($request->has('update_message')) {
            DynamicAppConfig::setValue('app.update_message', $request->update_message);
        }

        // Log the action
        \Log::info('Force update triggered', [
            'force_update' => $request->force_update,
            'update_message' => $request->update_message,
            'admin_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->force_update
                ? 'Force update enabled. Users will be prompted to update on next app open.'
                : 'Force update disabled.',
        ]);
    }

    /**
     * Get feature flags for current user
     */
    public function getFeatureFlags(Request $request)
    {
        $user = auth()->user();

        // Get all feature configs
        $features = DynamicAppConfig::ofCategory('features')
            ->public()
            ->get()
            ->map(function ($config) use ($user) {
                $featureKey = str_replace('features.', '', $config->key);
                $isEnabled = DynamicAppConfig::decodeValue($config->value, $config->type);

                // Check if user has specific permissions
                // (you can add custom logic here based on user's plan, etc.)

                return [
                    'key' => $featureKey,
                    'enabled' => $isEnabled,
                    'label' => $config->label,
                ];
            })
            ->keyBy('key')
            ->toArray();

        return response()->json([
            'success' => true,
            'features' => $features,
        ]);
    }
}
