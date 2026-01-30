<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Testimonial;

class TestimonialsSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'user_name' => 'Priya Sharma',
                'user_photo' => null,
                'rating' => 5,
                'message' => 'This app helped me ace my JEE preparation! The AI-generated quizzes are super helpful and the explanations are crystal clear.',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'user_name' => 'Rahul Kumar',
                'user_photo' => null,
                'rating' => 5,
                'message' => 'Best study app I have used! The scan notes feature saves so much time. I can now generate practice questions from my textbooks instantly.',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'user_name' => 'Ananya Patel',
                'user_photo' => null,
                'rating' => 4,
                'message' => 'Amazing for NEET prep! The detailed solutions help me understand concepts better. Would recommend to all medical aspirants.',
                'is_active' => true,
                'order' => 3,
            ],
            [
                'user_name' => 'Arjun Singh',
                'user_photo' => null,
                'rating' => 5,
                'message' => 'Game changer for my studies! I love how I can take a photo of my notes and get instant quizzes. Perfect for last-minute revision.',
                'is_active' => true,
                'order' => 4,
            ],
            [
                'user_name' => 'Sneha Reddy',
                'user_photo' => null,
                'rating' => 5,
                'message' => 'Such a helpful app for competitive exams! The variety of AI models and the quality of questions generated is outstanding.',
                'is_active' => true,
                'order' => 5,
            ],
            [
                'user_name' => 'Vikram Mehta',
                'user_photo' => null,
                'rating' => 4,
                'message' => 'Great for practice! The quiz difficulty levels are just right. This app has improved my preparation significantly.',
                'is_active' => true,
                'order' => 6,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create($testimonial);
        }

        $this->command->info('✅ Testimonials seeded successfully!');
    }
}
