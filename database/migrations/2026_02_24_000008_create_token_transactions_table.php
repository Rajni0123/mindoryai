<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['purchase', 'spend', 'refund', 'bonus', 'expired']);
            $table->integer('amount'); // Positive for credits, negative for debits
            $table->integer('balance_after');
            $table->string('description');
            $table->string('reference_type')->nullable(); // conversation, payment, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->decimal('inr_amount', 10, 2)->nullable(); // For purchases
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('created_at');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_transactions');
    }
};
