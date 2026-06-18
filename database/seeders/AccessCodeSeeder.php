<?php

namespace Database\Seeders;

use App\Models\AccessCode;
use Illuminate\Database\Seeder;

class AccessCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            [
                'code' => 'DEMO2024',
                'plan' => 'basic',
                'usage_limit' => 100,
                'expires_at' => now()->addMonths(3),
                'is_active' => true,
                'description' => 'Demo access code for testing',
            ],
            [
                'code' => 'PREMIUM2024',
                'plan' => 'premium',
                'usage_limit' => null,
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'description' => 'Premium access code',
            ],
            [
                'code' => 'LIFETIME2024',
                'plan' => 'lifetime',
                'usage_limit' => null,
                'expires_at' => null,
                'is_active' => true,
                'description' => 'Lifetime access code',
            ],
        ];

        foreach ($codes as $code) {
            AccessCode::updateOrCreate(
                ['code' => $code['code']],
                $code
            );
        }

        $this->command->info('Access codes created successfully!');
        $this->command->info('Demo Code: DEMO2024');
        $this->command->info('Premium Code: PREMIUM2024');
        $this->command->info('Lifetime Code: LIFETIME2024');
    }
}
