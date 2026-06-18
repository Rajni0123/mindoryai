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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('token_limit')->default(10000);
            $table->integer('tokens_used')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('can_use_gpt4')->default(false);
            $table->boolean('can_use_claude')->default(false);
            $table->boolean('can_use_deepseek')->default(true);
            $table->boolean('can_use_grok')->default(false);
            $table->timestamp('last_token_reset')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
