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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // razorpay, cashfree, phonepe
            $table->string('display_name');
            $table->boolean('is_enabled')->default(false);
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->text('merchant_id')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->boolean('is_test_mode')->default(true);
            $table->json('config')->nullable(); // Additional configuration
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
