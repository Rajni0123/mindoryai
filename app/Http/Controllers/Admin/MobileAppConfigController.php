<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DynamicAppConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MobileAppConfigController extends Controller
{
    /**
     * Display mobile app configuration page
     */
    public function index()
    {
        // Get all mobile app configs from FrontendConfig (synced with mobile app API)
        $logoUrl = \App\Models\FrontendConfig::getValue('mobile.app_logo');
        $iconUrl = \App\Models\FrontendConfig::getValue('mobile.app_icon');
        $loginLogoUrl = \App\Models\FrontendConfig::getValue('auth.login.logo');
        $splashScreenUrl = \App\Models\FrontendConfig::getValue('mobile.splash_screen');

        $configs = [
            'app_name' => \App\Models\FrontendConfig::getValue('mobile.app_name', 'Mindory AI'),
            'app_logo' => $logoUrl,
            'app_icon' => $iconUrl,
            'login_logo' => $loginLogoUrl,
            'splash_screen' => $splashScreenUrl,
            'icon_version' => \App\Models\FrontendConfig::getValue('mobile.icon_version', 1),

            'primary_color' => \App\Models\FrontendConfig::getValue('mobile.primary_color', '#0D9488'),
            'secondary_color' => \App\Models\FrontendConfig::getValue('mobile.secondary_color', '#F59E0B'),
            'background_color' => \App\Models\FrontendConfig::getValue('mobile.background_color', '#F0FDFA'),
            'theme' => \App\Models\FrontendConfig::getValue('mobile.theme', 'light'),
            'font_family' => \App\Models\FrontendConfig::getValue('mobile.font_family', 'Outfit'),

            // Feature flags
            'features' => [
                'chat_enabled' => DynamicAppConfig::getValue('features.chat_enabled', true),
                'quiz_from_image' => DynamicAppConfig::getValue('features.quiz_from_image', true),
                'voice_input' => DynamicAppConfig::getValue('features.voice_input', true),
                'image_upload' => DynamicAppConfig::getValue('features.image_upload', true),
                'pdf_upload' => DynamicAppConfig::getValue('features.pdf_upload', true),
                'notebook' => DynamicAppConfig::getValue('features.notebook', true),
                'referral_system' => DynamicAppConfig::getValue('features.referral_system', true),
                'share_chat' => DynamicAppConfig::getValue('features.share_chat', true),
            ],

            // Screen visibility (from features - using same structure)
            'screens' => [
                'chat' => DynamicAppConfig::getValue('features.chat_enabled', true),
                'notebook' => DynamicAppConfig::getValue('features.notebook', false),
            ],

            // App update settings
            'latest_version' => DynamicAppConfig::getValue('app.latest_version', '1.0.0'),
            'min_supported_version' => DynamicAppConfig::getValue('app.min_supported_version', '1.0.0'),
            'force_update' => DynamicAppConfig::getValue('app.force_update', false),
            'update_message' => DynamicAppConfig::getValue('app.update_message', 'A new version is available. Please update to continue.'),
            'update_url_android' => DynamicAppConfig::getValue('app.update_url_android'),
            'update_url_ios' => DynamicAppConfig::getValue('app.update_url_ios'),

            // AI Settings
            'ai_default_model' => DynamicAppConfig::getValue('ai.default_model', 'gpt-4o-mini'),
            'ai_max_tokens' => DynamicAppConfig::getValue('ai.max_tokens', 2000),
            'ai_timeout' => DynamicAppConfig::getValue('ai.timeout_seconds', 30),

            // Credit System
            'free_tier_messages' => DynamicAppConfig::getValue('credits.free_tier_messages', 50),
            'referral_reward' => DynamicAppConfig::getValue('credits.referral_reward', 3),
            'signup_bonus' => DynamicAppConfig::getValue('credits.signup_bonus', 3),

            // Content
            'welcome_message' => DynamicAppConfig::getValue('content.welcome_message'),
            'help_url' => DynamicAppConfig::getValue('content.help_url'),
            'privacy_policy_url' => DynamicAppConfig::getValue('content.privacy_policy_url'),
            'terms_url' => DynamicAppConfig::getValue('content.terms_url'),
        ];

        return view('admin.mobile-app-config', compact('configs'));
    }

    /**
     * Update mobile app configuration (Branding & UI)
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'primary_color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'background_color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'theme' => 'required|in:light,dark,auto',
            'font_family' => 'nullable|string',
            'app_logo' => 'nullable|image|max:3072',
            'app_icon' => 'nullable|image|max:3072',
            'login_logo' => 'nullable|image|max:3072',
            'splash_screen' => 'nullable|image|max:3072',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->with('error', 'Validation failed. Please check your inputs.');
        }

        try {
            // Update basic settings using FrontendConfig (synced with mobile app API)
            \App\Models\FrontendConfig::setValue('mobile.app_name', $request->app_name);
            \App\Models\FrontendConfig::setValue('mobile.primary_color', $request->primary_color);
            \App\Models\FrontendConfig::setValue('mobile.secondary_color', $request->secondary_color ?? '#F59E0B');
            \App\Models\FrontendConfig::setValue('mobile.background_color', $request->background_color ?? '#F0FDFA');
            \App\Models\FrontendConfig::setValue('mobile.theme', $request->theme);
            \App\Models\FrontendConfig::setValue('mobile.font_family', $request->font_family ?? 'Outfit');

            // Handle logo upload - save to public directory
            if ($request->hasFile('app_logo')) {
                $oldLogo = \App\Models\FrontendConfig::getValue('mobile.app_logo');
                if ($oldLogo) {
                    $oldFile = public_path(ltrim(parse_url($oldLogo, PHP_URL_PATH), '/'));
                    if (file_exists($oldFile) && str_contains($oldLogo, '/uploads/')) {
                        @unlink($oldFile);
                    }
                }

                $file = $request->file('app_logo');
                $filename = 'app_logo_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/mobile'), $filename);
                $url = '/uploads/mobile/' . $filename;

                \App\Models\FrontendConfig::setValue('mobile.app_logo', $url);
            }

            // Handle icon upload - save to public directory
            if ($request->hasFile('app_icon')) {
                $oldIcon = \App\Models\FrontendConfig::getValue('mobile.app_icon');
                if ($oldIcon) {
                    $oldFile = public_path(ltrim(parse_url($oldIcon, PHP_URL_PATH), '/'));
                    if (file_exists($oldFile) && str_contains($oldIcon, '/uploads/')) {
                        @unlink($oldFile);
                    }
                }

                $file = $request->file('app_icon');
                $filename = 'app_icon_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/mobile'), $filename);
                $url = '/uploads/mobile/' . $filename;

                \App\Models\FrontendConfig::setValue('mobile.app_icon', $url);

                // Increment icon version to force app reload
                $currentVersion = \App\Models\FrontendConfig::getValue('mobile.icon_version', 0);
                \App\Models\FrontendConfig::setValue('mobile.icon_version', $currentVersion + 1);
            }

            // Handle login logo upload - save to public directory
            if ($request->hasFile('login_logo')) {
                $oldLoginLogo = \App\Models\FrontendConfig::getValue('auth.login.logo');
                if ($oldLoginLogo) {
                    $oldFile = public_path(ltrim(parse_url($oldLoginLogo, PHP_URL_PATH), '/'));
                    if (file_exists($oldFile) && str_contains($oldLoginLogo, '/uploads/')) {
                        @unlink($oldFile);
                    }
                }

                $file = $request->file('login_logo');
                $filename = 'login_logo_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/mobile'), $filename);
                $url = '/uploads/mobile/' . $filename;

                \App\Models\FrontendConfig::setValue('auth.login.logo', $url);
            }

            // Handle splash screen upload - save to public directory
            if ($request->hasFile('splash_screen')) {
                $oldSplash = \App\Models\FrontendConfig::getValue('mobile.splash_screen');
                if ($oldSplash) {
                    $oldFile = public_path(ltrim(parse_url($oldSplash, PHP_URL_PATH), '/'));
                    if (file_exists($oldFile) && str_contains($oldSplash, '/uploads/')) {
                        @unlink($oldFile);
                    }
                }

                $file = $request->file('splash_screen');
                $filename = 'splash_screen_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/mobile'), $filename);
                $url = '/uploads/mobile/' . $filename;

                \App\Models\FrontendConfig::setValue('mobile.splash_screen', $url);
            }

            $iconVersion = \App\Models\FrontendConfig::getValue('mobile.icon_version', 1);
            return redirect()->back()->with('success', 'Mobile app branding updated successfully! Icon version: v' . $iconVersion);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update configuration: ' . $e->getMessage());
        }
    }

    /**
     * Update feature toggles
     */
    public function updateFeatures(Request $request)
    {
        try {
            // Update each feature individually
            DynamicAppConfig::setValue('features.chat_enabled', $request->boolean('feature_chat_enabled'), 'boolean');
            DynamicAppConfig::setValue('features.quiz_from_image', $request->boolean('feature_quiz_from_image'), 'boolean');
            DynamicAppConfig::setValue('features.voice_input', $request->boolean('feature_voice_input'), 'boolean');
            DynamicAppConfig::setValue('features.image_upload', $request->boolean('feature_image_upload'), 'boolean');
            DynamicAppConfig::setValue('features.pdf_upload', $request->boolean('feature_pdf_upload'), 'boolean');
            DynamicAppConfig::setValue('features.notebook', $request->boolean('feature_notebook'), 'boolean');
            DynamicAppConfig::setValue('features.referral_system', $request->boolean('feature_referral_system'), 'boolean');
            DynamicAppConfig::setValue('features.share_chat', $request->boolean('feature_share_chat'), 'boolean');

            return redirect()->back()->with('success', 'Feature toggles updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update features: ' . $e->getMessage());
        }
    }

    /**
     * Update screen visibility
     */
    public function updateScreens(Request $request)
    {
        try {
            // Since screens are controlled by features in the new system
            // We update the corresponding feature flags
            DynamicAppConfig::setValue('features.chat_enabled', $request->boolean('screen_chat'), 'boolean');
            DynamicAppConfig::setValue('features.notebook', $request->boolean('screen_notebook'), 'boolean');

            return redirect()->back()->with('success', 'Screen visibility updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update screen visibility: ' . $e->getMessage());
        }
    }

    /**
     * Update app version and update settings
     */
    public function updateVersion(Request $request)
    {
        $validated = $request->validate([
            'latest_version' => 'required|string|regex:/^\d+\.\d+\.\d+$/',
            'min_supported_version' => 'nullable|string|regex:/^\d+\.\d+\.\d+$/',
            'force_update' => 'boolean',
            'update_message' => 'nullable|string|max:500',
            'update_url_android' => 'nullable|url',
            'update_url_ios' => 'nullable|url',
        ]);

        try {
            DynamicAppConfig::setValue('app.latest_version', $request->latest_version);
            DynamicAppConfig::setValue('app.min_supported_version', $request->min_supported_version ?? $request->latest_version);
            DynamicAppConfig::setValue('app.force_update', $request->boolean('force_update'), 'boolean');
            DynamicAppConfig::setValue('app.update_message', $request->update_message ?? 'A new version is available.');
            DynamicAppConfig::setValue('app.update_url_android', $request->update_url_android);
            DynamicAppConfig::setValue('app.update_url_ios', $request->update_url_ios);

            return redirect()->back()->with('success', 'App update settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update version settings: ' . $e->getMessage());
        }
    }

    /**
     * Update AI settings
     */
    public function updateAiSettings(Request $request)
    {
        $validated = $request->validate([
            'ai_default_model' => 'required|string',
            'ai_max_tokens' => 'required|integer|min:100|max:8000',
            'ai_timeout' => 'required|integer|min:10|max:120',
        ]);

        try {
            DynamicAppConfig::setValue('ai.default_model', $request->ai_default_model);
            DynamicAppConfig::setValue('ai.max_tokens', $request->ai_max_tokens, 'integer');
            DynamicAppConfig::setValue('ai.timeout_seconds', $request->ai_timeout, 'integer');

            return redirect()->back()->with('success', 'AI settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update AI settings: ' . $e->getMessage());
        }
    }

    /**
     * Update credit system settings
     */
    public function updateCredits(Request $request)
    {
        $validated = $request->validate([
            'free_tier_messages' => 'required|integer|min:0|max:500',
            'referral_reward' => 'required|integer|min:0|max:100',
            'signup_bonus' => 'required|integer|min:0|max:100',
        ]);

        try {
            DynamicAppConfig::setValue('credits.free_tier_messages', $request->free_tier_messages, 'integer');
            DynamicAppConfig::setValue('credits.referral_reward', $request->referral_reward, 'integer');
            DynamicAppConfig::setValue('credits.signup_bonus', $request->signup_bonus, 'integer');

            return redirect()->back()->with('success', 'Credit system settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update credit settings: ' . $e->getMessage());
        }
    }

    /**
     * Update content settings
     */
    public function updateContent(Request $request)
    {
        $validated = $request->validate([
            'welcome_message' => 'nullable|string|max:500',
            'help_url' => 'nullable|url',
            'privacy_policy_url' => 'nullable|url',
            'terms_url' => 'nullable|url',
        ]);

        try {
            DynamicAppConfig::setValue('content.welcome_message', $request->welcome_message);
            DynamicAppConfig::setValue('content.help_url', $request->help_url);
            DynamicAppConfig::setValue('content.privacy_policy_url', $request->privacy_policy_url);
            DynamicAppConfig::setValue('content.terms_url', $request->terms_url);

            return redirect()->back()->with('success', 'Content settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update content settings: ' . $e->getMessage());
        }
    }

    /**
     * Trigger force update for all users
     */
    public function triggerForceUpdate(Request $request)
    {
        $validated = $request->validate([
            'force_update' => 'required|boolean',
            'update_message' => 'nullable|string|max:500',
        ]);

        try {
            DynamicAppConfig::setValue('app.force_update', $request->force_update, 'boolean');

            if ($request->has('update_message')) {
                DynamicAppConfig::setValue('app.update_message', $request->update_message);
            }

            $message = $request->force_update
                ? 'Force update enabled! Users will be prompted to update on next app open.'
                : 'Force update disabled.';

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to trigger force update: ' . $e->getMessage());
        }
    }
}
