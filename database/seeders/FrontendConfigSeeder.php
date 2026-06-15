<?php

namespace Database\Seeders;

use App\Models\FrontendConfig;
use Illuminate\Database\Seeder;

class FrontendConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            // App Behavior
            [
                'config_key' => 'maintenance_mode',
                'config_value' => 'false',
                'value_type' => 'boolean',
                'description' => 'Enable maintenance mode to show maintenance page',
                'is_active' => true
            ],
            [
                'config_key' => 'show_banner',
                'config_value' => 'true',
                'value_type' => 'boolean',
                'description' => 'Show promotional banner on homepage',
                'is_active' => true
            ],
            [
                'config_key' => 'enable_new_chat_ui',
                'config_value' => 'true',
                'value_type' => 'boolean',
                'description' => 'Enable new chat interface design',
                'is_active' => true
            ],

            // Text Content
            [
                'config_key' => 'header_text',
                'config_value' => 'Welcome to BlinkStudy',
                'value_type' => 'string',
                'description' => 'Header text shown on homepage',
                'is_active' => true
            ],
            [
                'config_key' => 'banner_message',
                'config_value' => '50% off on all plans! Limited time offer.',
                'value_type' => 'string',
                'description' => 'Promotional banner message',
                'is_active' => true
            ],
            [
                'config_key' => 'maintenance_message',
                'config_value' => 'We are currently under maintenance. Please check back soon!',
                'value_type' => 'string',
                'description' => 'Message displayed during maintenance mode',
                'is_active' => true
            ],

            // Numeric Values
            [
                'config_key' => 'chat_message_limit',
                'config_value' => '100',
                'value_type' => 'number',
                'description' => 'Maximum messages per conversation',
                'is_active' => true
            ],
            [
                'config_key' => 'max_file_upload_size',
                'config_value' => '10',
                'value_type' => 'number',
                'description' => 'Maximum file upload size in MB',
                'is_active' => true
            ],

            // JSON Configurations
            [
                'config_key' => 'supported_file_types',
                'config_value' => json_encode(['pdf', 'jpg', 'jpeg', 'png', 'webp']),
                'value_type' => 'json',
                'description' => 'List of supported file upload types',
                'is_active' => true
            ],
            [
                'config_key' => 'feature_flags',
                'config_value' => json_encode([
                    'voice_input' => true,
                    'pdf_upload' => true,
                    'image_analysis' => true,
                    'share_chat' => true
                ]),
                'value_type' => 'json',
                'description' => 'Feature flags to enable/disable features',
                'is_active' => true
            ],
            [
                'config_key' => 'theme_colors',
                'config_value' => json_encode([
                    'primary' => '#0D9488',
                    'secondary' => '#F59E0B',
                    'accent' => '#99F6E4'
                ]),
                'value_type' => 'json',
                'description' => 'Theme color configuration',
                'is_active' => true
            ],

            // Mobile App Specific
            [
                'config_key' => 'force_app_update',
                'config_value' => 'false',
                'value_type' => 'boolean',
                'description' => 'Force users to update mobile app',
                'is_active' => true
            ],
            [
                'config_key' => 'min_app_version',
                'config_value' => '1.0.0',
                'value_type' => 'string',
                'description' => 'Minimum required app version',
                'is_active' => true
            ],
            [
                'config_key' => 'app_update_message',
                'config_value' => 'A new version of the app is available. Please update to continue.',
                'value_type' => 'string',
                'description' => 'Message shown when app update is required',
                'is_active' => true
            ],

            // SEO Settings
            [
                'config_key' => 'seo.site_title',
                'config_value' => 'BlinkStudy - AI Study Companion for CBSE, ICSE & Competitive Exams',
                'value_type' => 'string',
                'description' => 'Site title for browser tab and Google search results',
                'is_active' => true
            ],
            [
                'config_key' => 'seo.meta_description',
                'config_value' => 'BlinkStudy is India\'s #1 AI-powered study app. Get instant doubt solving, AI quizzes, video explanations & personalized learning for CBSE, ICSE & competitive exams. Free for students!',
                'value_type' => 'string',
                'description' => 'Meta description for Google search results (max 160 chars)',
                'is_active' => true
            ],
            [
                'config_key' => 'seo.meta_keywords',
                'config_value' => 'BlinkStudy, AI study app, doubt solving app, CBSE study app, ICSE study app, AI tutor India, online learning, free study app, AI quiz, exam preparation, Class 10, Class 12, JEE, NEET, competitive exam app, BlinkStudy AI',
                'value_type' => 'string',
                'description' => 'Meta keywords for SEO (comma separated)',
                'is_active' => true
            ],
            [
                'config_key' => 'seo.favicon_url',
                'config_value' => 'icons/favicon_1768401530.png',
                'value_type' => 'string',
                'description' => 'Favicon URL path',
                'is_active' => true
            ],
            [
                'config_key' => 'seo.og_image',
                'config_value' => '',
                'value_type' => 'string',
                'description' => 'Open Graph image for social media sharing (1200x630)',
                'is_active' => true
            ],
            [
                'config_key' => 'seo.google_site_verification',
                'config_value' => '',
                'value_type' => 'string',
                'description' => 'Google Search Console verification code',
                'is_active' => true
            ],
            [
                'config_key' => 'seo.google_analytics_id',
                'config_value' => '',
                'value_type' => 'string',
                'description' => 'Google Analytics tracking ID (G-XXXXXXX)',
                'is_active' => true
            ],
            [
                'config_key' => 'seo.twitter_handle',
                'config_value' => '',
                'value_type' => 'string',
                'description' => 'Twitter/X handle (without @)',
                'is_active' => true
            ],
        ];

        foreach ($configs as $config) {
            FrontendConfig::updateOrCreate(
                ['config_key' => $config['config_key']],
                $config
            );
        }
    }
}
