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
        Schema::table('daily_usage_limits', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_usage_limits', 'study_battles_used')) {
                $table->integer('study_battles_used')->default(0)->after('mock_tests_used');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_usage_limits', function (Blueprint $table) {
            if (Schema::hasColumn('daily_usage_limits', 'study_battles_used')) {
                $table->dropColumn('study_battles_used');
            }
        });
    }
};
