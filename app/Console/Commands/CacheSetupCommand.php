<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CacheSetupCommand extends Command
{
    protected $signature = 'cache:setup {--seed : Seed greeting patterns after migration}';

    protected $description = 'Setup Smart Cache tables and optionally seed greeting patterns';

    public function handle(): int
    {
        $this->info('🚀 Setting up Smart Cache System...');
        $this->newLine();

        // Step 1: Run migrations
        $this->info('📦 Running migrations...');
        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✅ Migrations completed');
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return 1;
        }

        // Step 2: Clear config cache
        $this->info('🧹 Clearing config cache...');
        try {
            Artisan::call('config:clear');
            $this->info('✅ Config cache cleared');
        } catch (\Exception $e) {
            $this->warn('⚠️ Config cache clear failed (non-critical)');
        }

        // Step 3: Seed greeting patterns if requested
        if ($this->option('seed')) {
            $this->newLine();
            $this->info('🌱 Seeding greeting patterns...');
            try {
                Artisan::call('cache:seed', ['--patterns' => true]);
                $this->info('✅ Greeting patterns seeded');
            } catch (\Exception $e) {
                $this->warn('⚠️ Seeding failed: ' . $e->getMessage());
            }
        }

        // Step 4: Show stats
        $this->newLine();
        $this->info('📊 Smart Cache System Status:');
        $this->displayStatus();

        $this->newLine();
        $this->info('🎉 Smart Cache System setup complete!');
        $this->newLine();
        $this->info('Usage:');
        $this->line('  - Check stats: php artisan cache:stats');
        $this->line('  - Test cache:  php artisan cache:test "What is photosynthesis?"');
        $this->line('  - Cleanup:     php artisan cache:cleanup');

        return 0;
    }

    private function displayStatus(): void
    {
        $tables = [
            'answer_bank' => 'Answer Bank',
            'formula_bank' => 'Formula Bank',
            'ncert_cache' => 'NCERT Cache',
            'video_cache' => 'Video Cache',
            'image_cache' => 'Image Cache',
            'cache_analytics' => 'Cache Analytics',
            'greeting_patterns' => 'Greeting Patterns',
        ];

        $this->newLine();
        $tableData = [];

        foreach ($tables as $table => $name) {
            try {
                $count = \DB::table($table)->count();
                $status = '✅';
            } catch (\Exception $e) {
                $count = 'N/A';
                $status = '❌';
            }
            $tableData[] = [$status, $name, $count];
        }

        $this->table(['Status', 'Table', 'Records'], $tableData);

        // Show config status
        $this->newLine();
        $this->info('Configuration:');
        $enabled = config('smartcache.enabled', false);
        $this->line('  Cache Enabled: ' . ($enabled ? '✅ Yes' : '❌ No'));

        $features = config('smartcache.cacheable_features', []);
        $enabledFeatures = array_filter($features, fn($f) => $f['enabled'] ?? false);
        $this->line('  Enabled Features: ' . count($enabledFeatures));
    }
}
