<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_subscriptions')) {
            return;
        }

        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('seasonal_pack_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('billing_cycle', ['monthly', 'annual', 'seasonal'])->default('monthly');
            $table->enum('status', ['active', 'cancelled', 'expired', 'trial'])->default('active');
            $table->string('razorpay_subscription_id')->nullable();
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
