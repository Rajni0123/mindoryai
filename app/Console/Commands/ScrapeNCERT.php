<?php

namespace App\Console\Commands;

use App\Models\QuestionBank;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScrapeNCERT extends Command
{
    protected $signature = 'ncert:scrape
                            {--class= : Specific class (1-12)}
                            {--subject= : Specific subject}
                            {--chapter= : Specific chapter number}
                            {--list : List available subjects}
                            {--dry-run : Preview without saving}';

    protected $description = 'Scrape NCERT textbook content and generate Q&A for Answer Bank';

    // NCERT Subjects by Class
    protected array $ncertSubjects = [
        // Primary (1-5)
        1 => ['Mathematics', 'English', 'Hindi'],
        2 => ['Mathematics', 'English', 'Hindi'],
        3 => ['Mathematics', 'English', 'Hindi', 'EVS'],
        4 => ['Mathematics', 'English', 'Hindi', 'EVS'],
        5 => ['Mathematics', 'English', 'Hindi', 'EVS'],

        // Middle (6-8)
        6 => ['Mathematics', 'Science', 'Social Science', 'English', 'Hindi'],
        7 => ['Mathematics', 'Science', 'Social Science', 'English', 'Hindi'],
        8 => ['Mathematics', 'Science', 'Social Science', 'English', 'Hindi'],

        // Secondary (9-10)
        9 => ['Mathematics', 'Science', 'Social Science', 'English', 'Hindi'],
        10 => ['Mathematics', 'Science', 'Social Science', 'English', 'Hindi'],

        // Senior Secondary (11-12)
        11 => ['Physics', 'Chemistry', 'Mathematics', 'Biology', 'English', 'Hindi', 'Economics', 'Accountancy', 'Business Studies', 'History', 'Geography', 'Political Science'],
        12 => ['Physics', 'Chemistry', 'Mathematics', 'Biology', 'English', 'Hindi', 'Economics', 'Accountancy', 'Business Studies', 'History', 'Geography', 'Political Science'],
    ];

    // Key topics per subject per class (condensed for important content)
    protected array $ncertTopics = [
        // Class 6
        '6_Mathematics' => [
            'Knowing Our Numbers', 'Whole Numbers', 'Playing with Numbers', 'Basic Geometrical Ideas',
            'Understanding Elementary Shapes', 'Integers', 'Fractions', 'Decimals', 'Data Handling',
            'Mensuration', 'Algebra', 'Ratio and Proportion', 'Symmetry', 'Practical Geometry'
        ],
        '6_Science' => [
            'Food: Where Does It Come From?', 'Components of Food', 'Fibre to Fabric', 'Sorting Materials',
            'Separation of Substances', 'Changes Around Us', 'Getting to Know Plants', 'Body Movements',
            'The Living Organisms', 'Motion and Measurement', 'Light Shadows Reflections', 'Electricity and Circuits',
            'Fun with Magnets', 'Water', 'Air Around Us', 'Garbage In Garbage Out'
        ],

        // Class 7
        '7_Mathematics' => [
            'Integers', 'Fractions and Decimals', 'Data Handling', 'Simple Equations', 'Lines and Angles',
            'The Triangle and its Properties', 'Congruence of Triangles', 'Comparing Quantities',
            'Rational Numbers', 'Practical Geometry', 'Perimeter and Area', 'Algebraic Expressions',
            'Exponents and Powers', 'Symmetry', 'Visualising Solid Shapes'
        ],
        '7_Science' => [
            'Nutrition in Plants', 'Nutrition in Animals', 'Fibre to Fabric', 'Heat', 'Acids Bases and Salts',
            'Physical and Chemical Changes', 'Weather Climate Adaptations', 'Winds Storms Cyclones',
            'Soil', 'Respiration in Organisms', 'Transportation in Animals and Plants', 'Reproduction in Plants',
            'Motion and Time', 'Electric Current and Its Effects', 'Light', 'Water: A Precious Resource', 'Forests'
        ],

        // Class 8
        '8_Mathematics' => [
            'Rational Numbers', 'Linear Equations in One Variable', 'Understanding Quadrilaterals',
            'Practical Geometry', 'Data Handling', 'Squares and Square Roots', 'Cubes and Cube Roots',
            'Comparing Quantities', 'Algebraic Expressions and Identities', 'Visualising Solid Shapes',
            'Mensuration', 'Exponents and Powers', 'Direct and Inverse Proportions', 'Factorisation',
            'Introduction to Graphs', 'Playing with Numbers'
        ],
        '8_Science' => [
            'Crop Production and Management', 'Microorganisms', 'Synthetic Fibres and Plastics', 'Materials: Metals and Non-Metals',
            'Coal and Petroleum', 'Combustion and Flame', 'Conservation of Plants and Animals', 'Cell Structure and Functions',
            'Reproduction in Animals', 'Reaching the Age of Adolescence', 'Force and Pressure', 'Friction', 'Sound',
            'Chemical Effects of Electric Current', 'Some Natural Phenomena', 'Light', 'Stars and Solar System', 'Pollution'
        ],

        // Class 9
        '9_Mathematics' => [
            'Number Systems', 'Polynomials', 'Coordinate Geometry', 'Linear Equations in Two Variables',
            'Introduction to Euclids Geometry', 'Lines and Angles', 'Triangles', 'Quadrilaterals',
            'Areas of Parallelograms and Triangles', 'Circles', 'Constructions', 'Herons Formula',
            'Surface Areas and Volumes', 'Statistics', 'Probability'
        ],
        '9_Science' => [
            'Matter in Our Surroundings', 'Is Matter Around Us Pure', 'Atoms and Molecules', 'Structure of the Atom',
            'The Fundamental Unit of Life', 'Tissues', 'Diversity in Living Organisms', 'Motion', 'Force and Laws of Motion',
            'Gravitation', 'Work and Energy', 'Sound', 'Why Do We Fall Ill', 'Natural Resources', 'Improvement in Food Resources'
        ],

        // Class 10
        '10_Mathematics' => [
            'Real Numbers', 'Polynomials', 'Pair of Linear Equations in Two Variables', 'Quadratic Equations',
            'Arithmetic Progressions', 'Triangles', 'Coordinate Geometry', 'Introduction to Trigonometry',
            'Some Applications of Trigonometry', 'Circles', 'Constructions', 'Areas Related to Circles',
            'Surface Areas and Volumes', 'Statistics', 'Probability'
        ],
        '10_Science' => [
            'Chemical Reactions and Equations', 'Acids Bases and Salts', 'Metals and Non-metals', 'Carbon and its Compounds',
            'Periodic Classification of Elements', 'Life Processes', 'Control and Coordination', 'How do Organisms Reproduce',
            'Heredity and Evolution', 'Light Reflection and Refraction', 'Human Eye and Colourful World', 'Electricity',
            'Magnetic Effects of Electric Current', 'Sources of Energy', 'Our Environment', 'Management of Natural Resources'
        ],

        // Class 11 Physics
        '11_Physics' => [
            'Physical World', 'Units and Measurements', 'Motion in a Straight Line', 'Motion in a Plane',
            'Laws of Motion', 'Work Energy and Power', 'System of Particles and Rotational Motion', 'Gravitation',
            'Mechanical Properties of Solids', 'Mechanical Properties of Fluids', 'Thermal Properties of Matter',
            'Thermodynamics', 'Kinetic Theory', 'Oscillations', 'Waves'
        ],
        '11_Chemistry' => [
            'Some Basic Concepts of Chemistry', 'Structure of Atom', 'Classification of Elements and Periodicity',
            'Chemical Bonding and Molecular Structure', 'States of Matter', 'Thermodynamics', 'Equilibrium',
            'Redox Reactions', 'Hydrogen', 's-Block Elements', 'p-Block Elements', 'Organic Chemistry Basic Principles',
            'Hydrocarbons', 'Environmental Chemistry'
        ],
        '11_Mathematics' => [
            'Sets', 'Relations and Functions', 'Trigonometric Functions', 'Principle of Mathematical Induction',
            'Complex Numbers and Quadratic Equations', 'Linear Inequalities', 'Permutations and Combinations',
            'Binomial Theorem', 'Sequences and Series', 'Straight Lines', 'Conic Sections', 'Introduction to Three Dimensional Geometry',
            'Limits and Derivatives', 'Mathematical Reasoning', 'Statistics', 'Probability'
        ],
        '11_Biology' => [
            'The Living World', 'Biological Classification', 'Plant Kingdom', 'Animal Kingdom',
            'Morphology of Flowering Plants', 'Anatomy of Flowering Plants', 'Structural Organisation in Animals',
            'Cell: The Unit of Life', 'Biomolecules', 'Cell Cycle and Cell Division', 'Transport in Plants',
            'Mineral Nutrition', 'Photosynthesis in Higher Plants', 'Respiration in Plants', 'Plant Growth and Development',
            'Digestion and Absorption', 'Breathing and Exchange of Gases', 'Body Fluids and Circulation',
            'Excretory Products and their Elimination', 'Locomotion and Movement', 'Neural Control and Coordination',
            'Chemical Coordination and Integration'
        ],

        // Class 12 Physics
        '12_Physics' => [
            'Electric Charges and Fields', 'Electrostatic Potential and Capacitance', 'Current Electricity',
            'Moving Charges and Magnetism', 'Magnetism and Matter', 'Electromagnetic Induction',
            'Alternating Current', 'Electromagnetic Waves', 'Ray Optics and Optical Instruments',
            'Wave Optics', 'Dual Nature of Radiation and Matter', 'Atoms', 'Nuclei', 'Semiconductor Electronics'
        ],
        '12_Chemistry' => [
            'The Solid State', 'Solutions', 'Electrochemistry', 'Chemical Kinetics', 'Surface Chemistry',
            'General Principles of Isolation of Elements', 'The p-Block Elements', 'The d and f Block Elements',
            'Coordination Compounds', 'Haloalkanes and Haloarenes', 'Alcohols Phenols and Ethers',
            'Aldehydes Ketones and Carboxylic Acids', 'Amines', 'Biomolecules', 'Polymers', 'Chemistry in Everyday Life'
        ],
        '12_Mathematics' => [
            'Relations and Functions', 'Inverse Trigonometric Functions', 'Matrices', 'Determinants',
            'Continuity and Differentiability', 'Application of Derivatives', 'Integrals', 'Application of Integrals',
            'Differential Equations', 'Vector Algebra', 'Three Dimensional Geometry', 'Linear Programming', 'Probability'
        ],
        '12_Biology' => [
            'Reproduction in Organisms', 'Sexual Reproduction in Flowering Plants', 'Human Reproduction',
            'Reproductive Health', 'Principles of Inheritance and Variation', 'Molecular Basis of Inheritance',
            'Evolution', 'Human Health and Disease', 'Strategies for Enhancement in Food Production',
            'Microbes in Human Welfare', 'Biotechnology Principles and Processes', 'Biotechnology and Its Applications',
            'Organisms and Populations', 'Ecosystem', 'Biodiversity and Conservation', 'Environmental Issues'
        ],
    ];

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listSubjects();
        }

        $class = $this->option('class');
        $subject = $this->option('subject');
        $dryRun = $this->option('dry-run');

        if ($class) {
            // Process specific class
            $this->processClass((int) $class, $subject, $dryRun);
        } else {
            // Process all classes (6-12 for academic content)
            $this->info("Processing NCERT content for Classes 6-12...\n");
            foreach ([6, 7, 8, 9, 10, 11, 12] as $c) {
                $this->processClass($c, $subject, $dryRun);
            }
        }

        $this->newLine();
        $this->info("Total questions in Answer Bank: " . QuestionBank::count());
        return 0;
    }

    protected function listSubjects(): int
    {
        $this->info("NCERT Subjects by Class:\n");
        foreach ($this->ncertSubjects as $class => $subjects) {
            $this->line("Class {$class}: " . implode(', ', $subjects));
        }
        return 0;
    }

    protected function processClass(int $class, ?string $filterSubject, bool $dryRun): void
    {
        $subjects = $this->ncertSubjects[$class] ?? [];

        if ($filterSubject) {
            $subjects = array_filter($subjects, fn($s) => strtolower($s) === strtolower($filterSubject));
        }

        $this->info("Processing Class {$class}...");

        foreach ($subjects as $subject) {
            $this->processSubject($class, $subject, $dryRun);
        }
    }

    protected function processSubject(int $class, string $subject, bool $dryRun): void
    {
        $key = "{$class}_{$subject}";
        $topics = $this->ncertTopics[$key] ?? [];

        if (empty($topics)) {
            $this->warn("  No topics defined for Class {$class} {$subject}");
            return;
        }

        $this->line("  {$subject} ({$class}): " . count($topics) . " chapters");

        $bar = $this->output->createProgressBar(count($topics));
        $bar->start();

        $generated = 0;
        $skipped = 0;

        foreach ($topics as $index => $topic) {
            $chapterNum = $index + 1;

            // Generate Q&A for this chapter
            $result = $this->generateChapterQA($class, $subject, $chapterNum, $topic, $dryRun);

            if ($result['generated'] > 0) {
                $generated += $result['generated'];
            } else {
                $skipped++;
            }

            $bar->advance();

            // Rate limit between API calls
            if (!$dryRun && $result['api_called']) {
                sleep(1);
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("    Generated: {$generated} | Skipped: {$skipped}");
    }

    protected function generateChapterQA(int $class, string $subject, int $chapter, string $topic, bool $dryRun): array
    {
        // Check if we already have content for this chapter
        $existingCount = QuestionBank::where('exam_type', "NCERT Class {$class}")
            ->where('subject', strtolower($subject))
            ->where('topic', $this->slugify($topic))
            ->count();

        if ($existingCount >= 5) {
            // Already have enough content for this chapter
            return ['generated' => 0, 'api_called' => false];
        }

        if ($dryRun) {
            $this->line("    [DRY RUN] Would generate Q&A for: Class {$class} {$subject} Ch{$chapter} - {$topic}");
            return ['generated' => 1, 'api_called' => false];
        }

        // Generate Q&A using AI
        try {
            $questions = $this->callAIForChapter($class, $subject, $chapter, $topic);

            $saved = 0;
            foreach ($questions as $qa) {
                if (empty($qa['question']) || empty($qa['answer'])) continue;

                $normalized = QuestionBank::normalize($qa['question']);
                $hash = hash('sha256', $normalized);

                if (QuestionBank::where('question_hash', $hash)->exists()) {
                    continue;
                }

                QuestionBank::create([
                    'original_question' => $qa['question'],
                    'normalized_question' => $normalized,
                    'answer' => $qa['answer'],
                    'question_hash' => $hash,
                    'keywords' => QuestionBank::extractKeywords($normalized),
                    'subject' => strtolower($subject),
                    'topic' => $this->slugify($topic),
                    'exam_type' => "NCERT Class {$class}",
                    'difficulty' => $qa['difficulty'] ?? 'medium',
                    'ai_model' => 'gemini-ncert',
                ]);
                $saved++;
            }

            return ['generated' => $saved, 'api_called' => true];

        } catch (\Exception $e) {
            Log::error("NCERT scrape failed", [
                'class' => $class,
                'subject' => $subject,
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            return ['generated' => 0, 'api_called' => true];
        }
    }

    protected function callAIForChapter(int $class, string $subject, int $chapter, string $topic): array
    {
        $apiKey = \App\Models\FrontendConfig::getValue('ai.gemini_api_key', '');

        if (!$apiKey) {
            throw new \Exception('Gemini API key not configured');
        }

        $prompt = <<<PROMPT
You are an expert NCERT textbook content generator. Generate 5 important questions and detailed answers from NCERT Class {$class} {$subject} - Chapter {$chapter}: {$topic}.

Requirements:
1. Questions should be based on actual NCERT textbook content
2. Include definitions, concepts, formulas (if applicable), and important points
3. Answers should be exam-ready and comprehensive
4. Mix of 1-mark, 2-mark, and 5-mark type questions
5. Include diagrams/flowcharts description where applicable

Return ONLY valid JSON in this exact format:
{
  "questions": [
    {
      "question": "What is [concept]? Explain with examples.",
      "answer": "Detailed NCERT-style answer with key points, formulas, examples...",
      "difficulty": "easy|medium|hard",
      "marks": 1|2|3|5
    }
  ]
}
PROMPT;

        $response = Http::timeout(45)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}",
            [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 4096,
                ],
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->status());
        }

        $data = $response->json();
        $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Parse JSON response
        $parsed = json_decode($content, true);
        if ($parsed && isset($parsed['questions'])) {
            return $parsed['questions'];
        }

        // Try to extract JSON from markdown
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/', $content, $m)) {
            $parsed = json_decode($m[1], true);
            if ($parsed && isset($parsed['questions'])) {
                return $parsed['questions'];
            }
        }

        return [];
    }

    protected function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '_', $text);
        return trim($text, '_');
    }
}
