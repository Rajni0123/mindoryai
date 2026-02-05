<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyChallenge;
use App\Models\DynamicAppConfig;
use App\Services\PythonAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DailyChallengeController extends Controller
{
    public function index()
    {
        $challenges = DailyChallenge::orderBy('challenge_date', 'desc')->paginate(20);

        $settings = [
            'enabled' => DynamicAppConfig::getValue('features.daily_challenge_enabled', true),
            'questions_count' => DynamicAppConfig::getValue('daily_challenge.questions_count', 5),
            'time_limit' => DynamicAppConfig::getValue('daily_challenge.time_limit_seconds', 300),
            'reward_credits' => DynamicAppConfig::getValue('daily_challenge.reward_credits', 2),
            'streak_bonus' => DynamicAppConfig::getValue('daily_challenge.streak_bonus_credits', 5),
            'difficulty' => DynamicAppConfig::getValue('daily_challenge.difficulty', 'mixed'),
            'subjects' => DynamicAppConfig::getValue('daily_challenge.subjects', ['Mathematics', 'Science', 'English', 'Social Studies', 'General Knowledge']),
        ];

        $todayChallenge = DailyChallenge::getToday();

        return view('admin.daily-challenges.index', compact('challenges', 'settings', 'todayChallenge'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'questions_count' => 'required|integer|min:3|max:20',
            'time_limit' => 'required|integer|min:60|max:1800',
            'reward_credits' => 'required|integer|min:0|max:50',
            'streak_bonus' => 'required|integer|min:0|max:100',
            'difficulty' => 'required|in:easy,medium,hard,mixed',
            'subjects' => 'required|string',
        ]);

        try {
            DynamicAppConfig::setValue('features.daily_challenge_enabled', $request->boolean('enabled'), 'boolean');
            DynamicAppConfig::setValue('daily_challenge.questions_count', (int) $request->questions_count, 'integer');
            DynamicAppConfig::setValue('daily_challenge.time_limit_seconds', (int) $request->time_limit, 'integer');
            DynamicAppConfig::setValue('daily_challenge.reward_credits', (int) $request->reward_credits, 'integer');
            DynamicAppConfig::setValue('daily_challenge.streak_bonus_credits', (int) $request->streak_bonus, 'integer');
            DynamicAppConfig::setValue('daily_challenge.difficulty', $request->difficulty);

            $subjects = array_map('trim', explode(',', $request->subjects));
            DynamicAppConfig::setValue('daily_challenge.subjects', $subjects, 'json');

            return redirect()->back()->with('success', 'Daily challenge settings updated!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $subjects = DynamicAppConfig::getValue('daily_challenge.subjects', ['Mathematics', 'Science', 'English', 'Social Studies', 'General Knowledge']);
        return view('admin.daily-challenges.create', compact('subjects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'challenge_date' => 'required|date|unique:daily_challenges,challenge_date',
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:100',
            'difficulty' => 'required|in:easy,medium,hard,mixed',
            'time_limit_seconds' => 'required|integer|min:60|max:1800',
            'reward_credits' => 'required|integer|min:0|max:50',
            'questions' => 'required|array|min:3',
            'questions.*.question' => 'required|string',
            'questions.*.options' => 'required|array|min:2|max:4',
            'questions.*.correct_answer' => 'required|integer|min:0|max:3',
            'questions.*.explanation' => 'nullable|string',
        ]);

        try {
            DailyChallenge::create([
                'challenge_date' => $request->challenge_date,
                'title' => $request->title,
                'subject' => $request->subject,
                'difficulty' => $request->difficulty,
                'time_limit_seconds' => $request->time_limit_seconds,
                'reward_credits' => $request->reward_credits,
                'questions' => $request->questions,
                'is_active' => true,
            ]);

            return redirect()->route('admin.daily-challenges.index')->with('success', 'Daily challenge created!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create challenge: ' . $e->getMessage());
        }
    }

    public function edit(DailyChallenge $dailyChallenge)
    {
        $subjects = DynamicAppConfig::getValue('daily_challenge.subjects', ['Mathematics', 'Science', 'English', 'Social Studies', 'General Knowledge']);
        return view('admin.daily-challenges.edit', compact('dailyChallenge', 'subjects'));
    }

    public function update(Request $request, DailyChallenge $dailyChallenge)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:100',
            'difficulty' => 'required|in:easy,medium,hard,mixed',
            'time_limit_seconds' => 'required|integer|min:60|max:1800',
            'reward_credits' => 'required|integer|min:0|max:50',
            'questions' => 'required|array|min:3',
            'questions.*.question' => 'required|string',
            'questions.*.options' => 'required|array|min:2|max:4',
            'questions.*.correct_answer' => 'required|integer|min:0|max:3',
            'questions.*.explanation' => 'nullable|string',
        ]);

        try {
            $dailyChallenge->update([
                'title' => $request->title,
                'subject' => $request->subject,
                'difficulty' => $request->difficulty,
                'time_limit_seconds' => $request->time_limit_seconds,
                'reward_credits' => $request->reward_credits,
                'questions' => $request->questions,
            ]);

            return redirect()->route('admin.daily-challenges.index')->with('success', 'Challenge updated!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update challenge: ' . $e->getMessage());
        }
    }

    public function destroy(DailyChallenge $dailyChallenge)
    {
        $dailyChallenge->delete();
        return redirect()->route('admin.daily-challenges.index')->with('success', 'Challenge deleted!');
    }

    public function toggleActive(DailyChallenge $dailyChallenge)
    {
        $dailyChallenge->update(['is_active' => !$dailyChallenge->is_active]);
        return redirect()->back()->with('success', 'Challenge status updated!');
    }

    /**
     * AI-generate questions for daily challenge
     */
    public function generateWithAI(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:100',
            'difficulty' => 'required|in:easy,medium,hard,mixed',
            'count' => 'required|integer|min:3|max:20',
            'topic' => 'nullable|string|max:255',
            'class_level' => 'nullable|string|max:50',
        ]);

        $subject = $request->subject;
        $difficulty = $request->difficulty;
        $count = $request->count;
        $topic = $request->topic ?? '';
        $classLevel = $request->class_level ?? 'Class 10';

        $difficultyGuide = match($difficulty) {
            'easy' => 'simple and straightforward, suitable for beginners',
            'medium' => 'moderate difficulty, requiring some understanding',
            'hard' => 'challenging, requiring deep understanding and critical thinking',
            'mixed' => 'a mix of easy (30%), medium (40%), and hard (30%) questions',
        };

        $topicLine = $topic ? "Focus specifically on the topic: {$topic}." : "Cover a variety of topics within the subject.";

        $prompt = <<<PROMPT
