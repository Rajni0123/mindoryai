<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix: Remove foreign key constraint on plan_id that references deleted pricing_plans table
     */
    public function up(): void
    {
        if (Schema::hasTable('user_subscriptions')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                // SQLite approach - recreate table
                DB::statement('PRAGMA foreign_keys = OFF;');

                Schema::create('user_subscriptions_new', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id');
                    $table->unsignedBigInteger('plan_id');
                    $table->string('status')->default('pending');
                    $table->string('billing_cycle')->default('monthly');
                    $table->decimal('amount_paid', 10, 2)->default(0);
                    $table->timestamp('start_date')->nullable();
                    $table->timestamp('end_date')->nullable();
                    $table->timestamp('next_billing_date')->nullable();
                    $table->string('payment_method')->nullable();
                    $table->string('transaction_id')->nullable();
                    $table->boolean('auto_renew')->default(false);
                    $table->timestamps();

                    $table->index(['user_id', 'status']);
                });

                $columns = ['id', 'user_id', 'plan_id', 'status', 'billing_cycle', 'amount_paid',
                           'start_date', 'end_date', 'next_billing_date', 'payment_method',
                           'transaction_id', 'auto_renew', 'created_at', 'updated_at'];

                $existingColumns = [];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('user_subscriptions', $col)) {
                        $existingColumns[] = $col;
                    }
                }

                if (count($existingColumns) > 0) {
                    $columnList = implode(', ', $existingColumns);
                    DB::statement("INSERT INTO user_subscriptions_new ($columnList) SELECT $columnList FROM user_subscriptions;");
                }

                Schema::drop('user_subscriptions');
                Schema::rename('user_subscriptions_new', 'user_subscriptions');
                DB::statement('PRAGMA foreign_keys = ON;');
            } else {
                // MySQL/MariaDB approach - just drop foreign keys
                try {
                    $foreignKeys = DB::select("
                        SELECT CONSTRAINT_NAME
                        FROM information_schema.TABLE_CONSTRAINTS
                        WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = 'user_subscriptions'
                        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                    ");

                    foreach ($foreignKeys as $fk) {
                        Schema::table('user_subscriptions', function (Blueprint $table) use ($fk) {
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
