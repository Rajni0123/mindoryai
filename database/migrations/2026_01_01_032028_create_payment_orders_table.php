<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('payment_orders')) {
            Schema::create('payment_orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_id')->unique();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('plan_id')->constrained('pricing_plans')->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->string('currency', 10)->default('INR');
                $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
                $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
                $table->string('payment_id')->nullable();
                $table->string('payment_gateway')->nullable();
                $table->text('payment_data')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index('order_id');
            });
        } else {
            // Table exists, add missing columns
            Schema::table('payment_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('payment_orders', 'order_id')) {
                    $table->string('order_id')->unique()->after('id');
                }
                if (!Schema::hasColumn('payment_orders', 'user_id')) {
                    $table->foreignId('user_id')->after('order_id')->constrained('users')->onDelete('cascade');
                }
                if (!Schema::hasColumn('payment_orders', 'plan_id')) {
                    $table->foreignId('plan_id')->after('user_id')->constrained('pricing_plans')->onDelete('cascade');
                }
                if (!Schema::hasColumn('payment_orders', 'amount')) {
                    $table->decimal('amount', 10, 2)->after('plan_id');
                }
                if (!Schema::hasColumn('payment_orders', 'currency')) {
                    $table->string('currency', 10)->default('INR')->after('amount');
                }
                if (!Schema::hasColumn('payment_orders', 'billing_cycle')) {
                    $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly')->after('currency');
                }
                if (!Schema::hasColumn('payment_orders', 'status')) {
                    $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending')->after('billing_cycle');
                }
                if (!Schema::hasColumn('payment_orders', 'payment_id')) {
                    $table->string('payment_id')->nullable()->after('status');
                }
                if (!Schema::hasColumn('payment_orders', 'payment_gateway')) {
                    $table->string('payment_gateway')->nullable()->after('payment_id');
                }
                if (!Schema::hasColumn('payment_orders', 'payment_data')) {
                    $table->text('payment_data')->nullable()->after('payment_gateway');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
