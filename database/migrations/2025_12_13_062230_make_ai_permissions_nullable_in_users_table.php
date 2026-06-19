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
        Schema::table('users', function (Blueprint $table) {
            // Make AI permission fields nullable with default false
            $table->boolean('can_use_gpt4')->nullable()->default(false)->change();
            $table->boolean('can_use_claude')->nullable()->default(false)->change();
            $table->boolean('can_use_deepseek')->nullable()->default(false)->change();
            $table->boolean('can_use_grok')->nullable()->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_use_gpt4')->default(false)->change();
            $table->boolean('can_use_claude')->default(false)->change();
            $table->boolean('can_use_deepseek')->default(true)->change();
            $table->boolean('can_use_grok')->default(false)->change();
        });
    }
};
