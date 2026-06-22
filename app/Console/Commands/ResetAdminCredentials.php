<?php

namespace App\Console\Commands;

use App\Services\AdminCredentialService;
use Illuminate\Console\Command;

class ResetAdminCredentials extends Command
{
    protected $signature = 'admin:reset
        {--mobile= : Admin mobile number (10 digits)}
        {--email= : Admin email address}
        {--password= : New admin password}
        {--name=Admin : Admin display name}';

    protected $description = 'Create or reset an admin account (password login, no SMS required)';

    public function handle(AdminCredentialService $credentials): int
    {
        $mobile = $this->option('mobile');
        $email = $this->option('email');
        $password = $this->option('password');

        if (! $mobile && ! $email) {
            $this->error('Provide at least --mobile or --email.');

            return self::FAILURE;
        }

        if (! $password) {
            $password = $this->secret('Enter new admin password');
        }

        if (! $password || strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user = $credentials->resetAdmin([
            'mobile' => $mobile,
            'email' => $email,
            'password' => $password,
            'name' => $this->option('name'),
        ]);

        $ok = $credentials->verifyPassword($user, $password);

        $this->info('Admin account ready.');
        $this->line('ID: ' . $user->id);
        $this->line('Email: ' . $user->email);
        $this->line('Mobile: ' . ($user->mobile ?: '—'));
        $this->line('Role: ' . $user->role);
        $this->line('Password check: ' . ($ok ? 'OK' : 'FAILED'));

        if (! $ok) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Login URL: /admin/login');

        return self::SUCCESS;
    }
}
