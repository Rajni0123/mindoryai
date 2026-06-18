<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin123'),
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

        // Create demo user
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Demo User',
                'email' => 'user@example.com',
                'password' => Hash::make('user123'),
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

        echo "Admin user created:\n";
        echo "Email: admin@example.com\n";
        echo "Password: admin123\n\n";
        echo "Demo user created:\n";
        echo "Email: user@example.com\n";
        echo "Password: user123\n";
    }
}
