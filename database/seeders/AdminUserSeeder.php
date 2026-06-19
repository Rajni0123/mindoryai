<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('SEEDER_ADMIN_PASSWORD');
        $userPassword = env('SEEDER_DEMO_USER_PASSWORD');

        if (empty($adminPassword) || empty($userPassword)) {
            $this->command->warn('Set SEEDER_ADMIN_PASSWORD and SEEDER_DEMO_USER_PASSWORD in .env before seeding admin users.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'is_active' => true,
                'token_limit' => 999999,
                'tokens_used' => 0,
                'can_use_gpt4' => true,
                'can_use_claude' => true,
                'can_use_deepseek' => true,
                'can_use_grok' => true,
                'token_activated' => true,
                'token_activated_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Demo User',
                'email' => 'user@example.com',
                'password' => Hash::make($userPassword),
                'role' => 'user',
                'is_active' => true,
                'token_limit' => 10000,
                'tokens_used' => 0,
                'can_use_gpt4' => false,
                'can_use_claude' => false,
                'can_use_deepseek' => true,
                'can_use_grok' => false,
                'token_activated' => true,
                'token_activated_at' => now(),
            ]
        );

        $this->command->info('Admin and demo users seeded (passwords from .env only).');
    }
}
