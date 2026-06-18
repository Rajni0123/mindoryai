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
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->integer('message_tokens')->default(10000)->after('price');
            $table->boolean('can_use_gpt4')->default(false)->after('message_tokens');
            $table->boolean('can_use_claude')->default(false)->after('can_use_gpt4');
            $table->boolean('can_use_deepseek')->default(true)->after('can_use_claude');
            $table->boolean('can_use_grok')->default(false)->after('can_use_deepseek');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropColumn(['message_tokens', 'can_use_gpt4', 'can_use_claude', 'can_use_deepseek', 'can_use_grok']);
        });
    }
};
