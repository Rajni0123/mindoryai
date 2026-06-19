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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique();
            $table->string('payment_gateway'); // razorpay, cashfree, phonepe
            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('INR');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('purpose'); // subscription, credits, etc.
            $table->json('metadata')->nullable();
            $table->text('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('payment_gateway');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
