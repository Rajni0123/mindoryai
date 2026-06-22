<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\HybridRetrievalController;
use App\Services\Retrieval\RetrievalSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RetrievalDoctor extends Command
{
    protected $signature = 'retrieval:doctor {--render : Render the admin page HTML to catch Blade errors}';

    protected $description = 'Diagnose hybrid retrieval setup (tables, config, routes)';

    public function handle(): int
    {
        $this->info('Hybrid Retrieval — diagnostics');
        $this->newLine();

        $tables = ['knowledge_sources', 'document_chunks', 'temporary_pdf_retrievals'];
        foreach ($tables as $table) {
            $ok = Schema::hasTable($table);
            $this->line(sprintf('  [%s] table %s', $ok ? 'OK' : 'MISSING', $table));
        }

        $this->newLine();
        $configFile = config_path('retrieval.php');
        $this->line(sprintf(
            '  [%s] config/retrieval.php',
            is_file($configFile) ? 'OK' : 'MISSING'
        ));

        $routes = [
            'admin.hybrid-retrieval',
            'admin.hybrid-retrieval.settings',
            'admin.hybrid-retrieval.sources.store',
            'admin.hybrid-retrieval.sources.toggle',
        ];

        $this->newLine();
        foreach ($routes as $name) {
            try {
                $url = route($name, $name === 'admin.hybrid-retrieval.sources.toggle' ? ['knowledgeSource' => 1] : []);
                $this->line(sprintf('  [OK] %s → %s', $name, $url));
            } catch (\Throwable $e) {
                $this->error(sprintf('  [FAIL] %s — %s', $name, $e->getMessage()));
            }
        }

        if ($this->option('render')) {
            $this->newLine();
            $this->info('Rendering admin page…');
            try {
                $view = app(HybridRetrievalController::class)->index(app(RetrievalSettingsService::class));
                $html = $view->render();
                $this->line(sprintf('  [OK] Blade rendered (%d bytes)', strlen($html)));
            } catch (\Throwable $e) {
                $this->error('  [FAIL] Blade render — ' . $e->getMessage());
                $this->line('  at ' . $e->getFile() . ':' . $e->getLine());

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->comment('If tables are missing: php artisan migrate --force');
        $this->comment('If routes fail: php artisan route:clear && php artisan config:clear && php artisan view:clear');
        $this->comment('To test the view: php artisan retrieval:doctor --render');

        return self::SUCCESS;
    }
}
