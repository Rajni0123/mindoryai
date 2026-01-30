<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Policy;
use Illuminate\Support\Facades\Config;

class PolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [

            // 🔐 Privacy Policy
            [
                'key' => 'privacy_policy',
                'title' => 'Privacy Policy',
                'content' => json_encode([
                    'sections' => [
                        [
                            'title' => 'Last Updated',
                            'content' => date('F j, Y'),
                        ],
                        [
                            'title' => 'Introduction',
                            'content' => 'Mindory AI is committed to protecting your privacy.',
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_enabled' => true,
                'order' => 1,
            ],

            // 📜 Terms & Conditions
            [
                'key' => 'terms_of_service',
                'title' => 'Terms & Conditions',
                'content' => json_encode([
                    'sections' => [
                        [
                            'title' => 'Last Updated',
                            'content' => '05-01-2026',
                        ],
                        [
                            'title' => 'Terms',
                            'content' => 'These Terms govern your use of Mindory AI services.',
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_enabled' => true,
                'order' => 2,
            ],

            // 💰 Refund Policy
            [
                'key' => 'refund_policy',
                'title' => 'Refund Policy',
                'content' => json_encode([
                    'sections' => [
                        [
                            'title' => 'Overview',
                            'content' => 'All sales are final unless stated otherwise.',
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_enabled' => true,
                'order' => 3,
            ],

            // ❌ Cancellation Policy
            [
                'key' => 'cancellation_policy',
                'title' => 'Cancellation Policy',
                'content' => json_encode([
                    'sections' => [
                        [
                            'title' => 'Cancellation',
                            'content' => 'Cancellations must be requested immediately.',
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_enabled' => true,
                'order' => 4,
            ],

            // 🍪 Cookie Policy
            [
                'key' => 'cookie_policy',
                'title' => 'Cookie Policy',
                'content' => json_encode([
                    'sections' => [
                        [
                            'title' => 'Cookies',
                            'content' => 'We use cookies to improve user experience.',
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_enabled' => true,
                'order' => 5,
            ],

            // ℹ️ About
            [
                'key' => 'about',
                'title' => 'About',
                'content' => json_encode([
                    'version' => Config::get('app.version', '1.0.0'),
                    'description' => 'Mindory AI is an AI-powered learning companion.',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_enabled' => true,
                'order' => 6,
            ],

            // 🆘 Support
            [
                'key' => 'support',
                'title' => 'Help & Support',
                'content' => json_encode([
                    'sections' => [
                        [
                            'title' => 'Contact',
                            'content' => 'Email us at support@mindory.in',
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_enabled' => true,
                'order' => 7,
            ],
        ];

        foreach ($policies as $policy) {
            Policy::updateOrCreate(
                ['key' => $policy['key']],
                $policy
            );
        }

        $this->command?->info('✅ PolicySeeder executed successfully.');
    }
}
