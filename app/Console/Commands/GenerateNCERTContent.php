<?php

namespace App\Console\Commands;

use App\Models\QuestionBank;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateNCERTContent extends Command
{
    protected $signature = 'ncert:generate
                            {--class=10 : Class number (1-12)}
                            {--subject= : Specific subject}
                            {--chapter= : Specific chapter number}
                            {--questions=10 : Questions per chapter}
                            {--dry-run : Preview without saving}';

    protected $description = 'Generate NCERT textbook Q&A content for Answer Bank';

    // Complete NCERT Class 10 syllabus
    protected array $class10Syllabus = [
        'Mathematics' => [
            ['num' => '01', 'name' => 'Real Numbers', 'topics' => 'Euclid Division Lemma, Fundamental Theorem of Arithmetic, Irrational Numbers, Decimal Expansion'],
            ['num' => '02', 'name' => 'Polynomials', 'topics' => 'Zeros of Polynomial, Relationship between Zeros and Coefficients, Division Algorithm'],
            ['num' => '03', 'name' => 'Pair of Linear Equations in Two Variables', 'topics' => 'Graphical Method, Substitution Method, Elimination Method, Cross Multiplication'],
            ['num' => '04', 'name' => 'Quadratic Equations', 'topics' => 'Standard Form, Factorization Method, Completing Square, Quadratic Formula, Nature of Roots'],
            ['num' => '05', 'name' => 'Arithmetic Progressions', 'topics' => 'nth Term, Sum of n Terms, AP Applications'],
            ['num' => '06', 'name' => 'Triangles', 'topics' => 'Similar Triangles, Criteria for Similarity, Areas of Similar Triangles, Pythagoras Theorem'],
            ['num' => '07', 'name' => 'Coordinate Geometry', 'topics' => 'Distance Formula, Section Formula, Area of Triangle'],
            ['num' => '08', 'name' => 'Introduction to Trigonometry', 'topics' => 'Trigonometric Ratios, Ratios of Specific Angles, Trigonometric Identities'],
            ['num' => '09', 'name' => 'Some Applications of Trigonometry', 'topics' => 'Heights and Distances, Angle of Elevation, Angle of Depression'],
            ['num' => '10', 'name' => 'Circles', 'topics' => 'Tangent to Circle, Number of Tangents from Point'],
            ['num' => '11', 'name' => 'Constructions', 'topics' => 'Division of Line Segment, Construction of Similar Triangles, Tangents to Circle'],
            ['num' => '12', 'name' => 'Areas Related to Circles', 'topics' => 'Perimeter and Area of Circle, Sector and Segment'],
            ['num' => '13', 'name' => 'Surface Areas and Volumes', 'topics' => 'Combination of Solids, Conversion of Solids, Frustum of Cone'],
            ['num' => '14', 'name' => 'Statistics', 'topics' => 'Mean, Median, Mode of Grouped Data, Cumulative Frequency, Ogive'],
            ['num' => '15', 'name' => 'Probability', 'topics' => 'Classical Definition, Simple Problems'],
        ],
        'Science' => [
            ['num' => '01', 'name' => 'Chemical Reactions and Equations', 'topics' => 'Types of Chemical Reactions, Balancing Equations, Oxidation Reduction, Corrosion'],
            ['num' => '02', 'name' => 'Acids Bases and Salts', 'topics' => 'Properties of Acids and Bases, pH Scale, Neutralization, Importance of pH, Salts'],
            ['num' => '03', 'name' => 'Metals and Non-metals', 'topics' => 'Physical Properties, Chemical Properties, Reactivity Series, Extraction of Metals, Corrosion'],
            ['num' => '04', 'name' => 'Carbon and its Compounds', 'topics' => 'Covalent Bonding, Versatile Nature of Carbon, Homologous Series, Nomenclature, Chemical Properties'],
            ['num' => '05', 'name' => 'Periodic Classification of Elements', 'topics' => 'Early Attempts, Mendeleev Table, Modern Periodic Table, Trends in Periodic Table'],
            ['num' => '06', 'name' => 'Life Processes', 'topics' => 'Nutrition, Respiration, Transportation, Excretion in Plants and Animals'],
            ['num' => '07', 'name' => 'Control and Coordination', 'topics' => 'Nervous System, Reflex Action, Brain, Hormones in Animals, Plant Hormones'],
            ['num' => '08', 'name' => 'How do Organisms Reproduce', 'topics' => 'Asexual Reproduction, Sexual Reproduction, Reproductive Health'],
            ['num' => '09', 'name' => 'Heredity and Evolution', 'topics' => 'Mendel Laws, Sex Determination, Evolution, Speciation, Human Evolution'],
            ['num' => '10', 'name' => 'Light Reflection and Refraction', 'topics' => 'Reflection by Mirrors, Spherical Mirrors, Mirror Formula, Refraction, Lens Formula, Power of Lens'],
            ['num' => '11', 'name' => 'Human Eye and Colourful World', 'topics' => 'Human Eye, Defects of Vision, Refraction through Prism, Dispersion, Atmospheric Refraction, Scattering'],
            ['num' => '12', 'name' => 'Electricity', 'topics' => 'Electric Current, Potential Difference, Ohms Law, Resistance, Resistors in Series Parallel, Heating Effect, Electric Power'],
            ['num' => '13', 'name' => 'Magnetic Effects of Electric Current', 'topics' => 'Magnetic Field, Field due to Current, Force on Current, Electric Motor, Electromagnetic Induction, Generator, DC AC'],
            ['num' => '14', 'name' => 'Sources of Energy', 'topics' => 'Conventional Sources, Non-Conventional Sources, Environmental Consequences'],
            ['num' => '15', 'name' => 'Our Environment', 'topics' => 'Ecosystem, Food Chains, Ozone Layer, Waste Management'],
            ['num' => '16', 'name' => 'Management of Natural Resources', 'topics' => 'Conservation, Forests Wildlife, Water Management, Coal Petroleum'],
        ],
        'History' => [
            ['num' => '01', 'name' => 'The Rise of Nationalism in Europe', 'topics' => 'French Revolution, Making of Nationalism, Age of Revolutions, Unification of Italy Germany'],
            ['num' => '02', 'name' => 'Nationalism in India', 'topics' => 'First World War, Non-Cooperation Movement, Civil Disobedience, Sense of Collective Belonging'],
            ['num' => '03', 'name' => 'The Making of a Global World', 'topics' => 'Pre-Modern World, 19th Century, Inter-War Economy, Post-War World'],
            ['num' => '04', 'name' => 'The Age of Industrialisation', 'topics' => 'Before Industrial Revolution, Hand Labour, Industrialisation in Colonies, Factories'],
            ['num' => '05', 'name' => 'Print Culture and the Modern World', 'topics' => 'First Printed Books, Print Revolution, Reading Mania, Print and Censorship'],
        ],
        'Geography' => [
            ['num' => '01', 'name' => 'Resources and Development', 'topics' => 'Types of Resources, Development of Resources, Resource Planning, Land Resources, Land Degradation, Soil'],
            ['num' => '02', 'name' => 'Forest and Wildlife Resources', 'topics' => 'Flora Fauna, Conservation, Types of Species, Community Participation'],
            ['num' => '03', 'name' => 'Water Resources', 'topics' => 'Water Scarcity, Multi-Purpose Projects, Rainwater Harvesting'],
            ['num' => '04', 'name' => 'Agriculture', 'topics' => 'Types of Farming, Cropping Pattern, Major Crops, Technological Reforms, Food Security'],
            ['num' => '05', 'name' => 'Minerals and Energy Resources', 'topics' => 'Types of Minerals, Ferrous Non-Ferrous, Non-Metallic, Rock Minerals, Energy Resources'],
            ['num' => '06', 'name' => 'Manufacturing Industries', 'topics' => 'Importance, Industrial Location, Classification, Agro-Based, Mineral-Based, Industrial Pollution'],
            ['num' => '07', 'name' => 'Lifelines of National Economy', 'topics' => 'Transport, Roadways Railways, Pipelines, Waterways, Airways, Communication, Trade'],
        ],
        'Political Science' => [
            ['num' => '01', 'name' => 'Power Sharing', 'topics' => 'Belgium Sri Lanka, Majoritarianism, Accommodation, Power Sharing Forms'],
            ['num' => '02', 'name' => 'Federalism', 'topics' => 'Types of Federation, Indian Federalism, Decentralisation, Panchayati Raj'],
            ['num' => '03', 'name' => 'Democracy and Diversity', 'topics' => 'Origins of Social Differences, Overlapping Cross-Cutting, Politics of Social Divisions'],
            ['num' => '04', 'name' => 'Gender Religion and Caste', 'topics' => 'Gender Division, Women in Politics, Religion Communalism, Caste in Politics'],
            ['num' => '05', 'name' => 'Popular Struggles and Movements', 'topics' => 'Nepal Bolivia Movements, Mobilisation, Pressure Groups, Influence on Politics'],
            ['num' => '06', 'name' => 'Political Parties', 'topics' => 'Need for Parties, Party Systems, National Regional Parties, Challenges, Party Reforms'],
            ['num' => '07', 'name' => 'Outcomes of Democracy', 'topics' => 'Accountable Government, Economic Growth, Inequality, Social Diversity, Dignity Freedom'],
            ['num' => '08', 'name' => 'Challenges to Democracy', 'topics' => 'Challenge of Foundation, Expansion, Deepening, Political Reforms'],
        ],
        'Economics' => [
            ['num' => '01', 'name' => 'Development', 'topics' => 'Development Goals, Income and Other Goals, National Development, HDI, Sustainability'],
            ['num' => '02', 'name' => 'Sectors of the Indian Economy', 'topics' => 'Primary Secondary Tertiary, GDP, Historical Change, Employment, Organised Unorganised'],
            ['num' => '03', 'name' => 'Money and Credit', 'topics' => 'Money as Medium, Modern Forms, Loan Activities, Credit, Formal Informal Credit, SHG'],
            ['num' => '04', 'name' => 'Globalisation and the Indian Economy', 'topics' => 'MNCs, Foreign Trade, Globalisation, WTO, Impact, Fair Globalisation'],
            ['num' => '05', 'name' => 'Consumer Rights', 'topics' => 'Consumer Movement, Consumer Rights, Consumer Protection, RTI'],
        ],
    ];

    public function handle(): int
    {
        $class = (int) $this->option('class');
        $subject = $this->option('subject');
        $chapterNum = $this->option('chapter');
        $questionsPerChapter = (int) $this->option('questions');
        $dryRun = $this->option('dry-run');

        if ($class !== 10) {
            $this->warn("Currently only Class 10 is fully mapped. Running for Class 10...");
            $class = 10;
        }

        $syllabus = $this->class10Syllabus;

        // Filter by subject if specified
        if ($subject) {
            $syllabus = array_filter($syllabus, fn($key) => strtolower($key) === strtolower($subject), ARRAY_FILTER_USE_KEY);
        }

        $this->info("Generating NCERT Class {$class} Content");
        $this->info("Questions per chapter: {$questionsPerChapter}");
        $this->newLine();

        $totalGenerated = 0;
        $totalSkipped = 0;

        foreach ($syllabus as $subjectName => $chapters) {
            $this->info("[{$subjectName}] - " . count($chapters) . " chapters");

            // Filter by chapter if specified
            if ($chapterNum) {
                $chapters = array_filter($chapters, fn($ch) => $ch['num'] === str_pad($chapterNum, 2, '0', STR_PAD_LEFT));
            }

            $bar = $this->output->createProgressBar(count($chapters));
            $bar->start();

            foreach ($chapters as $chapter) {
                $result = $this->generateChapterContent($class, $subjectName, $chapter, $questionsPerChapter, $dryRun);
                $totalGenerated += $result['generated'];
                $totalSkipped += $result['skipped'];

                $bar->advance();

                // Rate limit
                if (!$dryRun && $result['api_called']) {
                    sleep(2);
                }
            }

            $bar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info("=" . str_repeat("=", 50));
        $this->info("Total Generated: {$totalGenerated}");
        $this->info("Total Skipped: {$totalSkipped}");
        $this->info("Total in Answer Bank: " . QuestionBank::count());

        return 0;
    }

    protected function generateChapterContent(int $class, string $subject, array $chapter, int $count, bool $dryRun): array
    {
        $examType = "NCERT Class {$class}";
        $chapterName = $chapter['name'];
        $topics = $chapter['topics'];

        // Check existing content
        $existing = QuestionBank::where('exam_type', $examType)
            ->where('subject', strtolower($subject))
            ->where('topic', $this->slugify($chapterName))
            ->count();

        if ($existing >= $count) {
            return ['generated' => 0, 'skipped' => 1, 'api_called' => false];
        }

        if ($dryRun) {
            return ['generated' => $count, 'skipped' => 0, 'api_called' => false];
        }

        // Generate content using AI
        try {
            $questions = $this->callAIForNCERT($class, $subject, $chapterName, $topics, $count);

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
                    'topic' => $this->slugify($chapterName),
                    'exam_type' => $examType,
                    'difficulty' => $qa['difficulty'] ?? 'medium',
                    'ai_model' => 'gemini-ncert',
                ]);
                $saved++;
            }

            return ['generated' => $saved, 'skipped' => 0, 'api_called' => true];

        } catch (\Exception $e) {
            Log::error("NCERT content generation failed", [
                'class' => $class,
                'subject' => $subject,
                'chapter' => $chapterName,
                'error' => $e->getMessage(),
            ]);
            return ['generated' => 0, 'skipped' => 1, 'api_called' => true];
        }
    }

    protected function callAIForNCERT(int $class, string $subject, string $chapter, string $topics, int $count): array
    {
        $apiKey = \App\Models\FrontendConfig::getValue('ai.gemini_api_key', '');

        if (!$apiKey) {
            throw new \Exception('Gemini API key not configured');
        }

        $prompt = <<<PROMPT
You are an expert NCERT textbook content creator for Indian CBSE students.

Generate exactly {$count} important questions and answers from:
- Class: {$class}
- Subject: {$subject}
- Chapter: {$chapter}
- Key Topics: {$topics}

IMPORTANT REQUIREMENTS:
1. Questions must be based on ACTUAL NCERT textbook content
2. Include a mix of:
   - 1-mark questions (definitions, fill in blanks)
   - 2-mark questions (short answers)
   - 3-mark questions (explanations with examples)
   - 5-mark questions (detailed answers with diagrams if needed)
3. Answers should be NCERT-style, exam-ready
4. Include formulas, diagrams description, and key points
5. Cover all the key topics mentioned

Return ONLY valid JSON:
{
  "questions": [
    {
      "question": "Define [concept] as per NCERT Class {$class} {$subject}.",
      "answer": "According to NCERT, [detailed answer with key points]...",
      "difficulty": "easy|medium|hard",
      "marks": 1|2|3|5
    }
  ]
}
PROMPT;

        $response = Http::timeout(60)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}",
            [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 8192,
                ],
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->status());
        }

        $data = $response->json();
        $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Parse JSON
        $parsed = json_decode($content, true);
        if ($parsed && isset($parsed['questions'])) {
            return $parsed['questions'];
        }

        // Try extracting JSON
        if (preg_match('/\{[\s\S]*"questions"[\s\S]*\}/m', $content, $m)) {
            $parsed = json_decode($m[0], true);
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
