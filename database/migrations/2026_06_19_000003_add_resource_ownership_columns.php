<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_cache', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index('user_id');
        });

        Schema::create('notification_user_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['user_id', 'notification_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_user_reads');

        Schema::table('quiz_cache', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
