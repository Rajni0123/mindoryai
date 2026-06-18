<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Topper ranking fields
            $table->decimal('topper_score', 10, 2)->default(0)->after('topper_total_chats');
            $table->integer('doubts_solved_count')->default(0)->after('topper_score');
            $table->integer('avg_response_time_seconds')->nullable()->after('doubts_solved_count');
            $table->integer('topper_rank_position')->nullable()->after('avg_response_time_seconds');
            $table->timestamp('last_topper_calculation')->nullable()->after('topper_rank_position');
        });

        // Add index for fast topper queries
        Schema::table('users', function (Blueprint $table) {
            $table->index(['topper_score', 'is_topper'], 'users_topper_score_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_topper_score_index');
            $table->dropColumn([
                'topper_score',
                'doubts_solved_count',
                'avg_response_time_seconds',
                'topper_rank_position',
                'last_topper_calculation',
            ]);
        });
    }
};
