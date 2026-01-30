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
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Basic, Premium, Lifetime
            $table->decimal('price', 10, 2); // 299.00, 599.00, 4999.00
            $table->string('period'); // month, once
            $table->text('features')->nullable(); // JSON array of features
            $table->string('badge')->nullable(); // MOST POPULAR, BEST VALUE
            $table->string('badge_color')->default('neon-blue'); // neon-blue, neon-violet
            $table->string('button_text')->default('Get Started'); // Button text
            $table->integer('order')->default(0); // Display order
            $table->boolean('is_active')->default(true); // Show/hide plan
            $table->boolean('is_highlighted')->default(false); // Premium plan highlight
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_plans');
    }
};
