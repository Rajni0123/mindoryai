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
            // Drop the old foreign key constraint pointing to user_plans
            $table->dropForeign(['plan_id']);

            // Add new foreign key constraint pointing to pricing_plans
            $table->foreign('plan_id')
                ->references('id')
                ->on('pricing_plans')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the foreign key constraint pointing to pricing_plans
            $table->dropForeign(['plan_id']);

            // Restore the old foreign key constraint pointing to user_plans
            $table->foreign('plan_id')
                ->references('id')
                ->on('user_plans')
                ->onDelete('set null');
        });
    }
};
