<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plan_features')) {
            return;
        }

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('feature_slug');
            $table->enum('limit_type', ['daily', 'monthly', 'unlimited', 'boolean'])->default('daily');
            $table->integer('limit_value')->default(0); // 0 = disabled, -1 = unlimited
            $table->json('extra_info')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'feature_slug']);
            $table->index('feature_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