Generate exactly {$count} multiple-choice quiz questions for Indian students ({$classLevel} level).

Subject: {$subject}
Difficulty: {$difficulty} — {$difficultyGuide}
{$topicLine}

STRICT RULES:
1. Each question MUST have exactly 4 options (A, B, C, D)
2. Each question MUST have exactly 1 correct answer
3. Questions should be educational, factually accurate, and exam-relevant
4. Include a brief 1-line explanation for each answer
5. Questions should be relevant to CBSE/ICSE curriculum
6. Do NOT repeat questions
7. Make options plausible (avoid obviously wrong answers)

You MUST respond in ONLY valid JSON format with NO extra text before or after. The format:
[
  {
    "question": "What is the chemical formula of water?",
    "options": ["H2O", "CO2", "NaCl", "O2"],
    "correct_answer": 0,
    "explanation": "Water is composed of 2 hydrogen atoms and 1 oxygen atom, giving formula H2O."
  }
]

correct_answer is the 0-based index of the correct option (0=first, 1=second, etc).
Generate exactly {$count} questions. Return ONLY the JSON array, nothing else.
PROMPT;

        try {
            $aiService = new PythonAIService();
            $result = $aiService->ask(
                text: $prompt,
                systemPrompt: 'You are an expert quiz question generator for Indian school students. Always respond with ONLY valid JSON arrays. No markdown, no code blocks, no explanations outside JSON.',
                temperature: 0.8,
                maxTokens: 4096
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'AI service error: ' . ($result['error'] ?? 'Unknown error'),
                ], 500);
            }

            $answer = $result['answer'] ?? '';

            // Clean the response - extract JSON from possible markdown wrapping
            $answer = trim($answer);
            if (str_starts_with($answer, '```json')) {
                $answer = substr($answer, 7);
            } elseif (str_starts_with($answer, '```')) {
                $answer = substr($answer, 3);
            }
            if (str_ends_with($answer, '```')) {
                $answer = substr($answer, 0, -3);
            }
            $answer = trim($answer);

            $questions = json_decode($answer, true);

            if (!is_array($questions) || empty($questions)) {
                Log::error('[DailyChallenge AI] Failed to parse AI response', ['raw' => $result['answer']]);
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to parse AI response. Please try again.',
                ], 422);
            }

            // Validate and clean each question
            $cleaned = [];
            foreach ($questions as $q) {
                if (empty($q['question']) || empty($q['options']) || !is_array($q['options'])) {
                    continue;
                }

                // Ensure exactly 4 options
                $options = array_values(array_slice($q['options'], 0, 4));
                while (count($options) < 4) {
                    $options[] = '';
                }

                $correctAnswer = (int) ($q['correct_answer'] ?? 0);
                if ($correctAnswer < 0 || $correctAnswer > 3) {
                    $correctAnswer = 0;
                }

                $cleaned[] = [
                    'question' => $q['question'],
                    'options' => $options,
                    'correct_answer' => $correctAnswer,
                    'explanation' => $q['explanation'] ?? '',
                ];
            }

            if (empty($cleaned)) {
                return response()->json([
                    'success' => false,
                    'error' => 'AI generated invalid questions. Please try again.',
                ], 422);
            }

            // Also generate a title suggestion
            $titleSuggestion = $subject . ' Challenge — ' . now()->format('d M Y');
            if ($topic) {
                $titleSuggestion = $subject . ': ' . $topic . ' — ' . now()->format('d M Y');
            }

            return response()->json([
                'success' => true,
                'questions' => $cleaned,
                'title_suggestion' => $titleSuggestion,
                'count' => count($cleaned),
                'model' => $result['model'] ?? 'unknown',
            ]);

        } catch (\Exception $e) {
            Log::error('[DailyChallenge AI] Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'AI generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
