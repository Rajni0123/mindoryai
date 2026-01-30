<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;

class QuizAttemptsSeeder extends Seeder
{
    public function run(): void
    {
        // Get the first user (or create one if doesn't exist)
        $user = User::first();

        if (!$user) {
            $this->command->warn('No users found in database. Please create a user first.');
            return;
        }

        // Clear existing quiz attempts for this user
        QuizAttempt::where('user_id', $user->id)->delete();
        $this->command->info('Cleared existing quiz attempts for user.');

        $attempts = [
            // Today's quizzes
            [
                'user_id' => $user->id,
                'title' => 'JEE Mathematics Trigonometry Quiz',
                'exam' => 'JEE',
                'subject' => 'Mathematics',
                'topic' => 'Trigonometry',
                'difficulty_level' => 'hard',
                'language' => 'english',
                'duration_minutes' => 15,
                'total_questions' => 10,
                'correct_answers' => 9,
                'wrong_answers' => 1,
                'skipped_questions' => 0,
                'score' => 90.00,
                'time_taken_seconds' => 840,
                'status' => 'completed',
                'started_at' => Carbon::now()->subHours(3),
                'completed_at' => Carbon::now()->subHours(3)->addMinutes(14),
                'created_at' => Carbon::now()->subHours(3),
            ],
            [
                'user_id' => $user->id,
                'title' => 'NEET Biology Cell Structure Quiz',
                'exam' => 'NEET',
                'subject' => 'Biology',
                'topic' => 'Cell Structure',
                'difficulty_level' => 'medium',
                'language' => 'english',
                'duration_minutes' => 10,
                'total_questions' => 10,
                'correct_answers' => 8,
                'wrong_answers' => 2,
                'skipped_questions' => 0,
                'score' => 80.00,
                'time_taken_seconds' => 540,
                'status' => 'completed',
                'started_at' => Carbon::now()->subHours(1),
                'completed_at' => Carbon::now()->subHours(1)->addMinutes(9),
                'created_at' => Carbon::now()->subHours(1),
            ],
            // Yesterday's quizzes
            [
                'user_id' => $user->id,
                'title' => 'JEE Physics Mechanics Quiz',
                'exam' => 'JEE',
                'subject' => 'Physics',
                'topic' => 'Mechanics',
                'difficulty_level' => 'hard',
                'language' => 'english',
                'duration_minutes' => 15,
                'total_questions' => 10,
                'correct_answers' => 8,
                'wrong_answers' => 2,
                'skipped_questions' => 0,
                'score' => 80.00,
                'time_taken_seconds' => 780,
                'status' => 'completed',
                'started_at' => Carbon::now()->subDays(1),
                'completed_at' => Carbon::now()->subDays(1)->addMinutes(13),
                'created_at' => Carbon::now()->subDays(1),
            ],
            [
                'user_id' => $user->id,
                'title' => 'NEET Chemistry Organic Quiz',
                'exam' => 'NEET',
                'subject' => 'Chemistry',
                'topic' => 'Organic Chemistry',
                'difficulty_level' => 'medium',
                'language' => 'english',
                'duration_minutes' => 10,
                'total_questions' => 10,
                'correct_answers' => 9,
                'wrong_answers' => 1,
                'skipped_questions' => 0,
                'score' => 90.00,
                'time_taken_seconds' => 540,
                'status' => 'completed',
                'started_at' => Carbon::now()->subDays(2),
                'completed_at' => Carbon::now()->subDays(2)->addMinutes(9),
                'created_at' => Carbon::now()->subDays(2),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Class 10 Mathematics Algebra',
                'exam' => 'Class 10',
                'subject' => 'Mathematics',
                'topic' => 'Algebra',
                'difficulty_level' => 'easy',
                'language' => 'hindi',
                'duration_minutes' => 10,
                'total_questions' => 10,
                'correct_answers' => 7,
                'wrong_answers' => 2,
                'skipped_questions' => 1,
                'score' => 70.00,
                'time_taken_seconds' => 600,
                'status' => 'completed',
                'started_at' => Carbon::now()->subDays(3),
                'completed_at' => Carbon::now()->subDays(3)->addMinutes(10),
                'created_at' => Carbon::now()->subDays(3),
            ],
            [
                'user_id' => $user->id,
                'title' => 'JEE Mathematics Calculus',
                'exam' => 'JEE',
                'subject' => 'Mathematics',
                'topic' => 'Calculus',
                'difficulty_level' => 'hard',
                'language' => 'english',
                'duration_minutes' => 20,
                'total_questions' => 10,
                'correct_answers' => 9,
                'wrong_answers' => 1,
                'skipped_questions' => 0,
                'score' => 90.00,
                'time_taken_seconds' => 1020,
                'status' => 'completed',
                'started_at' => Carbon::now()->subDays(4),
                'completed_at' => Carbon::now()->subDays(4)->addMinutes(17),
                'created_at' => Carbon::now()->subDays(4),
            ],
            [
                'user_id' => $user->id,
                'title' => 'NEET Biology Genetics Quiz',
                'exam' => 'NEET',
                'subject' => 'Biology',
                'topic' => 'Genetics',
                'difficulty_level' => 'medium',
                'language' => 'english',
                'duration_minutes' => 15,
                'total_questions' => 10,
                'correct_answers' => 8,
                'wrong_answers' => 2,
                'skipped_questions' => 0,
                'score' => 80.00,
                'time_taken_seconds' => 840,
                'status' => 'completed',
                'started_at' => Carbon::now()->subDays(5),
                'completed_at' => Carbon::now()->subDays(5)->addMinutes(14),
                'created_at' => Carbon::now()->subDays(5),
            ],
            [
                'user_id' => $user->id,
                'title' => 'SSC English Grammar Quiz',
                'exam' => 'SSC',
                'subject' => 'English',
                'topic' => 'Grammar',
                'difficulty_level' => 'easy',
                'language' => 'english',
                'duration_minutes' => 10,
                'total_questions' => 10,
                'correct_answers' => 10,
                'wrong_answers' => 0,
                'skipped_questions' => 0,
                'score' => 100.00,
                'time_taken_seconds' => 480,
                'status' => 'completed',
                'started_at' => Carbon::now()->subDays(6),
                'completed_at' => Carbon::now()->subDays(6)->addMinutes(8),
                'created_at' => Carbon::now()->subDays(6),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Class 12 Physics Electromagnetism',
                'exam' => 'Class 12',
                'subject' => 'Physics',
                'topic' => 'Electromagnetism',
                'difficulty_level' => 'hard',
                'language' => 'hindi',
                'duration_minutes' => 20,
                'total_questions' => 10,
                'correct_answers' => 6,
                'wrong_answers' => 3,
                'skipped_questions' => 1,
                'score' => 60.00,
                'time_taken_seconds' => 1200,
                'status' => 'completed',
                'started_at' => Carbon::now()->subDays(7),
                'completed_at' => Carbon::now()->subDays(7)->addMinutes(20),
                'created_at' => Carbon::now()->subDays(7),
            ],
        ];

        foreach ($attempts as $attempt) {
            QuizAttempt::create($attempt);
        }

        $this->command->info('✅ Quiz attempts seeded successfully!');
    }
}
