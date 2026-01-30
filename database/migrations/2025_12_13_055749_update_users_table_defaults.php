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
            // Make token_limit nullable with default 0
            $table->integer('token_limit')->nullable()->default(0)->change();
            $table->integer('tokens_used')->nullable()->default(0)->change();
            $table->boolean('is_active')->nullable()->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('token_limit')->default(10000)->change();
            $table->integer('tokens_used')->default(0)->change();
            $table->boolean('is_active')->default(true)->change();
        });
    }
};
