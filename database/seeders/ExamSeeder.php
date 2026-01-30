<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $exams = [
            [
                'name' => 'JEE Main',
                'slug' => 'jee-main',
                'description' => 'Joint Entrance Examination (Main) for admission to NITs, IIITs and other centrally funded technical institutions.',
                'category' => 'engineering',
                'level' => 'national',
                'subjects' => ['Physics', 'Chemistry', 'Mathematics'],
                'config' => [
                    'duration_minutes' => 180,
                    'total_questions' => 75,
                    'marking_scheme' => ['correct' => 4, 'wrong' => -1],
                ],
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => 'JEE Advanced',
                'slug' => 'jee-advanced',
                'description' => 'Joint Entrance Examination (Advanced) for admission to IITs.',
                'category' => 'engineering',
                'level' => 'national',
                'subjects' => ['Physics', 'Chemistry', 'Mathematics'],
                'config' => [
                    'duration_minutes' => 180,
                    'total_questions' => 54,
                    'marking_scheme' => ['correct' => 3, 'wrong' => -1],
                ],
                'is_active' => true,
                'order' => 2,
            ],
            [
                'name' => 'NEET UG',
                'slug' => 'neet-ug',
                'description' => 'National Eligibility cum Entrance Test for undergraduate medical courses.',
                'category' => 'medical',
                'level' => 'national',
                'subjects' => ['Physics', 'Chemistry', 'Biology (Botany)', 'Biology (Zoology)'],
                'config' => [
                    'duration_minutes' => 200,
                    'total_questions' => 200,
                    'marking_scheme' => ['correct' => 4, 'wrong' => -1],
                ],
                'is_active' => true,
                'order' => 3,
            ],
            [
                'name' => 'UPSC CSE',
                'slug' => 'upsc-cse',
                'description' => 'Union Public Service Commission Civil Services Examination.',
                'category' => 'government',
                'level' => 'national',
                'subjects' => ['General Studies', 'CSAT', 'Indian Polity', 'Geography', 'History', 'Economy', 'Science & Technology'],
                'config' => [
                    'duration_minutes' => 120,
                    'total_questions' => 100,
                    'marking_scheme' => ['correct' => 2, 'wrong' => -0.66],
                ],
                'is_active' => true,
                'order' => 4,
            ],
            [
                'name' => 'SSC CGL',
                'slug' => 'ssc-cgl',
                'description' => 'Staff Selection Commission Combined Graduate Level Examination.',
                'category' => 'government',
                'level' => 'national',
                'subjects' => ['General Intelligence', 'General Awareness', 'Quantitative Aptitude', 'English Comprehension'],
                'config' => [
                    'duration_minutes' => 60,
                    'total_questions' => 100,
                    'marking_scheme' => ['correct' => 2, 'wrong' => -0.5],
                ],
                'is_active' => true,
                'order' => 5,
            ],
            [
                'name' => 'CBSE Class 10',
                'slug' => 'cbse-class-10',
                'description' => 'CBSE Board Examination for Class 10.',
                'category' => 'board',
                'level' => 'board',
                'subjects' => ['Mathematics', 'Science', 'Social Science', 'English', 'Hindi'],
                'config' => [
                    'duration_minutes' => 180,
                    'total_questions' => 40,
                    'marking_scheme' => ['correct' => 1, 'wrong' => 0],
                ],
                'is_active' => true,
                'order' => 6,
            ],
            [
                'name' => 'CBSE Class 12',
                'slug' => 'cbse-class-12',
                'description' => 'CBSE Board Examination for Class 12.',
                'category' => 'board',
                'level' => 'board',
                'subjects' => ['Physics', 'Chemistry', 'Mathematics', 'Biology', 'English'],
                'config' => [
                    'duration_minutes' => 180,
                    'total_questions' => 40,
                    'marking_scheme' => ['correct' => 1, 'wrong' => 0],
                ],
                'is_active' => true,
                'order' => 7,
            ],
            [
                'name' => 'GATE',
                'slug' => 'gate',
                'description' => 'Graduate Aptitude Test in Engineering for postgraduate admissions and PSU recruitment.',
                'category' => 'engineering',
                'level' => 'national',
                'subjects' => ['Engineering Mathematics', 'General Aptitude', 'Core Subject'],
                'config' => [
                    'duration_minutes' => 180,
                    'total_questions' => 65,
                    'marking_scheme' => ['correct' => 2, 'wrong' => -0.66],
                ],
                'is_active' => true,
                'order' => 8,
            ],
        ];

        foreach ($exams as $examData) {
            Exam::updateOrCreate(
                ['slug' => $examData['slug']],
                $examData
            );
        }

        // Seed sample questions for JEE Main
        $jeeMain = Exam::where('slug', 'jee-main')->first();
        if ($jeeMain && $jeeMain->questions()->count() === 0) {
            $sampleQuestions = [
                [
                    'subject' => 'Physics',
                    'topic' => 'Kinematics',
                    'type' => 'mcq',
                    'question_text' => 'A body is thrown vertically upwards with a velocity of 20 m/s. What is the maximum height reached? (g = 10 m/s²)',
                    'options' => [
                        ['label' => 'A', 'text' => '10 m'],
                        ['label' => 'B', 'text' => '20 m'],
                        ['label' => 'C', 'text' => '30 m'],
                        ['label' => 'D', 'text' => '40 m'],
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'Using v² = u² - 2gh, at max height v = 0: 0 = (20)² - 2(10)h → h = 400/20 = 20 m',
                    'difficulty' => 'easy',
                    'year' => 2023,
                    'language' => 'english',
                    'tags' => ['kinematics', 'projectile', 'pyq'],
                ],
                [
                    'subject' => 'Chemistry',
                    'topic' => 'Atomic Structure',
                    'type' => 'mcq',
                    'question_text' => 'The number of radial nodes for 3p orbital is:',
                    'options' => [
                        ['label' => 'A', 'text' => '0'],
                        ['label' => 'B', 'text' => '1'],
                        ['label' => 'C', 'text' => '2'],
                        ['label' => 'D', 'text' => '3'],
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'Radial nodes = n - l - 1 = 3 - 1 - 1 = 1',
                    'difficulty' => 'medium',
                    'year' => 2023,
                    'language' => 'english',
                    'tags' => ['atomic-structure', 'quantum-numbers', 'pyq'],
                ],
                [
                    'subject' => 'Mathematics',
                    'topic' => 'Calculus',
                    'type' => 'mcq',
                    'question_text' => 'The value of ∫₀¹ x·eˣ dx is:',
                    'options' => [
                        ['label' => 'A', 'text' => '1'],
                        ['label' => 'B', 'text' => 'e'],
                        ['label' => 'C', 'text' => 'e - 1'],
                        ['label' => 'D', 'text' => '2e - 1'],
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'Using integration by parts: ∫x·eˣ dx = x·eˣ - eˣ. Evaluating from 0 to 1: (e - e) - (0 - 1) = 1',
                    'difficulty' => 'medium',
                    'year' => 2024,
                    'language' => 'english',
                    'tags' => ['calculus', 'integration', 'pyq'],
                ],
            ];

            foreach ($sampleQuestions as $q) {
                $jeeMain->questions()->create($q);
            }
        }
    }
}
