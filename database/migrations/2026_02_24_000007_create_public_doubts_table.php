<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_doubts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('topper_id')->constrained('users')->onDelete('cascade');
            $table->string('subject');
            $table->string('topic')->nullable();
            $table->text('question');
            $table->text('answer');
            $table->string('image_url')->nullable();
            $table->integer('upvotes')->default(0);
            $table->integer('views')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->json('tags')->nullable(); // ['calculus', 'integration', 'jee-level']
            $table->timestamps();

            $table->index('subject');
            $table->index('upvotes');
            $table->index('views');
            $table->index('is_featured');
            $table->fullText(['question', 'answer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_doubts');
    }
};
