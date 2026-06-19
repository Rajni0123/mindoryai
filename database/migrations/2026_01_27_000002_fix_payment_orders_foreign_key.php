<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix: Remove foreign key constraint on plan_id since we use user_plans table
     */
    public function up(): void
    {
        if (Schema::hasTable('payment_orders')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                // For SQLite, we need to recreate the table without the foreign key
                DB::statement('PRAGMA foreign_keys = OFF;');

                Schema::create('payment_orders_new', function (Blueprint $table) {
                    $table->id();
                    $table->string('order_id')->unique();
                    $table->unsignedBigInteger('user_id');
                    $table->unsignedBigInteger('plan_id');
                    $table->decimal('amount', 10, 2);
                    $table->string('currency', 10)->default('INR');
                    $table->string('billing_cycle')->default('monthly');
                    $table->string('status')->default('pending');
                    $table->string('payment_id')->nullable();
                    $table->string('payment_gateway')->nullable();
                    $table->text('payment_data')->nullable();
                    $table->timestamps();

                    $table->index(['user_id', 'status']);
                    $table->index('order_id');
                });

                DB::statement('INSERT INTO payment_orders_new SELECT * FROM payment_orders;');
                Schema::drop('payment_orders');
                Schema::rename('payment_orders_new', 'payment_orders');
                DB::statement('PRAGMA foreign_keys = ON;');
            } else {
                // For MySQL/MariaDB, drop foreign key if exists
                try {
                    $foreignKeys = DB::select("
                        SELECT CONSTRAINT_NAME
                        FROM information_schema.TABLE_CONSTRAINTS
                        WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = 'payment_orders'
                        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                    ");

                    foreach ($foreignKeys as $fk) {
                        Schema::table('payment_orders', function (Blueprint $table) use ($fk) {
                            $table->dropForeign([$fk->CONSTRAINT_NAME]);
                        });
                    }
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};
