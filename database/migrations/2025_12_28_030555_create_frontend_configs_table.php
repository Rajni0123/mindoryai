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
        Schema::create('frontend_configs', function (Blueprint $table) {
            $table->id();
            $table->string('config_key')->unique()->comment('Unique key identifier');
            $table->text('config_value')->comment('Configuration value (can be json)');
            $table->enum('value_type', ['boolean', 'string', 'number', 'json'])->default('string')->comment('Data type of the value');
            $table->string('description')->nullable()->comment('Human-readable description');
            $table->boolean('is_active')->default(true)->comment('Enable/disable config');
            $table->timestamps();

            $table->index('config_key');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frontend_configs');
    }
};
