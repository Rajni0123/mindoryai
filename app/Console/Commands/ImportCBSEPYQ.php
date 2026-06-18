<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ImportCBSEPYQ extends Command
{
    protected $signature = 'cbse:import-pyq
        {--class=all : Class to import (10, 12, or all)}
        {--subject=all : Subject to import (e.g., Mathematics)}
        {--year=all : Year to import (e.g., 2024)}
        {--scrape-first : Run Python scraper before importing}
        {--dry-run : Show what would be imported without actually importing}';

    protected $description = 'Import CBSE Previous Year Question papers from scraped JSON files into the database';

    private array $classMap = [
        '10' => ['slug' => 'cbse-class-10', 'dir' => 'class10'],
        '12' => ['slug' => 'cbse-class-12', 'dir' => 'class12'],
    ];

    private int $imported = 0;
    private int $skipped = 0;
    private int $failed = 0;

    public function handle(): int
    {
        $this->info('');
        $this->info('========================================');
        $this->info('  CBSE PYQ Import Tool');
        $this->info('========================================');
        $this->info('');

        // Run Python scraper first if requested
        if ($this->option('scrape-first')) {
            $this->runScraper();
        }

        $basePath = storage_path('app/cbse-pyq');

        if (!File::isDirectory($basePath)) {
            $this->error("PYQ data directory not found: {$basePath}");
            $this->info("Run the scraper first: python manim-server/services/cbse_pyq_scraper.py");
            return Command::FAILURE;
        }

        $targetClass = $this->option('class');
        $targetSubject = $this->option('subject');
        $targetYear = $this->option('year');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be imported');
            $this->info('');
        }

        // Determine which classes to process
        $classes = $targetClass === 'all'
            ? ['10', '12']
            : [$targetClass];

        foreach ($classes as $cls) {
            if (!isset($this->classMap[$cls])) {
                $this->error("Unknown class: {$cls}");
                continue;
            }

            $config = $this->classMap[$cls];
            $exam = Exam::where('slug', $config['slug'])->first();

            if (!$exam) {
                $this->error("Exam not found for slug: {$config['slug']}");
                $this->info("Run: php artisan db:seed --class=ExamSeeder");
                continue;
            }

            $classDir = $basePath . DIRECTORY_SEPARATOR . $config['dir'];
            if (!File::isDirectory($classDir)) {
                $this->warn("No data directory for Class {$cls}: {$classDir}");
                continue;
            }

            $this->info("Processing CBSE Class {$cls} (Exam ID: {$exam->id})...");

            // Get subject directories
            $subjectDirs = File::directories($classDir);

            foreach ($subjectDirs as $subjectDir) {
                $subject = basename($subjectDir);

                // Filter by subject if specified
                if ($targetSubject !== 'all' && strtolower($subject) !== strtolower($targetSubject)) {
                    continue;
                }

                // Get year JSON files
                $jsonFiles = File::glob($subjectDir . DIRECTORY_SEPARATOR . '*.json');

                foreach ($jsonFiles as $jsonFile) {
                    $year = (int) pathinfo($jsonFile, PATHINFO_FILENAME);

                    // Filter by year if specified
                    if ($targetYear !== 'all' && (int) $targetYear !== $year) {
                        continue;
                    }

                    $this->importFile($exam, $subject, $year, $jsonFile, $isDryRun);
                }
            }
        }

        $this->info('');
        $this->info('========================================');
        $this->info("  Import Complete");
        $this->info("  Imported: {$this->imported}");
        $this->info("  Skipped (duplicates): {$this->skipped}");
        $this->info("  Failed: {$this->failed}");
        $this->info('========================================');
        $this->info('');

        return Command::SUCCESS;
    }

    private function importFile(Exam $exam, string $subject, int $year, string $filePath, bool $isDryRun): void
    {
        $this->info("  [{$exam->name}] {$subject} {$year}...");

        $content = File::get($filePath);
        $questions = json_decode($content, true);

        if (!is_array($questions)) {
            $this->error("    Invalid JSON in: {$filePath}");
            $this->failed++;
            return;
        }

        $fileImported = 0;
        $fileSkipped = 0;

        foreach ($questions as $q) {
            $questionText = $q['question_text'] ?? $q['question'] ?? '';
            if (empty($questionText)) {
                $this->failed++;
                continue;
            }

            // Duplicate detection: check by exam_id + year + subject + question text
            $exists = ExamQuestion::where('exam_id', $exam->id)
                ->where('year', $year)
                ->where('subject', $subject)
                ->where('question_text', trim($questionText))
                ->exists();

            if ($exists) {
                $fileSkipped++;
                $this->skipped++;
                continue;
            }

            if ($isDryRun) {
                $fileImported++;
                $this->imported++;
                continue;
            }

            // Normalize options format
            $options = $q['options'] ?? [];
            if (is_array($options) && !empty($options)) {
                // Check if options are already in [{label, text}] format
                $first = $options[0] ?? null;
                if ($first && !isset($first['label'])) {
                    // Convert from {A: "...", B: "..."} format
                    $normalized = [];
                    foreach ($options as $label => $text) {
                        if (is_string($text)) {
                            $normalized[] = ['label' => (string) $label, 'text' => $text];
                        }
                    }
                    $options = $normalized;
                }
            }

            if (count($options) < 2) {
                $this->failed++;
                continue;
            }

            // Normalize correct answer
            $correctAnswer = strtoupper(trim($q['correct_answer'] ?? ''));
            if (!in_array($correctAnswer, ['A', 'B', 'C', 'D'])) {
                $this->failed++;
                continue;
            }

            // Normalize difficulty
            $difficulty = strtolower($q['difficulty'] ?? 'medium');
            if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
                $difficulty = 'medium';
            }

            try {
                ExamQuestion::create([
                    'exam_id' => $exam->id,
                    'subject' => $subject,
                    'topic' => $q['topic'] ?? null,
                    'year' => $year,
                    'type' => 'mcq',
                    'question_text' => trim($questionText),
                    'options' => $options,
                    'correct_answer' => $correctAnswer,
                    'explanation' => $q['explanation'] ?? null,
                    'solution_steps' => $q['solution_steps'] ?? null,
                    'difficulty' => $difficulty,
                    'language' => 'english',
                    'tags' => [
                        'pyq',
                        'real-paper',
                        (string) $year,
                        'cbse',
                        strtolower(str_replace(' ', '-', $q['topic'] ?? 'general')),
                    ],
                    'is_active' => true,
                ]);

                $fileImported++;
                $this->imported++;
            } catch (\Exception $e) {
                $this->failed++;
                Log::error("CBSE PYQ import failed", [
                    'exam' => $exam->name,
                    'subject' => $subject,
                    'year' => $year,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $action = $isDryRun ? 'Would import' : 'Imported';
        $this->info("    {$action}: {$fileImported}, Skipped: {$fileSkipped}");
    }

    private function runScraper(): void
    {
        $this->info('Running Python scraper...');
        $this->info('');

        $scraperPath = base_path('manim-server/services/cbse_pyq_scraper.py');

        if (!file_exists($scraperPath)) {
            $this->error("Scraper not found: {$scraperPath}");
            return;
        }

        $classArg = $this->option('class') !== 'all' ? "--class {$this->option('class')}" : "";
        $subjectArg = $this->option('subject') !== 'all' ? "--subject \"{$this->option('subject')}\"" : "";
        $yearArg = $this->option('year') !== 'all' ? "--year {$this->option('year')}" : "";

        $cmd = "python \"{$scraperPath}\" {$classArg} {$subjectArg} {$yearArg}";
        $this->info("  Running: {$cmd}");
        $this->info('');

        $returnCode = 0;
        passthru($cmd, $returnCode);

        if ($returnCode !== 0) {
            $this->warn("Scraper exited with code {$returnCode}");
        }

        $this->info('');
        $this->info('Scraper finished. Proceeding with import...');
        $this->info('');
    }
}
