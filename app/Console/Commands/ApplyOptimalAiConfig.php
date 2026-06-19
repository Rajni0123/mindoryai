<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ApplyOptimalAiConfig extends Command
{
    protected $signature = 'ai:apply-optimal-config';

    protected $description = 'Apply BlinkStudy optimal AI model stack (GPT-first, adaptive accuracy)';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => 'OptimalAiConfigSeeder', '--force' => true]);

        $this->newLine();
        $this->info('Optimal AI configuration applied.');
        $this->table(
            ['Feature', 'Model'],
            [
                ['Daily chat / doubts', config('ai.chat_model', 'gpt-4o-mini')],
                ['Hard JEE/NEET questions', config('ai.chat_complex_model', 'gpt-4o')],
                ['Scan & Solve', config('ai.vision_model', 'gpt-4o')],
                ['Quiz generate', config('ai.quiz_model', 'gpt-4o-mini')],
            ]
        );

        return self::SUCCESS;
    }
}
