<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FrontendConfig;
use App\Models\AiModel;
use App\Models\Plan;
use App\Models\PlanFeature;

class DiagnoseAI extends Command
{
    protected $signature = 'diagnose:ai';
    protected $description = 'Diagnose AI service configuration and identify issues';

    public function handle()
    {
        $this->info('');
        $this->info('============================================');
        $this->info(' BlinkStudy AI Configuration Diagnostic');
        $this->info('============================================');
        $this->info('');

        $hasIssues = false;

        // Check 1: AI Provider Status
        $this->info('1. Checking AI Provider Configuration...');
        $this->info('');

        $providers = [
            'google' => ['enabled' => 'ai.gemini_enabled', 'key' => 'ai.gemini_api_key', 'name' => 'Google Gemini'],
            'openai' => ['enabled' => 'ai.openai_enabled', 'key' => 'ai.openai_api_key', 'name' => 'OpenAI'],
            'anthropic' => ['enabled' => 'ai.claude_enabled', 'key' => 'ai.claude_api_key', 'name' => 'Anthropic Claude'],
            'deepseek' => ['enabled' => 'ai.deepseek_enabled', 'key' => 'ai.deepseek_api_key', 'name' => 'DeepSeek'],
        ];

        $hasActiveProvider = false;

        foreach ($providers as $provider => $config) {
            $enabled = FrontendConfig::getValue($config['enabled'], '0') === '1';
            $apiKey = FrontendConfig::getValue($config['key'], '');
            $hasKey = !empty($apiKey) && strlen($apiKey) > 10;

            $status = $enabled && $hasKey ? '  [OK]' : '  [!!]';
            $enabledText = $enabled ? 'ENABLED' : 'DISABLED';
            $keyText = $hasKey ? 'API KEY SET' : 'NO API KEY';

            if ($enabled && $hasKey) {
                $this->info("   {$config['name']}: {$enabledText}, {$keyText}");
                $hasActiveProvider = true;
            } else {
                $this->warn("   {$config['name']}: {$enabledText}, {$keyText}");
            }
        }

        if (!$hasActiveProvider) {
            $this->error('');
            $this->error('   CRITICAL: No AI provider is properly configured!');
            $this->error('   Go to Admin Panel → AI Settings to configure.');
            $hasIssues = true;
        }

        $this->info('');

        // Check 2: AI Models
        $this->info('2. Checking AI Models...');
        $this->info('');

        $totalModels = AiModel::count();
        $activeModels = AiModel::where('is_active', true)->count();

        if ($totalModels === 0) {
            $this->error('   CRITICAL: No AI models found in database!');
            $this->error('   Run: php artisan db:seed --class=AiModelsSeeder');
            $hasIssues = true;
        } else {
            $this->info("   Total models: {$totalModels}");
            $this->info("   Active models: {$activeModels}");

            if ($activeModels === 0) {
                $this->error('   WARNING: No active AI models!');
                $hasIssues = true;
            }
        }

        $this->info('');

        // Check 3: Plans and Features
        $this->info('3. Checking Plans and Features...');
        $this->info('');

        $totalPlans = Plan::count();
        $activePlans = Plan::where('is_active', true)->count();
        $totalFeatures = PlanFeature::count();

        if ($totalPlans === 0) {
            $this->error('   CRITICAL: No plans found in database!');
            $this->error('   Run: php artisan db:seed --class=SubscriptionPlansSeeder');
            $hasIssues = true;
        } else {
            $this->info("   Total plans: {$totalPlans}");
            $this->info("   Active plans: {$activePlans}");
            $this->info("   Total features: {$totalFeatures}");

            // Check required features exist
            $requiredFeatures = ['ai_doubt', 'quiz', 'topic_quiz_gen', 'reasoning', 'exam_prep', 'whiteboard_video'];
            $missingFeatures = [];

            foreach ($requiredFeatures as $feature) {
                if (!PlanFeature::where('feature_slug', $feature)->exists()) {
                    $missingFeatures[] = $feature;
                }
            }

            if (count($missingFeatures) > 0) {
                $this->error('   CRITICAL: Missing required features: ' . implode(', ', $missingFeatures));
                $this->error('   Run: php artisan db:seed --class=SubscriptionPlansSeeder');
                $hasIssues = true;
            }
        }

        $this->info('');

        // Check 4: Free Plan Check
        $this->info('4. Checking Free Plan...');
        $this->info('');

        $freePlan = Plan::where('slug', 'free')->first();
        if (!$freePlan) {
            $this->error('   CRITICAL: Free plan not found!');
            $this->error('   Users without subscriptions will fail!');
            $hasIssues = true;
        } else {
            $freeFeatures = PlanFeature::where('plan_id', $freePlan->id)->count();
            $this->info("   Free plan exists with {$freeFeatures} features configured");
        }

        $this->info('');
        $this->info('============================================');

        if ($hasIssues) {
            $this->error(' ISSUES FOUND - Please fix the errors above');
            $this->info('');
            $this->info(' Quick fix commands:');
            $this->info('   php artisan db:seed --class=AiModelsSeeder');
            $this->info('   php artisan db:seed --class=SubscriptionPlansSeeder');
            $this->info('');
            $this->info(' Then configure AI keys in Admin Panel → AI Settings');
            return 1;
        } else {
            $this->info(' All checks passed! AI should be working.');
            return 0;
        }
    }
}
