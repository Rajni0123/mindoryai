<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates tables for OTP-based authentication system
     */
    public function up(): void
    {
        // Add profile completion columns to users table
        if (!Schema::hasColumn('users', 'is_profile_complete')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_profile_complete')->default(false)->after('password');
                $table->timestamp('profile_completed_at')->nullable()->after('is_profile_complete');
                $table->integer('login_count')->default(0)->after('profile_completed_at');
                $table->string('student_class')->nullable()->after('login_count');
                $table->string('target_exam')->nullable()->after('student_class');
                $table->date('exam_date')->nullable()->after('target_exam');
            });
        }

        // OTP Verifications table
        if (!Schema::hasTable('otp_verifications')) {
            Schema::create('otp_verifications', function (Blueprint $table) {
                $table->id();
                $table->string('phone_number', 15);
                $table->string('email')->nullable();
                $table->string('otp_code', 6);
                $table->enum('type', ['phone', 'email', 'whatsapp'])->default('phone');
                $table->boolean('is_verified')->default(false);
                $table->timestamp('expires_at');
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index('phone_number');
                $table->index('email');
                $table->index('expires_at');
                $table->index(['phone_number', 'otp_code', 'is_verified']);
            });
        }

        // User Sessions table
        if (!Schema::hasTable('user_sessions')) {
            Schema::create('user_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('device_id')->nullable();
                $table->string('device_name')->nullable();
                $table->string('device_type')->nullable(); // android, ios, web
                $table->string('ip_address')->nullable();
                $table->boolean('is_first_login')->default(false);
                $table->boolean('profile_was_incomplete')->default(false);
                $table->timestamp('login_at')->useCurrent();
                $table->timestamp('logout_at')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('device_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('otp_verifications');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_profile_complete',
                'profile_completed_at',
                'login_count',
                'student_class',
                'target_exam',
                'exam_date'
            ]);
        });
    }
};
