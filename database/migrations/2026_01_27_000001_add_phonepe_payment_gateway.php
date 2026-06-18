<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add PhonePe gateway if it doesn't exist
        $exists = DB::table('payment_gateways')->where('name', 'phonepe')->exists();

        if (!$exists) {
            DB::table('payment_gateways')->insert([
                'name' => 'phonepe',
                'display_name' => 'PhonePe',
                'is_enabled' => false,
                'is_test_mode' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('payment_gateways')->where('name', 'phonepe')->delete();
    }
};
