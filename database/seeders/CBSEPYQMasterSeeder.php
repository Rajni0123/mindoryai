<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Master seeder for all CBSE Previous Year Questions.
 *
 * Usage:
 *   php artisan db:seed --class=CBSEPYQMasterSeeder
 *
 * This will:
 * 1. Ensure CBSE exams exist (ExamSeeder)
 * 2. Seed Class 10 real PYQ (Maths, Science) for 2020-2024
 * 3. Seed Class 12 real PYQ (Physics, Chemistry, Maths) for 2020-2024
 */
class CBSEPYQMasterSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('');
        $this->command?->info('========================================');
        $this->command?->info('  CBSE PYQ Master Seeder');
        $this->command?->info('  Real Board Exam Questions 2020-2024');
        $this->command?->info('========================================');
        $this->command?->info('');

        // Step 1: Ensure exams exist
        $this->command?->info('Step 1: Ensuring CBSE exams exist...');
        $this->call(ExamSeeder::class);

        // Step 2: Seed Class 10 PYQ
        $this->command?->info('');
        $this->command?->info('Step 2: Seeding CBSE Class 10 PYQ...');
        $this->call(CBSEClass10PYQSeeder::class);

        // Step 3: Seed Class 12 PYQ
        $this->command?->info('');
        $this->command?->info('Step 3: Seeding CBSE Class 12 PYQ...');
        $this->call(CBSEClass12PYQSeeder::class);

        // Summary
        $class10Count = \App\Models\ExamQuestion::whereHas('exam', fn($q) => $q->where('slug', 'cbse-class-10'))
            ->where('tags', 'like', '%real-paper%')
            ->count();

        $class12Count = \App\Models\ExamQuestion::whereHas('exam', fn($q) => $q->where('slug', 'cbse-class-12'))
            ->where('tags', 'like', '%real-paper%')
            ->count();

        $this->command?->info('');
        $this->command?->info('========================================');
        $this->command?->info('  CBSE PYQ Seeding Complete!');
        $this->command?->info("  Class 10 Questions: {$class10Count}");
        $this->command?->info("  Class 12 Questions: {$class12Count}");
        $this->command?->info("  Total: " . ($class10Count + $class12Count));
        $this->command?->info('========================================');
        $this->command?->info('');
    }
}
