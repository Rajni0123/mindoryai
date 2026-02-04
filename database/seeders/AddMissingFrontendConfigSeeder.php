<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddMissingFrontendConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $missingConfigs = [
            // Hero Section
            'hero_title' => 'Learn Smarter with AI',
            'hero_subtitle' => 'Your personal AI study companion for NCERT and exam preparation',
            'hero_description' => 'Pose any question, explore any concept, and get instant answers from your AI mentor',
            'hero_cta_text' => 'Get Started Free',
            'hero_cta_link' => '/register',

            // Feature Section
            'feature_section_title' => 'Powerful Features',
            'feature_section_description' => 'Experience cutting-edge AI technology designed specifically for Indian students',

            // Logo Settings
            'site_logo' => '',
            'logo_width' => '40',
            'logo_height' => '40',
            'chat_sidebar_logo' => '',
            'chat_sidebar_logo_height' => '32',

            // Site Settings
            'ip_whitelist_enabled' => '0',
            'ip_whitelist' => '',
            'site_name' => 'BlinkStudy',
            'maintenance_mode' => '0',
            'maintenance_message' => 'We are currently performing scheduled maintenance. Please check back soon.',

            // Sections Visibility
            'features_enabled' => '1',
            'show_pricing' => '1',
            'show_pricing_section' => '1',
            'show_testimonials' => '1',
            'show_footer' => '1',
        ];

        foreach ($missingConfigs as $key => $value) {
            $exists = DB::table('frontend_configs')
                ->where('config_key', $key)
                ->exists();

            if (!$exists) {
                DB::table('frontend_configs')->insert([
                    'config_key' => $key,
                    'config_value' => $value,
                    'value_type' => 'string',
                    'description' => "Site {$key} configuration",
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->info("✅ Added config: {$key}");
            } else {
                $this->command->info("⚠️  Config already exists: {$key}");
            }
        }

        $this->command->info("\n✅ Missing frontend configurations added successfully!");
    }
}
