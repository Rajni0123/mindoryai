<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Policy;
use Illuminate\Support\Facades\Config;

class PolicySeeder extends Seeder
{
    public function run(): void
    {
        $supportEmail = config('services.support_email', 'support@' . env('MAIN_DOMAIN', 'example.com'));

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
                            'content' => 'BlinkStudy AI is committed to protecting your privacy.',
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
                            'content' => 'These Terms govern your use of BlinkStudy AI services.',
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

            // 🗑️ Data Deletion / Account Deletion
            [
                'key' => 'data_deletion',
                'title' => 'Data Deletion Policy',
                'content' => json_encode([
                    'sections' => [
                        [
                            'title' => 'Last Updated',
                            'content' => date('F j, Y'),
                        ],
                        [
                            'title' => 'How to Request Account & Data Deletion',
                            'content' => "You can request deletion of your account and all associated data through any of the following methods:\n\n1. In-App: Go to Settings > Account > Delete My Account\n2. Email: Send a request to {$supportEmail} with subject 'Account Deletion Request'\n3. Contact Form: Visit our website and submit a data deletion request\n\nPlease use the email address associated with your BlinkStudy AI account when making requests.",
                        ],
                        [
                            'title' => 'What Data Gets Deleted',
                            'content' => "Upon account deletion, the following data will be permanently removed:\n\n• Your profile information (name, email, phone number)\n• Chat and doubt-solving history\n• Quiz history and performance data\n• Uploaded images, PDFs, and documents\n• Subscription and plan details\n• App preferences and settings\n• Notes and saved content\n• All AI-generated content linked to your account",
                        ],
                        [
                            'title' => 'Data Retention',
                            'content' => "Some data may be retained as required by Indian law:\n\n• Transaction/payment records: Retained for up to 7 years (as per Income Tax Act and GST regulations)\n• Legal compliance records: Retained as required by applicable laws\n\nAll retained data is anonymized and cannot be linked back to your identity.",
                        ],
                        [
                            'title' => 'Deletion Timeline',
                            'content' => "• Immediate: Your account access is disabled and you are logged out of all devices\n• Within 30 days: All personal data is permanently deleted from our active systems\n• Within 90 days: Data is removed from all backup systems\n\nYou will receive an email confirmation once deletion is complete.",
                        ],
                        [
                            'title' => 'Important Notes',
                            'content' => "• Account deletion is permanent and cannot be undone\n• If you have an active subscription, please cancel it before requesting deletion\n• Any remaining credits or plan benefits will be forfeited\n• You can create a new account after deletion, but previous data cannot be recovered",
                        ],
                        [
                            'title' => 'Contact Us',
                            'content' => "For any questions about data deletion:\n\nEmail: {$supportEmail}\nResponse Time: Within 48 hours",
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_enabled' => true,
                'order' => 6,
            ],

            // ℹ️ About
            [
                'key' => 'about',
                'title' => 'About',
                'content' => json_encode([
                    'version' => Config::get('app.version', '1.0.0'),
                    'description' => 'BlinkStudy AI is an AI-powered learning companion.',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_enabled' => true,
                'order' => 7,
            ],

            // 🆘 Support
            [
                'key' => 'support',
                'title' => 'Help & Support',
                'content' => json_encode([
                    'sections' => [
                        [
                            'title' => 'Contact',
                            'content' => "Email us at {$supportEmail}",
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_enabled' => true,
                'order' => 8,
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
