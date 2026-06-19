<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_feature_usage')) {
            return;
        }

        Schema::create('user_feature_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('feature_slug');
            $table->date('usage_date');
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'feature_slug', 'usage_date']);
            $table->index('user_id');
            $table->index(['user_id', 'feature_slug']);
            $table->index('usage_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_feature_usage');
    }
};
