<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FrontendConfig;

class MobileAppConfigSeeder extends Seeder
{
    /**
     * Seed mobile app configuration settings
     */
    public function run()
    {
        $configs = [
            // App Branding
            [
                'config_key' => 'mobile.app_name',
                'config_value' => 'BlinkStudy AI',
                'value_type' => 'string',
                'description' => 'Mobile app display name',
                'is_active' => true
            ],
            [
                'config_key' => 'mobile.app_logo',
                'config_value' => '',
                'value_type' => 'string',
                'description' => 'Mobile app logo path',
                'is_active' => true
            ],
            [
                'config_key' => 'mobile.app_icon',
                'config_value' => '',
                'value_type' => 'string',
                'description' => 'Mobile app icon path',
                'is_active' => true
            ],
            [
                'config_key' => 'mobile.primary_color',
                'config_value' => '#0D9488',
                'value_type' => 'string',
                'description' => 'Primary theme color (hex)',
                'is_active' => true
            ],
            [
                'config_key' => 'mobile.secondary_color',
                'config_value' => '#F59E0B',
                'value_type' => 'string',
                'description' => 'Secondary theme color (hex)',
                'is_active' => true
            ],
            [
                'config_key' => 'mobile.accent_color',
                'config_value' => '#99F6E4',
                'value_type' => 'string',
                'description' => 'Accent theme color (hex)',
                'is_active' => true
            ],
            [
                'config_key' => 'mobile.theme',
                'config_value' => 'dark',
                'value_type' => 'string',
                'description' => 'Default theme (light/dark/auto)',
                'is_active' => true
            ],
            [
                'config_key' => 'mobile.font_family',
                'config_value' => 'system',
                'value_type' => 'string',
                'description' => 'App font family',
                'is_active' => true
            ],

            // Feature Flags
            [
                'config_key' => 'mobile.features',
                'config_value' => json_encode([
                    'imageUpload' => true,
                    'fileUpload' => true,
                    'voiceInput' => true,
                    'multipleModels' => true,
                    'shareChat' => true,
                    'pdfUpload' => true,
                    'imageAnalysis' => true
                ]),
                'value_type' => 'json',
                'description' => 'Mobile app feature toggles',
                'is_active' => true
            ],

            // Screen Visibility
            [
                'config_key' => 'mobile.screens_visible',
                'config_value' => json_encode([
                    'chat' => true,
                    'settings' => true,
                    'profile' => true,
                    'plans' => true,
                    'notebook' => true
                ]),
                'value_type' => 'json',
                'description' => 'Mobile screen visibility toggles',
                'is_active' => true
            ],

            // App Update Settings
            [
                'config_key' => 'mobile.force_update',
                'config_value' => 'false',
                'value_type' => 'boolean',
                'description' => 'Force users to update app',
                'is_active' => true
            ],
            [
                'config_key' => 'mobile.min_version',
                'config_value' => '1.0.0',
                'value_type' => 'string',
                'description' => 'Minimum required app version',
                'is_active' => true
            ],
            [
                'config_key' => 'mobile.update_message',
                'config_value' => 'A new version is available. Please update to continue.',
                'value_type' => 'string',
                'description' => 'App update notification message',
                'is_active' => true
            ],
        ];

        foreach ($configs as $config) {
            FrontendConfig::updateOrCreate(
                ['config_key' => $config['config_key']],
                $config
            );
        }

        $this->command->info('✅ Mobile app configurations seeded successfully!');
    }
}
