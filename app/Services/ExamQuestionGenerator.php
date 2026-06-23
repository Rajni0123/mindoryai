<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\FrontendConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generates PYQ-style exam questions using admin-enabled AI (OpenAI/GPT first, Gemini fallback).
 */
class ExamQuestionGenerator
{
    public function generate(
        Exam $exam,
        string $subject = 'all',
        int $year = 2024,
        int $count = 10,
        string $difficulty = 'mixed',
        string $language = 'english',
    ): int {
        $language = $this->normalizeLanguage($language);
        $subjects = $subject === 'all'
            ? ($exam->subjects ?? ['General'])
            : [$subject];

        $perSubject = max(1, intdiv($count, count($subjects)));
        $totalSaved = 0;

        foreach ($subjects as $subj) {
            try {
                $questions = $this->generateForSubject($exam, $subj, $year, $perSubject, $difficulty, $language);
                $saved = $this->saveQuestions($exam, $questions, $subj, $year, $language);
                $totalSaved += $saved;
            } catch (\Exception $e) {
                Log::error("ExamQuestionGenerator failed for {$exam->name}/{$subj}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $totalSaved;
    }

    private function generateForSubject(Exam $exam, string $subject, int $year, int $count, string $difficulty, string $language): array
    {
        $prompt = $this->buildPrompt($exam, $subject, $year, $count, $difficulty, $language);
        $errors = [];

        if ($this->isOpenAiEnabled()) {
            try {
                $content = $this->generateWithOpenAi($prompt, $count, $language);
                $questions = $this->parseResponse($content);
                if ($questions !== []) {
                    Log::info('ExamQuestionGenerator: OpenAI generation succeeded', [
                        'exam' => $exam->name,
                        'subject' => $subject,
                        'count' => count($questions),
                    ]);

                    return $questions;
                }
                $errors[] = 'openai: empty or invalid JSON';
            } catch (\Throwable $e) {
                $errors[] = 'openai: ' . $e->getMessage();
                Log::warning('ExamQuestionGenerator: OpenAI failed', ['error' => $e->getMessage()]);
            }
        }

        if ($this->isGeminiEnabled()) {
            try {
                $content = $this->generateWithGemini($prompt, $count);
                $questions = $this->parseResponse($content);
                if ($questions !== []) {
                    Log::info('ExamQuestionGenerator: Gemini generation succeeded', [
                        'exam' => $exam->name,
                        'subject' => $subject,
                        'count' => count($questions),
                    ]);

                    return $questions;
                }
                $errors[] = 'gemini: empty or invalid JSON';
            } catch (\Throwable $e) {
                $errors[] = 'gemini: ' . $e->getMessage();
                Log::warning('ExamQuestionGenerator: Gemini failed', ['error' => $e->getMessage()]);
            }
        }

        if ($errors !== []) {
            Log::error('ExamQuestionGenerator: no AI provider produced questions', [
                'exam' => $exam->name,
                'subject' => $subject,
                'errors' => $errors,
            ]);
        }

        return [];
    }

    private function buildPrompt(Exam $exam, string $subject, int $year, int $count, string $difficulty, string $language): string
    {
        $markingScheme = $exam->config['marking_scheme'] ?? ['correct' => 4, 'wrong' => -1];
        $difficultyInstruction = $difficulty === 'mixed'
            ? 'Mix of easy (20%), medium (60%), and hard (20%) questions.'
            : "All questions should be {$difficulty} difficulty.";
        $languageInstruction = match ($language) {
            'hindi' => '8. Write ALL questions, all four options, and explanations entirely in Hindi using Devanagari script (हिंदी). Use standard Indian competitive exam Hindi terminology.',
            'hinglish' => '8. Write ALL questions, options, and explanations in Hinglish (Hindi in Roman/Latin script with common English exam terms where natural).',
            default => '8. Write all questions, options, and explanations in clear English suitable for Indian competitive exams.',
        };

        return <<<PROMPT
You are an expert Indian competitive exam question paper setter.

Generate exactly {$count} MCQ questions for the **{$exam->name}** exam.
Subject: **{$subject}**
Year pattern: **{$year}** (Previous Year Question pattern)
{$difficultyInstruction}

IMPORTANT RULES:
1. Questions MUST follow the exact pattern, difficulty, and style of real {$exam->name} {$year} papers.
2. Each question must have exactly 4 options (A, B, C, D).
3. Include detailed explanation for the correct answer.
4. Cover different topics within {$subject} (don't repeat the same topic).
5. Questions should be challenging and exam-realistic — not textbook-basic.
6. For numerical questions, include proper units and significant figures.
7. Marking scheme: +{$markingScheme['correct']} for correct, {$markingScheme['wrong']} for wrong.
{$languageInstruction}

Return ONLY valid JSON in this exact format, no other text:
{
  "questions": [
    {
      "question": "The complete question text here",
      "topic": "Specific topic name (e.g., Mechanics, Organic Chemistry, Calculus)",
      "options": {
        "A": "Option A text",
        "B": "Option B text",
        "C": "Option C text",
        "D": "Option D text"
      },
      "correct_answer": "B",
      "explanation": "Detailed step-by-step explanation of why this answer is correct",
      "difficulty": "easy|medium|hard"
    }
  ]
}
PROMPT;
    }

    private function generateWithOpenAi(string $prompt, int $count, string $language = 'english'): string
    {
        $apiKey = $this->resolveOpenAiApiKey();
        if ($apiKey === '') {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        $model = (string) (FrontendConfig::getValue('ai.openai_model', '')
            ?: config('ai.quiz_model', 'gpt-4o-mini'));

        $systemContent = match ($language) {
            'hindi' => 'You generate Indian competitive exam MCQs in Hindi (Devanagari script). Return ONLY valid JSON with a top-level "questions" array. No markdown.',
            'hinglish' => 'You generate Indian competitive exam MCQs in Hinglish (Roman Hindi). Return ONLY valid JSON with a top-level "questions" array. No markdown.',
            default => 'You generate Indian competitive exam MCQs. Return ONLY valid JSON with a top-level "questions" array. No markdown.',
        };

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->withOptions(['verify' => config('app.env') !== 'local'])
            ->connectTimeout(8)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemContent,
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.75,
                'max_tokens' => max(4096, $count * 600),
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('OpenAI API error: ' . $error);
        }

        return (string) ($response->json('choices.0.message.content') ?? '');
    }

    private function generateWithGemini(string $prompt, int $count): string
    {
        $gemini = new GeminiService(
            feature: 'exam_prep',
            modelName: (string) (FrontendConfig::getValue('ai.gemini_model', '') ?: 'gemini-2.0-flash'),
            userId: null
        );

        $response = $gemini->generateContent($prompt, [
            'temperature' => 0.8,
            'maxOutputTokens' => max(4096, $count * 500),
            'jsonMode' => true,
            'timeout' => 45,
            'connect_timeout' => 8,
        ]);

        return (string) ($response['content'] ?? '');
    }

    private function isOpenAiEnabled(): bool
    {
        return FrontendConfig::getValue('ai.openai_enabled', '0') === '1'
            && $this->resolveOpenAiApiKey() !== '';
    }

    private function isGeminiEnabled(): bool
    {
        return FrontendConfig::getValue('ai.gemini_enabled', '0') === '1'
            && (string) FrontendConfig::getValue('ai.gemini_api_key', '') !== '';
    }

    private function resolveOpenAiApiKey(): string
    {
        $apiKey = (string) FrontendConfig::getValue('ai.openai_api_key', '');
        if ($apiKey !== '') {
            return $apiKey;
        }

        return (string) config('ai.openai.api_key', env('OPENAI_API_KEY', ''));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseResponse(string $content): array
    {
        $data = json_decode($content, true);
        if (is_array($data) && isset($data['questions']) && is_array($data['questions'])) {
            return $data['questions'];
        }

        if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/', $content, $m)) {
            $data = json_decode($m[1], true);
            if (is_array($data) && isset($data['questions'])) {
                return $data['questions'];
            }
        }

        if (preg_match('/\{[\s\S]*"questions"[\s\S]*\}/', $content, $m)) {
            $data = json_decode($m[0], true);
            if (is_array($data) && isset($data['questions'])) {
                return $data['questions'];
            }
        }

        Log::warning('ExamQuestionGenerator: failed to parse AI response', [
            'content_length' => strlen($content),
            'first_200' => substr($content, 0, 200),
        ]);

        return [];
    }

    private function saveQuestions(Exam $exam, array $questions, string $subject, int $year, string $language = 'english'): int
    {
        $saved = 0;
        $language = $this->normalizeLanguage($language);

        foreach ($questions as $q) {
            if (empty($q['question']) || empty($q['correct_answer'])) {
                continue;
            }

            $options = [];
            if (isset($q['options']) && is_array($q['options'])) {
                foreach ($q['options'] as $label => $text) {
                    $options[] = ['label' => (string) $label, 'text' => (string) $text];
                }
            }

            if (count($options) < 2) {
                continue;
            }

            $difficulty = $q['difficulty'] ?? 'medium';
            if (! in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
                $difficulty = 'medium';
            }

            ExamQuestion::create([
                'exam_id' => $exam->id,
                'subject' => $subject,
                'topic' => $q['topic'] ?? null,
                'year' => $year,
                'type' => 'mcq',
                'question_text' => $q['question'],
                'options' => $options,
                'correct_answer' => strtoupper(trim((string) $q['correct_answer'])),
                'explanation' => $q['explanation'] ?? null,
                'difficulty' => $difficulty,
                'language' => $language,
                'tags' => ['pyq', (string) $year, 'ai-generated', $language],
                'is_active' => true,
            ]);

            $saved++;
        }

        Log::info("ExamQuestionGenerator: saved {$saved} questions", [
            'exam' => $exam->name,
            'subject' => $subject,
            'year' => $year,
        ]);

        return $saved;
    }

    private function normalizeLanguage(?string $language): string
    {
        $lang = strtolower(trim((string) ($language ?? 'english')));

        if (in_array($lang, ['hindi', 'hi'], true) || str_contains($lang, 'hindi')) {
            return 'hindi';
        }

        if (in_array($lang, ['hinglish'], true) || str_contains($lang, 'hinglish')) {
            return 'hinglish';
        }

        return 'english';
    }
}
