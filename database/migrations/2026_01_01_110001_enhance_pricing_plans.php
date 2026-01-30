<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Enhance pricing plans table with credit-based features
     */
    public function up(): void
    {
        // Check if pricing_plans table exists
        if (!Schema::hasTable('pricing_plans')) {
            Schema::create('pricing_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g., "Free", "Pro", "Premium"
                $table->string('slug')->unique(); // e.g., "free", "pro", "premium"
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2)->default(0); // Monthly price
                $table->decimal('yearly_price', 10, 2)->nullable(); // Yearly price (with discount)
                $table->integer('credits_per_month')->default(0); // Monthly credits
                $table->boolean('unlimited_credits')->default(false); // Unlimited mode
                $table->boolean('is_active')->default(true);
                $table->integer('max_messages_per_day')->nullable();
                $table->integer('max_images_per_day')->nullable();
                $table->json('features')->nullable(); // Additional features
                $table->integer('order')->default(0); // Display order
                $table->timestamps();
            });
        } else {
            // Add new columns if table exists
            Schema::table('pricing_plans', function (Blueprint $table) {
                // Add description first if it doesn't exist
                if (!Schema::hasColumn('pricing_plans', 'description')) {
                    $table->text('description')->nullable();
                }

                if (!Schema::hasColumn('pricing_plans', 'slug')) {
                    $table->string('slug')->nullable()->unique();
                }

                if (!Schema::hasColumn('pricing_plans', 'yearly_price')) {
                    $table->decimal('yearly_price', 10, 2)->nullable();
                }

                if (!Schema::hasColumn('pricing_plans', 'credits_per_month')) {
                    $table->integer('credits_per_month')->default(0);
                }

                if (!Schema::hasColumn('pricing_plans', 'unlimited_credits')) {
                    $table->boolean('unlimited_credits')->default(false);
                }

                if (!Schema::hasColumn('pricing_plans', 'max_messages_per_day')) {
                    $table->integer('max_messages_per_day')->nullable();
                }

                if (!Schema::hasColumn('pricing_plans', 'max_images_per_day')) {
                    $table->integer('max_images_per_day')->nullable();
                }

                if (!Schema::hasColumn('pricing_plans', 'order')) {
                    $table->integer('order')->default(0);
                }
            });
        }

        // Create user_subscriptions table
        if (!Schema::hasTable('user_subscriptions')) {
            Schema::create('user_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('plan_id')->constrained('pricing_plans')->onDelete('cascade');
                $table->enum('status', ['active', 'cancelled', 'expired', 'pending'])->default('pending');
                $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
                $table->decimal('amount_paid', 10, 2);
                $table->date('start_date');
                $table->date('end_date');
                $table->date('next_billing_date')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('transaction_id')->nullable();
                $table->boolean('auto_renew')->default(false);
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index('end_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
        // Don't drop pricing_plans as it might have existing data
    }
};
