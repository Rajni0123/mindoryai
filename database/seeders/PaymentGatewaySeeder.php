<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = [
            [
                'name' => 'razorpay',
                'display_name' => 'Razorpay',
                'is_enabled' => false,
                'is_test_mode' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'cashfree',
                'display_name' => 'Cashfree',
                'is_enabled' => false,
                'is_test_mode' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'phonepe',
                'display_name' => 'PhonePe',
                'is_enabled' => false,
                'is_test_mode' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                ['name' => $gateway['name']],
                $gateway
            );
        }

        echo "Payment gateways seeded:\n";
        echo "✅ Razorpay\n";
        echo "✅ Cashfree\n";
        echo "✅ PhonePe\n";
        echo "\nConfigure API keys in admin panel to enable gateways.\n";
    }
}
