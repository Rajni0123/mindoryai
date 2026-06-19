<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\DynamicAppConfig;
use App\Models\FrontendConfig;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds BlinkStudy optimal AI configuration:
 * - GPT-first for chat, quiz, scan
 * - Adaptive gpt-4o-mini → gpt-4o for hard questions
 */
class OptimalAiConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Enable providers (admin panel toggles)
        $providerFlags = [
            'ai.openai_enabled' => '1',
            'ai.google_enabled' => '1',
            'ai.gemini_enabled' => '1',
            'ai.claude_enabled' => '0',
            'ai.deepseek_enabled' => '0',
        ];

        foreach ($providerFlags as $key => $value) {
            FrontendConfig::setValue($key, $value, 'boolean');
        }

        // Mobile app default model
        DynamicAppConfig::setValue('ai.default_model', config('ai.chat_model', 'gpt-4o-mini'));
        DynamicAppConfig::setValue('ai.max_tokens', 2048);
        DynamicAppConfig::setValue('ai.timeout_seconds', 30);

        // Thinking levels — minimal for chat speed, high for maths
        $thinkingLevels = [
            'ai.thinking_level.chat' => 'minimal',
            'ai.thinking_level.pdf_solve' => 'medium',
            'ai.thinking_level.math_reasoning' => 'high',
            'ai.thinking_level.mcq_generation' => 'medium',
        ];

        foreach ($thinkingLevels as $key => $value) {
            Setting::set($key, $value, 'ai');
        }

        // Feature → model mapping (uses ai_models table IDs)
        $featureModels = [
            'chat' => 'gpt-4o-mini',
            'ai_doubt' => 'gpt-4o-mini',
            'scan_solve' => 'gpt-4o',
            'pdf_solve' => 'gpt-4o',
            'quiz' => 'gpt-4o-mini',
            'mcq_generation' => 'gpt-4o-mini',
            'exam_prep' => 'gpt-4o',
        ];

        foreach ($featureModels as $feature => $modelIdentifier) {
            $model = AiModel::where('model_identifier', $modelIdentifier)
                ->where('is_active', true)
                ->first();

            if ($model) {
                Setting::set("feature_model_{$feature}", (string) $model->id, 'ai');
            }
        }

        // Quiz generator default
        $quizModel = AiModel::where('model_identifier', 'gpt-4o-mini')->where('is_active', true)->first();
        if ($quizModel) {
            Setting::set('quiz_generator_model_id', (string) $quizModel->id, 'ai');
        }

        Setting::set('openai_model', config('ai.chat_model', 'gpt-4o-mini'), 'ai');

        $this->command?->info('✅ Optimal AI config applied (GPT-first, adaptive models)');
    }
}
