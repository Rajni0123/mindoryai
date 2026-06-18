<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Drop the empty pricing_plans table - we use user_plans instead
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            // Drop all foreign keys referencing pricing_plans first
            $foreignKeys = [
                'payment_orders' => 'payment_orders_plan_id_foreign',
                'user_subscriptions' => 'user_subscriptions_plan_id_foreign',
                'payments' => 'payments_plan_id_foreign',
            ];

            foreach ($foreignKeys as $table => $fkName) {
                if (Schema::hasTable($table)) {
                    try {
                        Schema::table($table, function (Blueprint $table) use ($fkName) {
                            $table->dropForeign($fkName);
                        });
                    } catch (\Exception $e) {
                        // FK might not exist, continue
                    }
                }
            }
        }

        Schema::dropIfExists('pricing_plans');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate if needed
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('billing_period')->default('month');
            $table->json('features')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
