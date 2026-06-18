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
        Schema::create('dynamic_app_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('type')->default('string'); // string, json, boolean, number, file
            $table->text('value')->nullable();
            $table->string('label');
            $table->string('category')->default('general'); // general, ui, features, content, updates
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true); // Can be accessed without auth?
            $table->integer('version')->default(1); // For cache busting
            $table->timestamps();

            $table->index('key');
            $table->index('category');
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_app_configs');
    }
};
