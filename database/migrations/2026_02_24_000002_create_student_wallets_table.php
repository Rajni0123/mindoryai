<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('token_balance')->default(0);
            $table->integer('total_tokens_purchased')->default(0);
            $table->integer('total_tokens_spent')->default(0);
            $table->timestamps();

            $table->unique('user_id');
            $table->index('token_balance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_wallets');
    }
};
