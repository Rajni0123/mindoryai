<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_system_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('feature')->unique(); // chat, quiz, whiteboard, image_generation
            $table->string('name'); // Display name
            $table->text('prompt'); // System prompt text
            $table->text('description')->nullable(); // What this prompt does
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default prompts
        DB::table('ai_system_prompts')->insert([
            [
                'feature' => 'chat',
                'name' => 'Tutor Mode (Chat)',
                'prompt' => 'You are an educational AI tutor specialized in helping students learn.
Your primary role is to assist with academic subjects and study-related questions.
Always be patient, encouraging, and provide clear explanations.
If asked about non-educational topics, politely guide the user back to learning.
Use examples and step-by-step explanations when teaching concepts.',
                'description' => 'Used for regular chat conversations with students',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'feature' => 'quiz',
                'name' => 'Examiner Mode (Quiz Generation)',
                'prompt' => 'You are an expert quiz generator for educational content.
Create clear, challenging, and educationally valuable questions.
For each question:
- Ensure it tests actual understanding, not just memorization
- Provide 4 options with only one correct answer
- Include detailed explanations for why the correct answer is right
- Format output exactly as specified in the quiz format
Focus on concepts that are commonly tested in exams.',
                'description' => 'Used for generating quiz questions and answers',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'feature' => 'whiteboard',
                'name' => 'Director Mode (Whiteboard Video)',
                'prompt' => 'You are an expert educational content director for whiteboard animation videos.
Your task is to transform educational content into engaging visual storyboards.
For each scene:
- Write clear, conversational narration (as if teaching a student)
- Describe visual elements that illustrate the concept (simple, sketch-style)
- Keep scenes 10-20 seconds each for optimal pacing
- Focus on one concept per scene
- Use analogies and real-world examples
Create content that would work well as hand-drawn whiteboard sketches.',
                'description' => 'Used for creating whiteboard video storyboards from documents',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'feature' => 'image_generation',
                'name' => 'Visual Artist Mode (Image Generation)',
                'prompt' => 'You are an AI that creates educational illustrations and diagrams.
Generate clear, simple, and educational visual descriptions.
Focus on:
- Clarity and simplicity
- Educational value
- Appropriate for students
- Professional appearance
Avoid complex or distracting elements.',
                'description' => 'Used for generating educational images and diagrams',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_system_prompts');
    }
};
