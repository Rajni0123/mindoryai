<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Support\Facades\Log;

/**
 * Generates PYQ-style exam questions using Gemini AI.
 *
 * Flow: Select Exam → AI generates previous-year-pattern questions → Save to DB → Use in mock tests
 */
class ExamQuestionGenerator
{
    /**
     * Generate questions for an exam using AI.
     *
     * @param Exam $exam
     * @param string $subject  Specific subject or 'all'
     * @param int $year        Target year (e.g., 2024, 2023)
     * @param int $count       Number of questions to generate
     * @param string $difficulty  easy|medium|hard|mixed
     * @return int Number of questions saved
     */
    public function generate(Exam $exam, string $subject = 'all', int $year = 2024, int $count = 10, string $difficulty = 'mixed'): int
    {
        $subjects = $subject === 'all'
            ? ($exam->subjects ?? ['General'])
            : [$subject];

        $perSubject = max(1, intdiv($count, count($subjects)));
        $totalSaved = 0;

        foreach ($subjects as $subj) {
            try {
                $questions = $this->generateForSubject($exam, $subj, $year, $perSubject, $difficulty);
                $saved = $this->saveQuestions($exam, $questions, $subj, $year);
                $totalSaved += $saved;
            } catch (\Exception $e) {
                Log::error("ExamQuestionGenerator failed for {$exam->name}/{$subj}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $totalSaved;
    }

    /**
     * Generate questions for a specific subject using Gemini.
     */
    private function generateForSubject(Exam $exam, string $subject, int $year, int $count, string $difficulty): array
    {
        $gemini = new GeminiService(
            feature: 'exam_prep',
            modelName: 'gemini-2.0-flash',
            userId: null
        );

        $markingScheme = $exam->config['marking_scheme'] ?? ['correct' => 4, 'wrong' => -1];
        $difficultyInstruction = $difficulty === 'mixed'
            ? "Mix of easy (20%), medium (60%), and hard (20%) questions."
            : "All questions should be {$difficulty} difficulty.";

        $prompt = <<<PROMPT
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

        $response = $gemini->generateContent($prompt, [
            'temperature' => 0.8,
            'maxOutputTokens' => max(4096, $count * 500),
            'jsonMode' => true,
        ]);

        $content = $response['content'] ?? '';
        return $this->parseResponse($content);
    }

    /**
     * Parse Gemini JSON response into question array.
     */
    private function parseResponse(string $content): array
    {
        // Try direct JSON decode
        $data = json_decode($content, true);
        if ($data && isset($data['questions'])) {
            return $data['questions'];
        }

        // Try extracting JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/', $content, $m)) {
            $data = json_decode($m[1], true);
            if ($data && isset($data['questions'])) {
                return $data['questions'];
            }
        }

        // Try finding JSON object with balanced braces
        if (preg_match('/\{[\s\S]*"questions"[\s\S]*\}/', $content, $m)) {
            $data = json_decode($m[0], true);
            if ($data && isset($data['questions'])) {
                return $data['questions'];
            }
        }

        Log::warning('ExamQuestionGenerator: failed to parse AI response', [
            'content_length' => strlen($content),
            'first_200' => substr($content, 0, 200),
        ]);

        return [];
    }

    /**
     * Save parsed questions to the database.
     */
    private function saveQuestions(Exam $exam, array $questions, string $subject, int $year): int
    {
        $saved = 0;

        foreach ($questions as $q) {
            if (empty($q['question']) || empty($q['correct_answer'])) {
                continue;
            }

            // Convert options from {A: "...", B: "..."} to [{label: "A", text: "..."}, ...]
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
            if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
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
                'correct_answer' => strtoupper(trim($q['correct_answer'])),
                'explanation' => $q['explanation'] ?? null,
                'difficulty' => $difficulty,
                'language' => 'english',
                'tags' => ['pyq', (string) $year, 'ai-generated'],
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
}
