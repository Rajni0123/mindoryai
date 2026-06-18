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
            // 2FA (Google Authenticator) fields
            $table->boolean('two_factor_enabled')->default(false)->after('role');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');

            // Admin login security
            $table->integer('failed_login_attempts')->default(0)->after('two_factor_confirmed_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->string('last_login_ip')->nullable()->after('locked_until');
            $table->text('last_login_user_agent')->nullable()->after('last_login_ip');
            $table->timestamp('last_login_at')->nullable()->after('last_login_user_agent');

            // Session management
            $table->timestamp('last_activity_at')->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'failed_login_attempts',
                'locked_until',
                'last_login_ip',
                'last_login_user_agent',
                'last_login_at',
                'last_activity_at',
            ]);
        });
    }
};
