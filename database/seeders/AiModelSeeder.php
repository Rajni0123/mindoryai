<?php

namespace Database\Seeders;

use App\Models\AiModel;
use Illuminate\Database\Seeder;

class AiModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            [
                'name' => 'GPT-4o',
                'provider' => 'openai',
                'model_identifier' => 'gpt-4o',
                'api_url' => 'https://api.openai.com/v1/chat/completions',
                'description' => 'OpenAI\'s most advanced multimodal model',
                'icon' => 'auto_awesome',
                'color' => '#10a37f',
                'is_active' => true,
                'supports_vision' => true,
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'order' => 1,
            ],
            [
                'name' => 'Claude Sonnet 4',
                'provider' => 'anthropic',
                'model_identifier' => 'claude-sonnet-4-20250514',
                'api_url' => 'https://api.anthropic.com/v1/messages',
                'description' => 'Anthropic\'s balanced model for complex tasks',
                'icon' => 'psychology',
                'color' => '#9d5bff',
                'is_active' => true,
                'supports_vision' => true,
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'order' => 2,
            ],
            [
                'name' => 'Gemini Pro',
                'provider' => 'google',
                'model_identifier' => 'gemini-pro',
                'api_url' => 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent',
                'description' => 'Google\'s powerful AI model',
                'icon' => 'stars',
                'color' => '#4285f4',
                'is_active' => true,
                'supports_vision' => false,
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'order' => 3,
            ],
            [
                'name' => 'GPT-4o Mini',
                'provider' => 'openai',
                'model_identifier' => 'gpt-4o-mini',
                'api_url' => 'https://api.openai.com/v1/chat/completions',
                'description' => 'Fast and efficient OpenAI model',
                'icon' => 'bolt',
                'color' => '#3ddcff',
                'is_active' => true,
                'supports_vision' => true,
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'order' => 4,
            ],
            [
                'name' => 'DeepSeek V3',
                'provider' => 'deepseek',
                'model_identifier' => 'deepseek-chat',
                'api_url' => 'https://api.deepseek.com/v1/chat/completions',
                'description' => 'DeepSeek\'s powerful AI reasoning model',
                'icon' => 'explore',
                'color' => '#1e90ff',
                'is_active' => true,
                'supports_vision' => false,
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'order' => 5,
            ],
        ];

        foreach ($models as $model) {
            AiModel::updateOrCreate(
                ['provider' => $model['provider'], 'model_identifier' => $model['model_identifier']],
                $model
            );
        }
    }
}
