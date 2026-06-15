<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run: php artisan db:seed
     */
    public function run(): void
    {
        $this->call([
            // Core system seeders
            AdminUserSeeder::class,

            // AI Models and Settings
            AiModelsSeeder::class,
            AiSettingsSeeder::class,

            // Subscription Plans and Features (REQUIRED for feature limits to work)
            SubscriptionPlansSeeder::class,

            // User Plans (Legacy - kept for backward compatibility)
            UserPlanSeeder::class,

            // Frontend configuration
            FrontendConfigSeeder::class,

            // Auth configuration
            AuthConfigSeeder::class,

            // Homepage settings
            HomepageSettingsSeeder::class,

            // Dynamic app config
            DynamicAppConfigSeeder::class,

            // Mobile app config
            MobileAppConfigSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('=============================================');
        $this->command->info(' Database seeding completed successfully!');
        $this->command->info('=============================================');
        $this->command->info('');
        $this->command->warn('IMPORTANT: Configure AI API keys in Admin Panel:');
        $this->command->warn('  1. Go to Admin Panel → AI Settings');
        $this->command->warn('  2. Enable Google Gemini and add your API key');
        $this->command->warn('  3. (Optional) Enable OpenAI as fallback');
        $this->command->info('');
    }
}
