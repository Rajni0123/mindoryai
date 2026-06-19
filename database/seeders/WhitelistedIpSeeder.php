<?php

namespace Database\Seeders;

use App\Models\WhitelistedIp;
use Illuminate\Database\Seeder;

class WhitelistedIpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ips = [];

        // Add localhost IPs only in local environment
        if (app()->environment('local')) {
            $ips[] = [
                'ip_address' => '127.0.0.1',
                'description' => 'Localhost (Development)',
                'is_active' => true,
            ];
            $ips[] = [
                'ip_address' => '::1',
                'description' => 'IPv6 Localhost (Development)',
                'is_active' => true,
            ];
        }

        // Add your production allowed IPs here
        // Example:
        // $ips[] = [
        //     'ip_address' => '203.0.113.10',
        //     'description' => 'Office IP',
        //     'is_active' => true,
        // ];

        foreach ($ips as $ip) {
            WhitelistedIp::updateOrCreate(
                ['ip_address' => $ip['ip_address']],
                $ip
            );
        }

        $this->command->info('Whitelisted IPs seeded successfully!');
    }
}
