<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'title' => 'AI-Powered Chat',
                'description' => 'Engage in intelligent conversations with advanced AI models. Get instant answers to your questions with context-aware responses.',
                'icon' => 'chat',
                'color' => 'blue',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Image Analysis',
                'description' => 'Upload images and get detailed AI-powered analysis. Extract text, identify objects, and understand visual content effortlessly.',
                'icon' => 'image',
                'color' => 'violet',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Multiple AI Models',
                'description' => 'Access GPT-4, Claude, DeepSeek, and Grok AI models. Choose the best model for your specific needs and tasks.',
                'icon' => 'psychology',
                'color' => 'blue',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Secure & Private',
                'description' => 'Your data is protected with enterprise-grade security. IP whitelisting and token-based authentication ensure safe access.',
                'icon' => 'security',
                'color' => 'violet',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Usage Tracking',
                'description' => 'Monitor your AI usage with detailed analytics. Track token consumption, API calls, and image processing in real-time.',
                'icon' => 'analytics',
                'color' => 'blue',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'Flexible Plans',
                'description' => 'Choose from Free, Basic, Pro, or Enterprise plans. Scale your usage based on your needs with customizable limits.',
                'icon' => 'workspace_premium',
                'color' => 'violet',
                'order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(
                ['title' => $feature['title']],
                $feature
            );
        }
    }
}
