<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Topper-specific fields
            $table->boolean('is_topper')->default(false)->after('role');
            $table->boolean('is_verified_topper')->default(false)->after('is_topper');
            $table->string('topper_exam')->nullable()->after('is_verified_topper'); // jee, neet, boards
            $table->integer('topper_rank')->nullable()->after('topper_exam');
            $table->decimal('topper_rating', 3, 2)->default(5.00)->after('topper_rank');
            $table->integer('topper_total_chats')->default(0)->after('topper_rating');
            $table->boolean('topper_available')->default(false)->after('topper_total_chats');
            $table->timestamp('topper_last_active')->nullable()->after('topper_available');
            $table->text('topper_bio')->nullable()->after('topper_last_active');
            $table->string('topper_specialization')->nullable()->after('topper_bio'); // physics, chemistry, etc.
            $table->json('topper_subjects')->nullable()->after('topper_specialization'); // ['Physics', 'Chemistry', 'Maths']
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_topper',
                'is_verified_topper',
                'topper_exam',
                'topper_rank',
                'topper_rating',
                'topper_total_chats',
                'topper_available',
                'topper_last_active',
                'topper_bio',
                'topper_specialization',
                'topper_subjects',
            ]);
        });
    }
};
