<?php

namespace App\Services;

use App\Models\AIResponseFeedback;
use App\Models\AILearnedPreferences;
use App\Models\TopicFeedbackAnalytics;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FeedbackAnalysisService
{
    // Topic keywords for extraction
    private const TOPIC_KEYWORDS = [
        // Physics
        'mechanics' => 'Physics',
        'thermodynamics' => 'Physics',
        'optics' => 'Physics',
        'electrostatics' => 'Physics',
        'magnetism' => 'Physics',
        'waves' => 'Physics',
        'kinematics' => 'Physics',
        'dynamics' => 'Physics',
        'gravitation' => 'Physics',
        'newton' => 'Physics',
        'momentum' => 'Physics',
        'energy' => 'Physics',
        'electricity' => 'Physics',

        // Chemistry
        'organic chemistry' => 'Chemistry',
        'inorganic chemistry' => 'Chemistry',
        'physical chemistry' => 'Chemistry',
        'chemical bonding' => 'Chemistry',
        'periodic table' => 'Chemistry',
        'electrochemistry' => 'Chemistry',
        'thermochemistry' => 'Chemistry',
        'equilibrium' => 'Chemistry',
        'redox' => 'Chemistry',
        'mole concept' => 'Chemistry',
        'stoichiometry' => 'Chemistry',

        // Mathematics
        'calculus' => 'Mathematics',
        'integration' => 'Mathematics',
        'differentiation' => 'Mathematics',
        'algebra' => 'Mathematics',
        'trigonometry' => 'Mathematics',
        'geometry' => 'Mathematics',
        'probability' => 'Mathematics',
        'statistics' => 'Mathematics',
        'matrices' => 'Mathematics',
        'vectors' => 'Mathematics',
        'limits' => 'Mathematics',
        'continuity' => 'Mathematics',

        // Biology
        'photosynthesis' => 'Biology',
        'respiration' => 'Biology',
        'genetics' => 'Biology',
        'cell biology' => 'Biology',
        'ecology' => 'Biology',
        'evolution' => 'Biology',
        'human physiology' => 'Biology',
        'plant biology' => 'Biology',
        'molecular biology' => 'Biology',
        'biotechnology' => 'Biology',
    ];

    // Feedback reason patterns
    private const FEEDBACK_PATTERNS = [
        'too_complex' => ['too complex', 'difficult to understand', 'confusing', 'complicated', 'hard to follow', 'samajh nahi aaya', 'bahut mushkil'],
        'too_simple' => ['too simple', 'too basic', 'need more depth', 'more detail', 'advanced', 'deeper explanation'],
        'wrong_answer' => ['wrong', 'incorrect', 'mistake', 'error', 'galat', 'not correct'],
        'missing_steps' => ['missing steps', 'skip', 'more steps', 'show working', 'step by step', 'how did you get'],
        'needs_examples' => ['example', 'real world', 'practical', 'application', 'udaharan', 'samjhao'],
        'language_issue' => ['hindi', 'hinglish', 'english', 'language', 'bhasha'],
    ];

    /**
     * Process and store feedback
     */
    public static function processFeedback(array $data): array
    {
        $userId = $data['user_id'];

        // Extract topic from question
        $topicInfo = self::extractTopic($data['question']);
        $data['topic'] = $topicInfo['topic'];
        $data['subject'] = $topicInfo['subject'];

        // Store feedback
        $feedback = AIResponseFeedback::create($data);

        // Analyze and update preferences
        self::analyzeAndUpdatePreferences($userId, $data);

        // Update topic analytics
        if ($data['topic']) {
            self::updateTopicAnalytics($userId, $data);
        }

        // Generate follow-up suggestion if negative
        $followUp = null;
        if ($data['feedback_type'] === 'dislike') {
            $followUp = self::generateFollowUpSuggestion($data['feedback_reason'] ?? '');
        }

        // Clear cache for this user
        Cache::forget("feedback_prompt_{$userId}");

        return [
            'feedback_id' => $feedback->id,
            'preferences_updated' => true,
            'follow_up' => $followUp,
        ];
    }

    /**
     * Extract topic from question text
     */
    public static function extractTopic(string $question): array
    {
        $questionLower = strtolower($question);

        foreach (self::TOPIC_KEYWORDS as $keyword => $subject) {
            if (str_contains($questionLower, $keyword)) {
                return [
                    'topic' => ucwords($keyword),
                    'subject' => $subject,
                ];
            }
        }

        return ['topic' => null, 'subject' => null];
    }

    /**
     * Analyze feedback and update user preferences
     */
    public static function analyzeAndUpdatePreferences(int $userId, array $feedbackData): void
    {
        $preferences = AILearnedPreferences::getOrCreateForUser($userId);

        // Update counters
        $preferences->total_feedbacks++;

        if ($feedbackData['feedback_type'] === 'like') {
            $preferences->positive_feedbacks++;

            // Track what worked well
            if ($feedbackData['topic']) {
                $preferences->addFavoriteTopic($feedbackData['topic']);
            }
        } else {
            $preferences->negative_feedbacks++;

            // Analyze feedback reason
            $reason = strtolower($feedbackData['feedback_reason'] ?? '');
            $comment = strtolower($feedbackData['user_comment'] ?? '');
            $fullText = $reason . ' ' . $comment;

            // Update specific issue counts and preferences
            foreach (self::FEEDBACK_PATTERNS as $pattern => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($fullText, $keyword)) {
                        self::updatePreferenceForPattern($preferences, $pattern, $feedbackData['topic']);
                        break;
                    }
                }
            }
        }

        // Update feedback score
        $preferences->updateFeedbackScore();
        $preferences->save();
    }

    /**
     * Update preference based on feedback pattern
     */
    private static function updatePreferenceForPattern(AILearnedPreferences $preferences, string $pattern, ?string $topic): void
    {
        switch ($pattern) {
            case 'too_complex':
                $preferences->too_complex_count++;
                $preferences->prefers_simple_language = true;
                $preferences->prefers_detailed_explanations = false;
                if ($topic) {
                    $preferences->addStrugglingTopic($topic);
                }
                break;

            case 'too_simple':
                $preferences->too_simple_count++;
                $preferences->prefers_simple_language = false;
                $preferences->prefers_detailed_explanations = true;
                break;

            case 'wrong_answer':
                $preferences->wrong_answer_count++;
                if ($topic) {
                    $preferences->addStrugglingTopic($topic);
                }
                break;

            case 'missing_steps':
                $preferences->missing_steps_count++;
                $preferences->prefers_step_by_step = true;
                break;

            case 'needs_examples':
                $preferences->needs_examples_count++;
                $preferences->prefers_examples = true;
                $preferences->likes_real_world_examples = true;
                break;

            case 'language_issue':
                // Check if they want Hindi/Hinglish
                $preferences->prefers_hinglish = true;
                break;
        }
    }

    /**
     * Update topic-specific analytics
     */
    public static function updateTopicAnalytics(int $userId, array $feedbackData): void
    {
        $analytics = TopicFeedbackAnalytics::firstOrCreate(
            [
                'user_id' => $userId,
                'topic' => $feedbackData['topic'],
            ],
            [
                'subject' => $feedbackData['subject'],
                'total_questions' => 0,
                'liked_responses' => 0,
                'disliked_responses' => 0,
            ]
        );

        $analytics->total_questions++;
        $analytics->last_feedback_at = now();

        if ($feedbackData['feedback_type'] === 'like') {
            $analytics->liked_responses++;
        } else {
            $analytics->disliked_responses++;

            // Track specific issues
            $reason = strtolower($feedbackData['feedback_reason'] ?? '');
            if (str_contains($reason, 'complex')) {
                $analytics->too_complex_count++;
            }
            if (str_contains($reason, 'simple')) {
                $analytics->too_simple_count++;
            }
            if (str_contains($reason, 'wrong')) {
                $analytics->wrong_answer_count++;
            }
            if (str_contains($reason, 'step')) {
                $analytics->missing_steps_count++;
            }
        }

        $analytics->updateSatisfactionRate();
    }

    /**
     * Generate feedback-aware prompt for AI
     */
    public static function generateFeedbackAwarePrompt(int $userId): string
    {
        // Cache for 10 minutes to reduce DB queries
        $cacheKey = "feedback_prompt_{$userId}";

        return Cache::remember($cacheKey, 600, function () use ($userId) {
            $preferences = AILearnedPreferences::getOrCreateForUser($userId);
            $topicAnalytics = TopicFeedbackAnalytics::where('user_id', $userId)
                ->where('total_questions', '>=', 2)
                ->get();

            $promptParts = [];

            // Language & Complexity based on feedback
            if ($preferences->prefers_simple_language && $preferences->too_complex_count >= 2) {
                $promptParts[] = self::getSimpleLanguagePrompt($preferences);
            }

            if ($preferences->prefers_detailed_explanations && $preferences->too_simple_count >= 2) {
                $promptParts[] = self::getDetailedExplanationPrompt($preferences);
            }

            // Examples preference
            if ($preferences->prefers_examples && $preferences->needs_examples_count >= 2) {
                $promptParts[] = self::getExamplesPrompt();
            }

            // Step-by-step preference
            if ($preferences->prefers_step_by_step && $preferences->missing_steps_count >= 2) {
                $promptParts[] = self::getStepByStepPrompt();
            }

            // Communication style
            if ($preferences->prefers_hinglish) {
                $promptParts[] = self::getHinglishPrompt();
            }

            // Topic-specific adjustments
            $strugglingTopics = $topicAnalytics
                ->filter(fn($t) => $t->satisfaction_rate < 50)
                ->pluck('topic')
                ->take(5)
                ->toArray();

            if (!empty($strugglingTopics)) {
                $promptParts[] = self::getStrugglingTopicsPrompt($strugglingTopics);
            }

            // Overall satisfaction adjustment
            if ($preferences->needsExtraCare()) {
                $promptParts[] = self::getLowSatisfactionPrompt($preferences);
            } elseif ($preferences->feedback_score > 0.8 && $preferences->total_feedbacks >= 10) {
                $promptParts[] = self::getHighSatisfactionPrompt($preferences);
            }

            return implode("\n\n", $promptParts);
        });
    }

    /**
     * Get prompt for simple language preference
     */
    private static function getSimpleLanguagePrompt(AILearnedPreferences $preferences): string
    {
        return "CRITICAL - USER PREFERS SIMPLE LANGUAGE:
- User has given negative feedback {$preferences->too_complex_count} times for \"too complex\"
- Use extremely simple words and short sentences
- Avoid technical jargon unless absolutely necessary
- Explain concepts like teaching to a beginner
- Break down everything into very small, digestible steps
- Example: Instead of \"oxidation-reduction reaction\", say \"chemical reaction where electrons move from one substance to another\"
- If using technical terms, always explain them immediately in simple words";
    }

    /**
     * Get prompt for detailed explanation preference
     */
    private static function getDetailedExplanationPrompt(AILearnedPreferences $preferences): string
    {
        return "USER PREFERS DETAILED EXPLANATIONS:
- User has requested more depth {$preferences->too_simple_count} times
- Provide comprehensive, thorough explanations
- Include mathematical derivations when relevant
- Add theoretical background and context
- Don't oversimplify - this student wants depth
- Include edge cases and exceptions
- Connect concepts to advanced applications";
    }

    /**
     * Get prompt for examples preference
     */
    private static function getExamplesPrompt(): string
    {
        return "USER LOVES EXAMPLES:
- Always include 2-3 real-world examples
- Use relatable analogies (Indian context preferred)
- Provide fully solved numerical examples
- Show practical applications of every concept
- Use everyday scenarios student can relate to
- Include \"For example...\" in every major explanation";
    }

    /**
     * Get prompt for step-by-step preference
     */
    private static function getStepByStepPrompt(): string
    {
        return "USER NEEDS STEP-BY-STEP SOLUTIONS:
- Break EVERY solution into clearly numbered steps
- Explain reasoning for each step (\"We do this because...\")
- Show all intermediate calculations
- NEVER skip any step, even if it seems obvious
- Format: Step 1: [action] -> [result]
- At the end, summarize the key steps";
    }

    /**
     * Get prompt for Hinglish preference
     */
    private static function getHinglishPrompt(): string
    {
        return "USER PREFERS HINGLISH:
- Mix Hindi and English naturally in responses
- Use Hindi for conversational parts and explanations
- Keep technical terms and formulas in English
- Be conversational and friendly, like a helpful senior
- Example: \"Dekho, yeh concept simple hai. Basically...\"
- Use phrases like: \"samjho\", \"matlab\", \"yaani\", \"bas\"";
    }

    /**
     * Get prompt for struggling topics
     */
    private static function getStrugglingTopicsPrompt(array $topics): string
    {
        $topicList = implode(', ', $topics);
        return "USER STRUGGLES WITH THESE TOPICS: {$topicList}

When these topics come up:
- Use EXTRA simple explanations
- Provide MORE examples than usual
- Break into SMALLER steps
- Use more encouraging tone
- Offer: \"Kya aur detail mein samjhaun?\" (Want me to explain more?)
- Be patient and supportive";
    }

    /**
     * Get prompt for low satisfaction users
     */
    private static function getLowSatisfactionPrompt(AILearnedPreferences $preferences): string
    {
        $score = round($preferences->feedback_score * 100);
        return "ALERT: USER SATISFACTION IS LOW ({$score}%)
- Be extra careful with explanations
- Ask for clarification more often
- Offer alternative explanations proactively
- Be more patient and encouraging
- After answering, ask: \"Did this help? Want me to explain differently?\"
- Focus on building confidence
- Celebrate small wins";
    }

    /**
     * Get prompt for high satisfaction users
     */
    private static function getHighSatisfactionPrompt(AILearnedPreferences $preferences): string
    {
        $score = round($preferences->feedback_score * 100);
        return "USER IS HIGHLY SATISFIED ({$score}%)
- Current teaching style is working great!
- Maintain the same approach
- Can introduce slightly advanced concepts
- Feel free to challenge them with deeper questions
- Occasional quick tips for competitive exams";
    }

    /**
     * Generate follow-up suggestion after negative feedback
     */
    public static function generateFollowUpSuggestion(?string $reason): array
    {
        $reasonLower = strtolower($reason ?? '');

        $suggestions = [
            'too complex' => [
                'message' => "I see this was too complex. Let me explain it more simply.",
                'options' => [
                    'Use simpler words',
                    'Break it into smaller steps',
                    'Give a real-life example',
                    'Try a different approach',
                ],
            ],
            'too simple' => [
                'message' => "I understand you need more depth. Let me provide detailed explanation.",
                'options' => [
                    'Mathematical derivation',
                    'Theoretical background',
                    'Advanced concepts',
                    'Multiple solution methods',
                ],
            ],
            'wrong' => [
                'message' => "I apologize for the error. Let me recalculate this carefully.",
                'options' => [
                    'Re-solve from scratch',
                    'Verify each step',
                    'Cross-check with another method',
                    'Point out where the mistake is',
                ],
            ],
            'step' => [
                'message' => "You're right, I should show all steps. Let me redo this with complete working.",
                'options' => [
                    'Every calculation step',
                    'Reasoning for each step',
                    'Intermediate results',
                    'Final verification',
                ],
            ],
            'example' => [
                'message' => "Let me add more examples to make this clearer.",
                'options' => [
                    'Real-world application',
                    'Numerical example',
                    'Visual description',
                    'Analogy from daily life',
                ],
            ],
        ];

        foreach ($suggestions as $key => $suggestion) {
            if (str_contains($reasonLower, $key)) {
                return $suggestion;
            }
        }

        // Default follow-up
        return [
            'message' => "Thank you for the feedback! Let me try explaining this differently.",
            'options' => [
                'Simpler explanation',
                'More examples',
                'Step-by-step solution',
                'Ask me what part confused you',
            ],
        ];
    }

    /**
     * Get user feedback statistics
     */
    public static function getUserFeedbackStats(int $userId): array
    {
        $preferences = AILearnedPreferences::getOrCreateForUser($userId);
        $recentFeedback = AIResponseFeedback::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $topicStats = TopicFeedbackAnalytics::where('user_id', $userId)
            ->orderByDesc('total_questions')
            ->limit(10)
            ->get();

        return [
            'total_feedbacks' => $preferences->total_feedbacks,
            'positive_feedbacks' => $preferences->positive_feedbacks,
            'negative_feedbacks' => $preferences->negative_feedbacks,
            'satisfaction_score' => round($preferences->feedback_score * 100),
            'preferences' => [
                'simple_language' => $preferences->prefers_simple_language,
                'detailed_explanations' => $preferences->prefers_detailed_explanations,
                'examples' => $preferences->prefers_examples,
                'step_by_step' => $preferences->prefers_step_by_step,
                'hinglish' => $preferences->prefers_hinglish,
            ],
            'issue_counts' => [
                'too_complex' => $preferences->too_complex_count,
                'too_simple' => $preferences->too_simple_count,
                'wrong_answer' => $preferences->wrong_answer_count,
                'missing_steps' => $preferences->missing_steps_count,
            ],
            'struggling_topics' => $preferences->struggles_with_topics ?? [],
            'favorite_topics' => $preferences->favorite_topics ?? [],
            'topic_satisfaction' => $topicStats->map(fn($t) => [
                'topic' => $t->topic,
                'questions' => $t->total_questions,
                'satisfaction' => round($t->satisfaction_rate),
            ])->toArray(),
            'recent_feedback' => $recentFeedback->map(fn($f) => [
                'type' => $f->feedback_type,
                'reason' => $f->feedback_reason,
                'topic' => $f->topic,
                'date' => $f->created_at->diffForHumans(),
            ])->toArray(),
        ];
    }

    /**
     * Get global feedback analytics (for admin)
     */
    public static function getGlobalAnalytics(): array
    {
        $totalFeedback = AIResponseFeedback::count();
        $positiveFeedback = AIResponseFeedback::where('feedback_type', 'like')->count();
        $recentSatisfaction = AIResponseFeedback::getSatisfactionRate(7);

        $commonIssues = AIResponseFeedback::getCommonReasons(30, 10);

        $topicPerformance = TopicFeedbackAnalytics::getGlobalTopicStats();

        $preferencesDistribution = [
            'prefers_simple' => AILearnedPreferences::where('prefers_simple_language', true)->count(),
            'prefers_detailed' => AILearnedPreferences::where('prefers_detailed_explanations', true)->count(),
            'prefers_examples' => AILearnedPreferences::where('prefers_examples', true)->count(),
            'prefers_hinglish' => AILearnedPreferences::where('prefers_hinglish', true)->count(),
        ];

        return [
            'total_feedbacks' => $totalFeedback,
            'positive_rate' => $totalFeedback > 0 ? round(($positiveFeedback / $totalFeedback) * 100) : 0,
            'negative_rate' => $totalFeedback > 0 ? round((($totalFeedback - $positiveFeedback) / $totalFeedback) * 100) : 0,
            'recent_satisfaction' => round($recentSatisfaction * 100),
            'common_issues' => $commonIssues,
            'topic_performance' => $topicPerformance,
            'preferences_distribution' => $preferencesDistribution,
        ];
    }
}
