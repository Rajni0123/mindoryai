<?php

namespace Database\Seeders;

use App\Models\DynamicAppConfig;
use Illuminate\Database\Seeder;

class DynamicAppConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            // ============================
            // APP BRANDING & ICONS
            // ============================
            [
                'key' => 'app.icon_url',
                'type' => 'string',
                'value' => asset('mobile-app/assets/icon.png'),
                'label' => 'App Icon URL',
                'category' => 'branding',
                'description' => 'URL of the app icon (512x512 PNG)',
                'is_public' => true,
            ],
            [
                'key' => 'app.icon_version',
                'type' => 'integer',
                'value' => 1,
                'label' => 'App Icon Version',
                'category' => 'branding',
                'description' => 'Increment this to force app to reload icon',
                'is_public' => true,
            ],
            [
                'key' => 'app.logo_url',
                'type' => 'string',
                'value' => asset('mobile-app/assets/icon.png'),
                'label' => 'App Logo URL',
                'category' => 'branding',
                'description' => 'URL of the login screen logo',
                'is_public' => true,
            ],
            [
                'key' => 'app.splash_icon_url',
                'type' => 'string',
                'value' => asset('mobile-app/assets/icon.png'),
                'label' => 'Splash Screen Icon',
                'category' => 'branding',
                'description' => 'URL of splash screen icon',
                'is_public' => true,
            ],

            // ============================
            // APP VERSION & UPDATES
            // ============================
            [
                'key' => 'app.latest_version',
                'type' => 'string',
                'value' => '1.0.0',
                'label' => 'Latest App Version',
                'category' => 'updates',
                'description' => 'Current latest version of the app',
                'is_public' => true,
            ],
            [
                'key' => 'app.min_supported_version',
                'type' => 'string',
                'value' => '1.0.0',
                'label' => 'Minimum Supported Version',
                'category' => 'updates',
                'description' => 'Oldest version that can still use the app',
                'is_public' => true,
            ],
            [
                'key' => 'app.force_update',
                'type' => 'boolean',
                'value' => false,
                'label' => 'Force Update Required',
                'category' => 'updates',
                'description' => 'If true, users must update to continue using',
                'is_public' => true,
            ],
            [
                'key' => 'app.update_message',
                'type' => 'string',
                'value' => 'A new version is available. Please update for the best experience.',
                'label' => 'Update Message',
                'category' => 'updates',
                'description' => 'Message shown to users when update is available',
                'is_public' => true,
            ],
            [
                'key' => 'app.update_url_android',
                'type' => 'string',
                'value' => 'https://play.google.com/store/apps/details?id=com.blinkstudy.app',
                'label' => 'Android Update URL',
                'category' => 'updates',
                'description' => 'Play Store URL for app updates',
                'is_public' => true,
            ],
            [
                'key' => 'app.update_url_ios',
                'type' => 'string',
                'value' => 'https://apps.apple.com/app/blinkstudy',
                'label' => 'iOS Update URL',
                'category' => 'updates',
                'description' => 'App Store URL for app updates',
                'is_public' => true,
            ],

            // ============================
            // APP UI & THEMING
            // ============================
            [
                'key' => 'ui.primary_color',
                'type' => 'string',
                'value' => '#0df259',
                'label' => 'Primary Color',
                'category' => 'ui',
                'description' => 'Main brand color (hex code)',
                'is_public' => true,
            ],
            [
                'key' => 'ui.secondary_color',
                'type' => 'string',
                'value' => '#3b82f6',
                'label' => 'Secondary Color',
                'category' => 'ui',
                'description' => 'Secondary accent color (hex code)',
                'is_public' => true,
            ],
            [
                'key' => 'ui.background_color',
                'type' => 'string',
                'value' => '#0a0a0a',
                'label' => 'Background Color',
                'category' => 'ui',
                'description' => 'App background color (hex code)',
                'is_public' => true,
            ],
            [
                'key' => 'ui.theme',
                'type' => 'string',
                'value' => 'dark',
                'label' => 'App Theme',
                'category' => 'ui',
                'description' => 'Default theme: light, dark, or auto',
                'is_public' => true,
            ],
            [
                'key' => 'ui.font_family',
                'type' => 'string',
                'value' => 'system',
                'label' => 'Font Family',
                'category' => 'ui',
                'description' => 'Default font: system, inter, roboto, etc.',
                'is_public' => true,
            ],

            // ============================
            // FEATURE FLAGS
            // ============================
            [
                'key' => 'features.chat_enabled',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Chat Feature',
                'category' => 'features',
                'description' => 'Enable/disable AI chat feature',
                'is_public' => true,
            ],
            [
                'key' => 'features.quiz_from_image',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Quiz from Image',
                'category' => 'features',
                'description' => 'Enable quiz generation from images',
                'is_public' => true,
            ],
            [
                'key' => 'features.voice_input',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Voice Input',
                'category' => 'features',
                'description' => 'Enable voice-to-text input',
                'is_public' => true,
            ],
            [
                'key' => 'features.image_upload',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Image Upload',
                'category' => 'features',
                'description' => 'Enable image upload in chat',
                'is_public' => true,
            ],
            [
                'key' => 'features.pdf_upload',
                'type' => 'boolean',
                'value' => true,
                'label' => 'PDF Upload',
                'category' => 'features',
                'description' => 'Enable PDF document upload',
                'is_public' => true,
            ],
            [
                'key' => 'features.notebook',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Notebook Feature',
                'category' => 'features',
                'description' => 'Enable notebook/document scanning',
                'is_public' => true,
            ],
            [
                'key' => 'features.referral_system',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Referral System',
                'category' => 'features',
                'description' => 'Enable referral code system',
                'is_public' => true,
            ],
            [
                'key' => 'features.share_chat',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Share Chat',
                'category' => 'features',
                'description' => 'Enable chat sharing feature',
                'is_public' => true,
            ],

            // ============================
            // CONTENT & MARKETING
            // ============================
            [
                'key' => 'content.welcome_message',
                'type' => 'string',
                'value' => 'Welcome to BlinkStudy AI! Your personal AI tutor for homework, notes, and quiz preparation.',
                'label' => 'Welcome Message',
                'category' => 'content',
                'description' => 'First-time user welcome message',
                'is_public' => true,
            ],
            [
                'key' => 'content.onboarding_steps',
                'type' => 'json',
                'value' => json_encode([
                    ['title' => 'Chat with AI', 'description' => 'Ask any question and get instant answers'],
                    ['title' => 'Scan Notes', 'description' => 'Upload photos of your notes for AI analysis'],
                    ['title' => 'Generate Quizzes', 'description' => 'Create quizzes from images and notes'],
                    ['title' => 'Earn Rewards', 'description' => 'Refer friends and earn free credits'],
                ]),
                'label' => 'Onboarding Steps',
                'category' => 'content',
                'description' => 'App tutorial steps for new users',
                'is_public' => true,
            ],
            [
                'key' => 'content.help_url',
                'type' => 'string',
                'value' => config('app.url') . '/help',
                'label' => 'Help Center URL',
                'category' => 'content',
                'description' => 'Link to help/support page',
                'is_public' => true,
            ],
            [
                'key' => 'content.privacy_policy_url',
                'type' => 'string',
                'value' => config('app.url') . '/privacy',
                'label' => 'Privacy Policy URL',
                'category' => 'content',
                'description' => 'Link to privacy policy',
                'is_public' => true,
            ],
            [
                'key' => 'content.terms_url',
                'type' => 'string',
                'value' => config('app.url') . '/terms',
                'label' => 'Terms of Service URL',
                'category' => 'content',
                'description' => 'Link to terms of service',
                'is_public' => true,
            ],

            // ============================
            // AI MODEL SETTINGS
            // ============================
            [
                'key' => 'ai.default_model',
                'type' => 'string',
                'value' => 'gpt-4o-mini',
                'label' => 'Default AI Model',
                'category' => 'ai',
                'description' => 'Default AI model for chat',
                'is_public' => true,
            ],
            [
                'key' => 'ai.max_tokens',
                'type' => 'integer',
                'value' => 2000,
                'label' => 'Max Tokens',
                'category' => 'ai',
                'description' => 'Maximum tokens for AI responses',
                'is_public' => true,
            ],
            [
                'key' => 'ai.timeout_seconds',
                'type' => 'integer',
                'value' => 30,
                'label' => 'AI Timeout',
                'category' => 'ai',
                'description' => 'AI response timeout in seconds',
                'is_public' => false,
            ],

            // ============================
            // CREDIT SYSTEM
            // ============================
            [
                'key' => 'credits.free_tier_messages',
                'type' => 'integer',
                'value' => 50,
                'label' => 'Free Tier Messages',
                'category' => 'credits',
                'description' => 'Number of free messages for free users',
                'is_public' => true,
            ],
            [
                'key' => 'credits.referral_reward',
                'type' => 'integer',
                'value' => 3,
                'label' => 'Referral Reward',
                'category' => 'credits',
                'description' => 'Credits earned per successful referral',
                'is_public' => true,
            ],
            [
                'key' => 'credits.signup_bonus',
                'type' => 'integer',
                'value' => 3,
                'label' => 'Signup Bonus',
                'category' => 'credits',
                'description' => 'Free credits for new users',
                'is_public' => true,
            ],

            // ============================
            // ADS CONFIGURATION
            // ============================
            [
                'key' => 'ads.enabled',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Ads Enabled',
                'category' => 'ads',
                'description' => 'Master switch to enable/disable all ads',
                'is_public' => true,
            ],
            [
                'key' => 'ads.admob_app_id_android',
                'type' => 'string',
                'value' => 'ca-app-pub-3940256099942544~3347511713',
                'label' => 'AdMob App ID (Android)',
                'category' => 'ads',
                'description' => 'Google AdMob Android App ID (current: test ID)',
                'is_public' => false,
            ],
            [
                'key' => 'ads.admob_app_id_ios',
                'type' => 'string',
                'value' => 'ca-app-pub-3940256099942544~1458002511',
                'label' => 'AdMob App ID (iOS)',
                'category' => 'ads',
                'description' => 'Google AdMob iOS App ID (current: test ID)',
                'is_public' => false,
            ],
            [
                'key' => 'ads.banner_id_android',
                'type' => 'string',
                'value' => 'ca-app-pub-3940256099942544/6300978111',
                'label' => 'Banner Ad ID (Android)',
                'category' => 'ads',
                'description' => 'AdMob banner unit ID for Android (current: test ID)',
                'is_public' => true,
            ],
            [
                'key' => 'ads.banner_id_ios',
                'type' => 'string',
                'value' => 'ca-app-pub-3940256099942544/2934735716',
                'label' => 'Banner Ad ID (iOS)',
                'category' => 'ads',
                'description' => 'AdMob banner unit ID for iOS (current: test ID)',
                'is_public' => true,
            ],
            [
                'key' => 'ads.interstitial_id_android',
                'type' => 'string',
                'value' => 'ca-app-pub-3940256099942544/1033173712',
                'label' => 'Interstitial Ad ID (Android)',
                'category' => 'ads',
                'description' => 'AdMob interstitial unit ID for Android (current: test ID)',
                'is_public' => true,
            ],
            [
                'key' => 'ads.interstitial_id_ios',
                'type' => 'string',
                'value' => 'ca-app-pub-3940256099942544/4411468910',
                'label' => 'Interstitial Ad ID (iOS)',
                'category' => 'ads',
                'description' => 'AdMob interstitial unit ID for iOS (current: test ID)',
                'is_public' => true,
            ],
            [
                'key' => 'ads.rewarded_id_android',
                'type' => 'string',
                'value' => 'ca-app-pub-3940256099942544/5224354917',
                'label' => 'Rewarded Ad ID (Android)',
                'category' => 'ads',
                'description' => 'AdMob rewarded unit ID for Android (current: test ID)',
                'is_public' => true,
            ],
            [
                'key' => 'ads.rewarded_id_ios',
                'type' => 'string',
                'value' => 'ca-app-pub-3940256099942544/1712485313',
                'label' => 'Rewarded Ad ID (iOS)',
                'category' => 'ads',
                'description' => 'AdMob rewarded unit ID for iOS (current: test ID)',
                'is_public' => true,
            ],
            [
                'key' => 'ads.banner_enabled',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Banner Ads Enabled',
                'category' => 'ads',
                'description' => 'Enable banner ads for free users',
                'is_public' => true,
            ],
            [
                'key' => 'ads.interstitial_enabled',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Interstitial Ads Enabled',
                'category' => 'ads',
                'description' => 'Enable interstitial ads for free users',
                'is_public' => true,
            ],
            [
                'key' => 'ads.rewarded_enabled',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Rewarded Ads Enabled',
                'category' => 'ads',
                'description' => 'Enable rewarded ads for earning credits',
                'is_public' => true,
            ],
            [
                'key' => 'ads.interstitial_frequency',
                'type' => 'integer',
                'value' => 3,
                'label' => 'Interstitial Frequency',
                'category' => 'ads',
                'description' => 'Show interstitial ad every N actions',
                'is_public' => true,
            ],
            [
                'key' => 'ads.rewarded_credits',
                'type' => 'integer',
                'value' => 5,
                'label' => 'Rewarded Ad Credits',
                'category' => 'ads',
                'description' => 'Credits earned per rewarded ad watch',
                'is_public' => true,
            ],

            // ============================
            // DAILY CHALLENGE
            // ============================
            [
                'key' => 'features.daily_challenge_enabled',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Daily Challenge',
                'category' => 'features',
                'description' => 'Enable/disable daily challenge feature',
                'is_public' => true,
            ],
            [
                'key' => 'daily_challenge.questions_count',
                'type' => 'integer',
                'value' => 5,
                'label' => 'Questions Per Challenge',
                'category' => 'daily_challenge',
                'description' => 'Number of questions in each daily challenge',
                'is_public' => true,
            ],
            [
                'key' => 'daily_challenge.time_limit_seconds',
                'type' => 'integer',
                'value' => 300,
                'label' => 'Time Limit (seconds)',
                'category' => 'daily_challenge',
                'description' => 'Time limit for completing the daily challenge',
                'is_public' => true,
            ],
            [
                'key' => 'daily_challenge.reward_credits',
                'type' => 'integer',
                'value' => 2,
                'label' => 'Reward Credits',
                'category' => 'daily_challenge',
                'description' => 'Credits earned for completing daily challenge',
                'is_public' => true,
            ],
            [
                'key' => 'daily_challenge.streak_bonus_credits',
                'type' => 'integer',
                'value' => 5,
                'label' => 'Streak Bonus Credits',
                'category' => 'daily_challenge',
                'description' => 'Bonus credits for 7-day streak',
                'is_public' => true,
            ],
            [
                'key' => 'daily_challenge.difficulty',
                'type' => 'string',
                'value' => 'mixed',
                'label' => 'Default Difficulty',
                'category' => 'daily_challenge',
                'description' => 'Default difficulty: easy, medium, hard, mixed',
                'is_public' => true,
            ],
            [
                'key' => 'daily_challenge.subjects',
                'type' => 'json',
                'value' => json_encode(['Mathematics', 'Science', 'English', 'Social Studies', 'General Knowledge']),
                'label' => 'Challenge Subjects',
                'category' => 'daily_challenge',
                'description' => 'Subjects available for daily challenges',
                'is_public' => true,
            ],

            // ============================
            // NOTIFICATIONS
            // ============================
            [
                'key' => 'notifications.enabled',
                'type' => 'boolean',
                'value' => true,
                'label' => 'Push Notifications',
                'category' => 'notifications',
                'description' => 'Enable push notifications',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.promotional_enabled',
                'type' => 'boolean',
                'value' => false,
                'label' => 'Promotional Notifications',
                'category' => 'notifications',
                'description' => 'Send promotional notifications',
                'is_public' => false,
            ],
        ];

        foreach ($configs as $config) {
            DynamicAppConfig::updateOrCreate(
                ['key' => $config['key']],
                [
                    'type' => $config['type'],
                    'value' => $config['value'],
                    'label' => $config['label'],
                    'category' => $config['category'],
                    'description' => $config['description'],
                    'is_public' => $config['is_public'],
                ]
            );
        }

        $this->command->info('Dynamic app configs seeded successfully!');
        $this->command->info('Total configs: ' . count($configs));
    }
}
