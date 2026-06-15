<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HomepageSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // ── Logo Settings ──
            ['key' => 'site_logo', 'value' => '', 'type' => 'image', 'group' => 'logo', 'order' => 1],
            ['key' => 'site_name', 'value' => 'BlinkStudy', 'type' => 'text', 'group' => 'logo', 'order' => 2],
            ['key' => 'logo_icon', 'value' => 'school', 'type' => 'text', 'group' => 'logo', 'order' => 3],

            // ── Hero Section ──
            ['key' => 'hero_badge_left', 'value' => 'Made in India', 'type' => 'text', 'group' => 'hero', 'order' => 1],
            ['key' => 'hero_badge_right', 'value' => 'Proudly Built for Indian Students', 'type' => 'text', 'group' => 'hero', 'order' => 2],
            ['key' => 'hero_title_line1', 'value' => 'Your AI Study', 'type' => 'text', 'group' => 'hero', 'order' => 3],
            ['key' => 'hero_title_highlight', 'value' => 'Companion', 'type' => 'text', 'group' => 'hero', 'order' => 4],
            ['key' => 'hero_description', 'value' => 'Stuck on a problem? Get instant, step-by-step explanations for Math, Science, and more. Scan, ask, learn — anytime.', 'type' => 'textarea', 'group' => 'hero', 'order' => 5],
            ['key' => 'hero_search_label', 'value' => 'DOUBT_SOLVER', 'type' => 'text', 'group' => 'hero', 'order' => 6],
            ['key' => 'hero_search_model', 'value' => 'MODEL: GEMINI-2.0', 'type' => 'text', 'group' => 'hero', 'order' => 7],
            ['key' => 'hero_search_placeholder', 'value' => 'Type your doubt here...', 'type' => 'text', 'group' => 'hero', 'order' => 8],
            ['key' => 'hero_cta_text', 'value' => 'Solve', 'type' => 'text', 'group' => 'hero', 'order' => 9],

            // ── Stats Section ──
            ['key' => 'stat1_value', 'value' => '10K+', 'type' => 'text', 'group' => 'stats', 'order' => 1],
            ['key' => 'stat1_label', 'value' => 'Students', 'type' => 'text', 'group' => 'stats', 'order' => 2],
            ['key' => 'stat2_value', 'value' => '50K+', 'type' => 'text', 'group' => 'stats', 'order' => 3],
            ['key' => 'stat2_label', 'value' => 'Solved', 'type' => 'text', 'group' => 'stats', 'order' => 4],
            ['key' => 'stat3_value', 'value' => '4.9', 'type' => 'text', 'group' => 'stats', 'order' => 5],
            ['key' => 'stat3_label', 'value' => 'Rating', 'type' => 'text', 'group' => 'stats', 'order' => 6],

            // ── Features Section ──
            ['key' => 'features_badge', 'value' => 'Learning Modules', 'type' => 'text', 'group' => 'features', 'order' => 1],
            ['key' => 'features_title', 'value' => 'Academic Capabilities', 'type' => 'text', 'group' => 'features', 'order' => 2],
            ['key' => 'features_description', 'value' => 'Everything you need to ace your exams and understand concepts deeply.', 'type' => 'textarea', 'group' => 'features', 'order' => 3],

            ['key' => 'feature1_icon', 'value' => 'photo_camera', 'type' => 'text', 'group' => 'features', 'order' => 4],
            ['key' => 'feature1_title', 'value' => 'Scan & Solve', 'type' => 'text', 'group' => 'features', 'order' => 5],
            ['key' => 'feature1_description', 'value' => 'Snap a photo of any problem and get instant step-by-step solutions in seconds.', 'type' => 'textarea', 'group' => 'features', 'order' => 6],
            ['key' => 'feature1_color', 'value' => 'primary', 'type' => 'text', 'group' => 'features', 'order' => 7],

            ['key' => 'feature2_icon', 'value' => 'quiz', 'type' => 'text', 'group' => 'features', 'order' => 8],
            ['key' => 'feature2_title', 'value' => 'AI Quizzes', 'type' => 'text', 'group' => 'features', 'order' => 9],
            ['key' => 'feature2_description', 'value' => 'Generate personalized quizzes from your notes, images, or any topic instantly.', 'type' => 'textarea', 'group' => 'features', 'order' => 10],
            ['key' => 'feature2_color', 'value' => 'secondary', 'type' => 'text', 'group' => 'features', 'order' => 11],

            ['key' => 'feature3_icon', 'value' => 'chat', 'type' => 'text', 'group' => 'features', 'order' => 12],
            ['key' => 'feature3_title', 'value' => 'AI Tutor Chat', 'type' => 'text', 'group' => 'features', 'order' => 13],
            ['key' => 'feature3_description', 'value' => 'Chat with AI to understand concepts and get homework help 24/7.', 'type' => 'textarea', 'group' => 'features', 'order' => 14],
            ['key' => 'feature3_color', 'value' => 'cyan-500', 'type' => 'text', 'group' => 'features', 'order' => 15],

            ['key' => 'feature4_icon', 'value' => 'video_library', 'type' => 'text', 'group' => 'features', 'order' => 16],
            ['key' => 'feature4_title', 'value' => 'Whiteboard Videos', 'type' => 'text', 'group' => 'features', 'order' => 17],
            ['key' => 'feature4_description', 'value' => 'Convert any topic into engaging AI-animated whiteboard explanation videos.', 'type' => 'textarea', 'group' => 'features', 'order' => 18],
            ['key' => 'feature4_color', 'value' => 'pink-500', 'type' => 'text', 'group' => 'features', 'order' => 19],

            // ── Subjects Section ──
            ['key' => 'subjects_badge', 'value' => 'Subject Coverage', 'type' => 'text', 'group' => 'subjects', 'order' => 1],
            ['key' => 'subjects_title', 'value' => 'Explore Topics', 'type' => 'text', 'group' => 'subjects', 'order' => 2],
            ['key' => 'subjects_description', 'value' => 'From STEM to Humanities, our AI models are trained for every major discipline.', 'type' => 'textarea', 'group' => 'subjects', 'order' => 3],

            ['key' => 'subject1_name', 'value' => 'Math', 'type' => 'text', 'group' => 'subjects', 'order' => 4],
            ['key' => 'subject1_icon', 'value' => 'functions', 'type' => 'text', 'group' => 'subjects', 'order' => 5],
            ['key' => 'subject1_color', 'value' => 'primary', 'type' => 'text', 'group' => 'subjects', 'order' => 6],

            ['key' => 'subject2_name', 'value' => 'Physics', 'type' => 'text', 'group' => 'subjects', 'order' => 7],
            ['key' => 'subject2_icon', 'value' => 'rocket_launch', 'type' => 'text', 'group' => 'subjects', 'order' => 8],
            ['key' => 'subject2_color', 'value' => 'secondary', 'type' => 'text', 'group' => 'subjects', 'order' => 9],

            ['key' => 'subject3_name', 'value' => 'Chemistry', 'type' => 'text', 'group' => 'subjects', 'order' => 10],
            ['key' => 'subject3_icon', 'value' => 'science', 'type' => 'text', 'group' => 'subjects', 'order' => 11],
            ['key' => 'subject3_color', 'value' => 'purple-500', 'type' => 'text', 'group' => 'subjects', 'order' => 12],

            ['key' => 'subject4_name', 'value' => 'Biology', 'type' => 'text', 'group' => 'subjects', 'order' => 13],
            ['key' => 'subject4_icon', 'value' => 'biotech', 'type' => 'text', 'group' => 'subjects', 'order' => 14],
            ['key' => 'subject4_color', 'value' => 'blue-500', 'type' => 'text', 'group' => 'subjects', 'order' => 15],

            ['key' => 'subject5_name', 'value' => 'English', 'type' => 'text', 'group' => 'subjects', 'order' => 16],
            ['key' => 'subject5_icon', 'value' => 'auto_stories', 'type' => 'text', 'group' => 'subjects', 'order' => 17],
            ['key' => 'subject5_color', 'value' => 'pink-500', 'type' => 'text', 'group' => 'subjects', 'order' => 18],

            ['key' => 'subject6_name', 'value' => 'History', 'type' => 'text', 'group' => 'subjects', 'order' => 19],
            ['key' => 'subject6_icon', 'value' => 'history_edu', 'type' => 'text', 'group' => 'subjects', 'order' => 20],
            ['key' => 'subject6_color', 'value' => 'cyan-500', 'type' => 'text', 'group' => 'subjects', 'order' => 21],

            // ── CTA Section ──
            ['key' => 'cta_badge', 'value' => 'STATUS: EXAM_READY', 'type' => 'text', 'group' => 'cta', 'order' => 1],
            ['key' => 'cta_title', 'value' => 'Ace Your Exams Today', 'type' => 'text', 'group' => 'cta', 'order' => 2],
            ['key' => 'cta_description', 'value' => 'Join thousands of students boosting their grades with AI-powered doubt solving, quizzes, and whiteboard videos.', 'type' => 'textarea', 'group' => 'cta', 'order' => 3],
            ['key' => 'cta_button_primary', 'value' => 'Start Free Trial', 'type' => 'text', 'group' => 'cta', 'order' => 4],
            ['key' => 'cta_button_secondary', 'value' => 'Browse Subjects', 'type' => 'text', 'group' => 'cta', 'order' => 5],
            ['key' => 'cta_badge1', 'value' => '95% Better Grades', 'type' => 'text', 'group' => 'cta', 'order' => 6],
            ['key' => 'cta_badge2', 'value' => 'Millions of Solutions', 'type' => 'text', 'group' => 'cta', 'order' => 7],
            ['key' => 'cta_badge3', 'value' => 'Interactive Learning', 'type' => 'text', 'group' => 'cta', 'order' => 8],

            // ── Pricing Section ──
            ['key' => 'pricing_title', 'value' => 'Choose Your Plan', 'type' => 'text', 'group' => 'pricing', 'order' => 1],
            ['key' => 'pricing_description', 'value' => 'Start free and upgrade as you grow. All plans include core features.', 'type' => 'textarea', 'group' => 'pricing', 'order' => 2],
        ];

        foreach ($settings as $setting) {
            \App\Models\HomepageSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
