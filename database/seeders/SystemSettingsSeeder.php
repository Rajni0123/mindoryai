<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Authentication Settings
        SystemSetting::set(
            'auth.email_login_enabled',
            'true',
            'boolean',
            'auth',
            'Enable/disable email and password login for users'
        );

        SystemSetting::set(
            'auth.google_login_enabled',
            'true',
            'boolean',
            'auth',
            'Enable/disable Google OAuth login for users'
        );

        // General Settings
        SystemSetting::set(
            'general.site_name',
            'BlinkStudy',
            'string',
            'general',
            'Website name'
        );

        SystemSetting::set(
            'general.site_description',
            'AI-powered library system',
            'string',
            'general',
            'Website description'
        );

        echo "Default system settings created:\n";
        echo "- Email login: enabled\n";
        echo "- Google login: enabled\n";
    }
}
