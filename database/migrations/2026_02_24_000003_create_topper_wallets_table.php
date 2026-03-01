<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topper_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0); // INR balance
            $table->decimal('total_earned', 10, 2)->default(0);
            $table->decimal('total_withdrawn', 10, 2)->default(0);
            $table->decimal('pending_payout', 10, 2)->default(0);
            $table->string('upi_id')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('pan_number')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('balance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topper_wallets');
    }
};
