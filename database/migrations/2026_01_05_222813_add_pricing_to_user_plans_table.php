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
        Schema::table('user_plans', function (Blueprint $table) {
            $table->string('description')->nullable()->after('slug');
            $table->decimal('price', 10, 2)->default(0)->after('description');
            $table->string('billing_period')->default('month')->after('price'); // month or year
            $table->string('billing_description')->nullable()->after('billing_period');
            $table->integer('order')->default(0)->after('is_active');
            $table->json('features')->nullable()->after('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            $table->dropColumn(['description', 'price', 'billing_period', 'billing_description', 'order', 'features']);
        });
    }
};
