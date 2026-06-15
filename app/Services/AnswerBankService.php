<?php

namespace App\Services;

use App\Models\QuestionBank;
use App\Models\AnswerBankStats;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnswerBankService
{
    /**
     * Main method - Get answer from cache or API
     * Returns ['answer' => string, 'source' => 'cache'|'api', 'subject' => string|null]
     */
    public function getAnswer(string $question, array $context = []): array
    {
        // Step 1: Try to find in database
        $cached = QuestionBank::findMatch($question);

        if ($cached) {
            AnswerBankStats::track(true);

            Log::info('AnswerBank: Cache HIT', [
                'question' => $question,
                'matched_id' => $cached->id,
                'hit_count' => $cached->hit_count,
            ]);

            return [
                'answer' => $cached->answer,
                'source' => 'cache',
                'question_id' => $cached->id,
                'subject' => $cached->subject,
                'topic' => $cached->topic,
                'hit_count' => $cached->hit_count,
            ];
        }

        // Step 2: Not found - call AI API
        Log::info('AnswerBank: Cache MISS - calling API', ['question' => $question]);

        $aiResponse = $this->callAI($question, $context);

        if ($aiResponse['success']) {
            // Step 3: Auto-detect subject and topic
            $meta = $this->detectMeta($question, $aiResponse['answer']);

            // Step 4: Store in database for future use
            $stored = QuestionBank::storeAnswer($question, $aiResponse['answer'], array_merge($meta, [
                'ai_model' => $aiResponse['model'] ?? 'gemini',
                'exam_type' => $context['exam_type'] ?? null,
            ]));

            AnswerBankStats::track(false);

            return [
                'answer' => $aiResponse['answer'],
                'source' => 'api',
                'question_id' => $stored->id,
                'subject' => $meta['subject'] ?? null,
                'topic' => $meta['topic'] ?? null,
                'hit_count' => 0,
            ];
        }

        // API failed
        return [
            'answer' => 'Sorry, unable to process your question right now. Please try again.',
            'source' => 'error',
            'question_id' => null,
            'subject' => null,
            'topic' => null,
            'hit_count' => 0,
        ];
    }

    /**
     * Call Gemini API (primary) with GPT as fallback
     */
    protected function callAI(string $question, array $context = []): array
    {
        $examType = $context['exam_type'] ?? 'JEE/NEET';

        $systemPrompt = "You are an expert {$examType} tutor for Indian students. "
            . "Give clear, concise, exam-focused answers. "
            . "Include formulas, key points, and tricks where applicable. "
            . "Use simple English that Indian students can understand. "
            . "If it's a numerical problem, show step-by-step solution. "
            . "Format the answer well with proper headings and bullet points.";

        // Try Gemini first (cheaper)
        try {
            $response = $this->callGemini($question, $systemPrompt);
            if ($response) {
                return ['success' => true, 'answer' => $response, 'model' => 'gemini'];
            }
        } catch (\Exception $e) {
            Log::warning('Gemini API failed', ['error' => $e->getMessage()]);
        }

        // Fallback to GPT
        try {
            $response = $this->callGPT($question, $systemPrompt);
            if ($response) {
                return ['success' => true, 'answer' => $response, 'model' => 'gpt'];
            }
        } catch (\Exception $e) {
            Log::warning('GPT API failed', ['error' => $e->getMessage()]);
        }

        return ['success' => false, 'answer' => null, 'model' => null];
    }

    /**
     * Call Gemini API
     */
    protected function callGemini(string $question, string $systemPrompt): ?string
    {
        $apiKey = \App\Models\FrontendConfig::getValue('ai.gemini_api_key', '');

        if (!$apiKey) {
            throw new \Exception('Gemini API key not configured');
        }

        $response = Http::timeout(30)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}",
            [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]]
                ],
                'contents' => [
                    [
                        'parts' => [['text' => $question]]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3, // Low temperature for consistent answers
                    'maxOutputTokens' => 2048,
                ],
            ]
        );

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        throw new \Exception('Gemini API error: ' . $response->status());
    }

    /**
     * Call GPT API (fallback)
     */
    protected function callGPT(string $question, string $systemPrompt): ?string
    {
        $apiKey = \App\Models\FrontendConfig::getValue('ai.openai_api_key', '');

        if (!$apiKey) {
            throw new \Exception('OpenAI API key not configured');
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'max_tokens' => 2048,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $question]
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? null;
        }

        throw new \Exception('OpenAI API error: ' . $response->status());
    }

    /**
     * Auto-detect subject and topic from question
     */
    protected function detectMeta(string $question, string $answer): array
    {
        $q = mb_strtolower($question . ' ' . $answer);

        // Subject detection
        $subjectMap = [
            'physics' => ['force', 'energy', 'newton', 'velocity', 'acceleration', 'momentum', 'wave',
                         'electric', 'magnetic', 'current', 'resistance', 'capacitor', 'inductor',
                         'thermodynamic', 'heat', 'temperature', 'optic', 'lens', 'mirror', 'refraction',
                         'gravity', 'friction', 'torque', 'angular', 'oscillation', 'pendulum',
                         'semiconductor', 'photoelectric', 'nuclear', 'radioactive', 'quantum'],

            'chemistry' => ['atom', 'molecule', 'element', 'compound', 'reaction', 'acid', 'base',
                           'salt', 'solution', 'oxidation', 'reduction', 'redox', 'electrochemist',
                           'organic', 'inorganic', 'hydrocarbon', 'polymer', 'alkane', 'alkene',
                           'periodic table', 'electron configuration', 'bond', 'ionic', 'covalent',
                           'molar', 'molarity', 'stoichiometr', 'equilibrium', 'catalyst', 'ph'],

            'biology' => ['cell', 'dna', 'rna', 'protein', 'enzyme', 'gene', 'chromosome',
                         'mitosis', 'meiosis', 'photosynthesis', 'respiration', 'digestion',
                         'nervous', 'endocrine', 'hormone', 'blood', 'heart', 'kidney', 'liver',
                         'ecology', 'ecosystem', 'evolution', 'species', 'taxonomy', 'plant',
                         'animal', 'bacteria', 'virus', 'fungi', 'immune', 'antibiotic',
                         'reproduction', 'embryo', 'genetic', 'mutation', 'biotechnology'],

            'mathematics' => ['equation', 'function', 'derivative', 'integral', 'calculus', 'limit',
                            'matrix', 'determinant', 'vector', 'probability', 'statistics', 'permutation',
                            'combination', 'trigonometry', 'sin', 'cos', 'tan', 'logarithm',
                            'polynomial', 'quadratic', 'linear', 'geometric', 'arithmetic', 'sequence',
                            'set theory', 'complex number', 'conic', 'parabola', 'ellipse', 'hyperbola',
                            'differentiat', 'integrat', 'binomial', 'coordinate geometry'],
        ];

        $subject = null;
        $maxScore = 0;
        foreach ($subjectMap as $subj => $keywords) {
            $score = 0;
            foreach ($keywords as $kw) {
                if (str_contains($q, $kw)) $score++;
            }
            if ($score > $maxScore) {
                $maxScore = $score;
                $subject = $subj;
            }
        }

        // Topic detection (simplified - expand as needed)
        $topicMap = [
            'newton_laws' => ['newton', 'f=ma', 'inertia', 'action reaction'],
            'thermodynamics' => ['thermodynamic', 'entropy', 'enthalpy', 'heat engine', 'carnot'],
            'electrostatics' => ['coulomb', 'electric field', 'charge', 'capacitor', 'dielectric'],
            'optics' => ['lens', 'mirror', 'refraction', 'reflection', 'prism', 'snell'],
            'organic_chemistry' => ['organic', 'hydrocarbon', 'alkane', 'alkene', 'benzene', 'alcohol'],
            'periodic_table' => ['periodic', 'element', 'group', 'period', 'electron configuration'],
            'cell_biology' => ['cell', 'mitochondria', 'nucleus', 'ribosome', 'golgi', 'membrane'],
            'genetics' => ['gene', 'dna', 'rna', 'mutation', 'heredit', 'mendel', 'chromosome'],
            'calculus' => ['derivative', 'integral', 'differentiat', 'integrat', 'limit'],
            'algebra' => ['equation', 'polynomial', 'quadratic', 'linear', 'matrix'],
            'trigonometry' => ['trigonometr', 'sin', 'cos', 'tan', 'angle'],
            'probability' => ['probability', 'permutation', 'combination', 'bayes'],
        ];

        $topic = null;
        $maxTopicScore = 0;
        foreach ($topicMap as $t => $keywords) {
            $score = 0;
            foreach ($keywords as $kw) {
                if (str_contains($q, $kw)) $score++;
            }
            if ($score > $maxTopicScore) {
                $maxTopicScore = $score;
                $topic = $t;
            }
        }

        return [
            'subject' => $subject,
            'topic' => $topic,
        ];
    }

    /**
     * Bulk import questions from a JSON/CSV file
     */
    public function bulkImport(string $filePath): array
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $imported = 0;
        $skipped = 0;

        if ($extension === 'json') {
            $data = json_decode(file_get_contents($filePath), true);

            foreach ($data as $item) {
                $normalized = QuestionBank::normalize($item['question']);
                $hash = hash('sha256', $normalized);

                if (QuestionBank::where('question_hash', $hash)->exists()) {
                    $skipped++;
                    continue;
                }

                QuestionBank::storeAnswer(
                    $item['question'],
                    $item['answer'],
                    [
                        'subject' => $item['subject'] ?? null,
                        'topic' => $item['topic'] ?? null,
                        'exam_type' => $item['exam_type'] ?? null,
                        'difficulty' => $item['difficulty'] ?? null,
                        'ai_model' => 'pre-imported',
                    ]
                );
                $imported++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }
}
