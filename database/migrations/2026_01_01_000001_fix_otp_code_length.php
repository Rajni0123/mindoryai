<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FIX: Update otp_code to support configurable OTP lengths (4-8 digits)
     * Previous migration limited it to 4, but service uses configurable length (default 6)
     */
    public function up(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            // Change to support up to 8 digits (configurable in admin panel)
            $table->string('otp_code', 8)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->string('otp_code', 4)->change();
        });
    }
};
