<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AdminSubdomainDoctor extends Command
{
    protected $signature = 'admin:doctor';

    protected $description = 'Verify admin routes and print aaPanel/nginx checks for ad.* subdomain';

    public function handle(): int
    {
        $this->info('Admin panel diagnostics');
        $this->newLine();

        $routes = ['admin.login', 'admin.dashboard', 'admin.users', 'admin.hybrid-retrieval'];
        foreach ($routes as $name) {
            try {
                $this->line(sprintf('  [OK] %s → %s', $name, route($name)));
            } catch (\Throwable $e) {
                $this->error(sprintf('  [FAIL] %s — %s', $name, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->comment('aaPanel — ad.blinkstudy.in site MUST use the same Laravel public root:');
        $this->line('  /www/wwwroot/blinkstudy.in/public');
        $this->newLine();
        $this->comment('On server, compare nginx roots:');
        $this->line('  grep -E "server_name|root" /www/server/panel/vhost/nginx/ad.blinkstudy.in.conf');
        $this->line('  grep -E "server_name|root" /www/server/panel/vhost/nginx/blinkstudy.in.conf');
        $this->newLine();
        $this->comment('Test Laravel locally (should NOT be nginx 404):');
        $this->line('  curl -sI -H "Host: ad.blinkstudy.in" http://127.0.0.1/admin/users | head -5');
        $this->newLine();
        $this->comment('If curl shows 302/404 Laravel but browser shows nginx 404, fix ad site root + rewrite in aaPanel.');

        return self::SUCCESS;
    }
}
