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
        if (Schema::hasTable('pricing_plans')) {
            Schema::table('pricing_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('pricing_plans', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pricing_plans')) {
            Schema::table('pricing_plans', function (Blueprint $table) {
                if (Schema::hasColumn('pricing_plans', 'description')) {
                    $table->dropColumn('description');
                }
            });
        }
    }
};
