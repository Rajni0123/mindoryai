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
            if (!Schema::hasColumn('users', 'education_level')) {
                $table->string('education_level')->nullable()->after('student_class');
            }
            if (!Schema::hasColumn('users', 'is_profile_complete')) {
                $table->boolean('is_profile_complete')->default(false)->after('education_level');
            }
            if (!Schema::hasColumn('users', 'login_count')) {
                $table->integer('login_count')->default(0)->after('is_profile_complete');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['education_level', 'is_profile_complete', 'login_count']);
        });
    }
};
