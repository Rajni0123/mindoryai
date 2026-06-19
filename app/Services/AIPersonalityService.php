<?php

namespace App\Services;

use App\Models\User;
use App\Services\LearningAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIPersonalityService
{
    /**
     * Get time-based greeting and motivation
     */
    public static function getTimeBasedGreeting(): array
    {
        $hour = (int) now()->format('H');

        if ($hour >= 5 && $hour < 12) {
            return [
                'greeting' => 'Good morning',
                'emoji' => '🌅',
                'motivation' => 'Great time to study! Your brain is fresh and ready to absorb concepts.',
                'tag' => 'early_learner',
            ];
        } elseif ($hour >= 12 && $hour < 17) {
            return [
                'greeting' => 'Good afternoon',
                'emoji' => '☀️',
                'motivation' => 'Perfect time for practice problems and revision!',
                'tag' => 'afternoon_grinder',
            ];
        } elseif ($hour >= 17 && $hour < 21) {
            return [
                'greeting' => 'Good evening',
                'emoji' => '🌆',
                'motivation' => 'Evening study session! Let\'s make it productive.',
                'tag' => 'evening_scholar',
            ];
        } elseif ($hour >= 21 && $hour < 24) {
            return [
                'greeting' => 'Still studying',
                'emoji' => '🌙',
                'motivation' => 'Night owl mode activated! Remember to take breaks.',
                'tag' => 'night_owl',
            ];
        } else {
            return [
                'greeting' => 'Burning the midnight oil',
                'emoji' => '🦉',
                'motivation' => 'Late night grind! Make sure you get enough sleep too.',
                'tag' => 'late_night_warrior',
            ];
        }
    }

    /**
     * Get study streak information
     */
    public static function getStudyStreak(?string $lastStudyDate, int $currentStreak): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        if ($lastStudyDate === $today || $lastStudyDate === $yesterday) {
            return [
                'streak' => $currentStreak,
                'message' => "🔥 {$currentStreak} day streak! Keep it going!",
                'active' => true,
            ];
        }

        return [
            'streak' => 0,
            'message' => 'Welcome back! Start a new streak today.',
            'active' => false,
        ];
    }

    /**
     * Get exam countdown motivation
     */
    public static function getExamMotivation(?string $examName, ?int $daysLeft): ?string
    {
        if (!$examName || !$daysLeft || $daysLeft < 0) {
            return null;
        }

        if ($daysLeft <= 7) {
            return "Final week countdown! {$daysLeft} days to {$examName}. Focus on revision and mock tests.";
        } elseif ($daysLeft <= 30) {
            return "{$daysLeft} days to {$examName}. Time to intensify your preparation!";
        } elseif ($daysLeft <= 90) {
            return "{$daysLeft} days to {$examName}. Build strong fundamentals now.";
        }

        return "{$daysLeft} days to {$examName}. You have time to master everything!";
    }

    /**
     * Get personalized tags based on user data
     */
    public static function getPersonalizedTags(array $userData): array
    {
        $tags = [];
        $hour = (int) now()->format('H');

        // Study time based
        if ($hour >= 21 || $hour < 5) {
            $tags[] = 'Night Owl 🦉';
        }
        if ($hour >= 5 && $hour < 8) {
            $tags[] = 'Early Bird 🐦';
        }

        // Performance based
        $questionsAnswered = $userData['questionsAnswered'] ?? 0;
        $streak = $userData['currentStreak'] ?? 0;

        if ($questionsAnswered > 1000) {
            $tags[] = 'Quiz Master 🎯';
        } elseif ($questionsAnswered > 500) {
            $tags[] = 'Problem Solver 💡';
        } elseif ($questionsAnswered > 100) {
            $tags[] = 'Active Learner 📚';
        }

        if ($streak >= 30) {
            $tags[] = 'Dedication Champion 🏆';
        } elseif ($streak >= 7) {
            $tags[] = 'Consistent Learner ⚡';
        }

        // Subject based
        $subject = $userData['favoriteSubject'] ?? null;
        $subjectTags = [
            'Physics' => 'Physics Enthusiast 🔬',
            'Chemistry' => 'Chemistry Buff ⚗️',
            'Mathematics' => 'Math Wizard 🧮',
            'Math' => 'Math Wizard 🧮',
            'Biology' => 'Biology Expert 🧬',
            'History' => 'History Buff 📜',
            'Geography' => 'Geography Explorer 🌍',
        ];
        if ($subject && isset($subjectTags[$subject])) {
            $tags[] = $subjectTags[$subject];
        }

        // Exam based
        $exam = $userData['targetExam'] ?? null;
        $examTags = [
            'JEE' => 'Future Engineer 🎓',
            'JEE Main' => 'Future Engineer 🎓',
            'JEE Advanced' => 'IIT Aspirant 🎓',
            'NEET' => 'Future Doctor 👨‍⚕️',
            'UPSC' => 'Civil Service Aspirant 🏛️',
            'CAT' => 'MBA Aspirant 📊',
            'GATE' => 'Engineering Master 🔧',
        ];
        if ($exam && isset($examTags[$exam])) {
            $tags[] = $examTags[$exam];
        }

        return $tags;
    }

    /**
     * Calculate days to exam
     */
    public static function getDaysToExam(?string $examDate): ?int
    {
        if (!$examDate) {
            return null;
        }

        try {
            $exam = Carbon::parse($examDate);
            $days = now()->startOfDay()->diffInDays($exam->startOfDay(), false);
            return $days >= 0 ? $days : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Build user data array from User model
     */
    public static function buildUserData(User $user): array
    {
        $daysToExam = self::getDaysToExam($user->exam_date);

        return [
            'name' => $user->name ?? 'Student',
            'targetExam' => $user->target_exam,
            'examDate' => $user->exam_date,
            'daysToExam' => $daysToExam,
            'currentStreak' => $user->current_streak ?? 0,
            'lastStudyDate' => $user->last_study_date,
            'class' => $user->student_class,
            'educationLevel' => $user->education_level,
            'favoriteSubject' => $user->favorite_subject,
            'questionsAnswered' => $user->questions_answered ?? 0,
            'gender' => $user->gender,
            'sessionMessageCount' => $user->session_message_count ?? 0,
        ];
    }

    /**
     * Generate dynamic system prompt based on user context
     * @param bool $includeGreeting DEPRECATED - always false now, greetings removed from AI responses
     */
    public static function generateDynamicPrompt(User $user, bool $includeGreeting = false): string
    {
        $userData = self::buildUserData($user);
        $timeContext = self::getTimeBasedGreeting();
        $streakInfo = self::getStudyStreak($userData['lastStudyDate'], $userData['currentStreak']);
        $examInfo = self::getExamMotivation($userData['targetExam'], $userData['daysToExam']);
        $personalTags = self::getPersonalizedTags($userData);

        $firstName = explode(' ', $userData['name'])[0];
        $tagsString = !empty($personalTags) ? implode(', ', $personalTags) : 'New Learner';

        // Build context sections - NEVER include greeting
        $studentContext = self::buildStudentContext($userData, $streakInfo, $examInfo, $tagsString);
        $formattingRules = self::getFormattingRules();
        $teachingApproach = self::buildTeachingApproach($userData, $timeContext, $streakInfo, $examInfo, false);

        return <<<PROMPT
You are an expert AI tutor for BlinkStudy - India's leading exam preparation platform.

{$studentContext}

{$formattingRules}

SUBJECTS YOU TEACH:
• Physics, Chemistry, Mathematics, Biology (JEE/NEET/CBSE 11-12)
• Science, Math, Social Science (CBSE 6-10)
• UPSC subjects (History, Geography, Polity, Economics, etc.)
• Competitive exams (CAT, GATE, Banking, SSC)

{$teachingApproach}

CRITICAL REMINDERS:
• NO greetings, NO streak mentions, NO motivational closings
• Give SHORT answer first, then offer to explain more
• When user says "Yes/Continue/Haan" → ONLY expand your IMMEDIATELY PREVIOUS response
• NEVER go back to older topics when user wants continuation
• Check the LAST message you sent and continue THAT topic only
• LANGUAGE CONSISTENCY: If user asked "in hindi" → entire response + continuation prompt in Hindi (Devanagari)
• Hindi continuation = "विस्तृत व्याख्या चाहिए?" | English = "Want detailed explanation?"
PROMPT;
    }

    /**
     * Build student context section
     */
    private static function buildStudentContext(array $userData, array $streakInfo, ?string $examInfo, string $tagsString): string
    {
        $lines = [
            "STUDENT CONTEXT (Use this to personalize responses):",
            "Student Name: {$userData['name']}",
        ];

        if ($userData['targetExam']) {
            $lines[] = "Target Exam: {$userData['targetExam']}";
        }
        if ($examInfo) {
            $lines[] = "Exam Countdown: {$examInfo}";
        }

        $lines[] = "Current Streak: {$streakInfo['message']}";

        // Education level context
        if ($userData['educationLevel']) {
            $levelMap = [
                'school' => 'School (Grade 1-10) - Explain in simple terms with relatable examples',
                'high_school' => 'High School (Grade 11-12) - Use exam-focused explanations with formulas and shortcuts',
                'university' => 'University (Undergraduate) - Use academic depth with theoretical concepts',
                'graduate' => 'Graduate (Post-graduate) - Use advanced concepts with research perspective',
            ];
            $levelDescription = $levelMap[$userData['educationLevel']] ?? $userData['educationLevel'];
            $lines[] = "Education Level: {$levelDescription}";
        } elseif ($userData['class']) {
            $lines[] = "Study Level: Class {$userData['class']}";
        }
        if ($userData['favoriteSubject']) {
            $lines[] = "Favorite Subject: {$userData['favoriteSubject']}";
        }

        $lines[] = "Questions Solved: {$userData['questionsAnswered']}";
        $lines[] = "Student Tags: {$tagsString}";

        return implode("\n", $lines);
    }

    /**
     * Build time context section
     */
    private static function buildTimeContext(array $timeContext): string
    {
        return <<<SECTION
TIME CONTEXT:
Current Time: {$timeContext['greeting']} {$timeContext['emoji']}
Study Tip: {$timeContext['motivation']}
SECTION;
    }

    /**
     * Build personalization rules - simplified, no greetings/streaks
     */
    private static function buildPersonalizationRules(array $userData, array $streakInfo, array $timeContext): string
    {
        $firstName = explode(' ', $userData['name'])[0];

        return <<<RULES
PERSONALIZATION RULES:
1. Address student by name occasionally: "{$firstName}, [answer here]"
2. Use Hindi-English mix (Hinglish) if student uses it
3. Keep responses focused and educational
RULES;
    }

    /**
     * Get formatting rules (strict no-markdown)
     */
    private static function getFormattingRules(): string
    {
        return <<<RULES
CRITICAL FORMATTING RULES (MUST FOLLOW):
• Use **bold** for headings and key terms
• Use • for bullets, → for arrows, ✓ for correct, ✗ for wrong
• Write topic headings in **CAPITAL LETTERS**
• Use proper spacing and line breaks for readability
• Use simple numbered lists: 1. 2. 3.

ICON RULES - EXTREMELY STRICT:
DO NOT use icons in regular text. NO emojis. NO icons for words like sunlight, water, oxygen etc.

ONLY use icons in ONE place: Next to chemical/mathematical FORMULAS/EQUATIONS.

CORRECT (icon only with formula):
"The equation is: <icon name="formula"/> 6CO₂ + 6H₂O → C₆H₁₂O₆ + 6O₂"
"Using formula: <icon name="formula"/> E = mc²"
"Chemical reaction: <icon name="flask"/> 2H₂ + O₂ → 2H₂O"

WRONG (never do this):
"Plants use <icon name="sun"/> sunlight" ❌
"It produces <icon name="oxygen"/> oxygen" ❌
"The <icon name="water"/> water molecule" ❌

SUMMARY:
- Regular text = NO ICONS
- Formulas/Equations = ONE icon before the formula
- Maximum 1-2 icons per entire response

FORMULA & EQUATION FORMATTING:
• Write chemical equations with arrows: 6CO₂ + 6H₂O → C₆H₁₂O₆ + 6O₂
• Use subscript characters where possible: H₂O, CO₂, O₂, H₂SO₄
• Use superscript for powers: x², m³, 10⁸
• Greek letters: α, β, γ, θ, π, Δ, Σ, λ, μ, ω
RULES;
    }

    /**
     * Build teaching approach section
     * @param bool $includeGreeting Whether to include greeting (only for first message) - DEPRECATED, always false now
     */
    private static function buildTeachingApproach(array $userData, array $timeContext, array $streakInfo, ?string $examInfo, bool $includeGreeting = false): string
    {
        $firstName = explode(' ', $userData['name'])[0];
        $streak = $streakInfo['streak'];
        $targetExam = $userData['targetExam'] ?? 'your exam';
        $daysToExam = $userData['daysToExam'];

        $examTipSection = '';
        if ($userData['targetExam']) {
            $examTipSection = "\n   {$userData['targetExam']} Exam Tip: [Relevant exam-specific tip]";
        }

        return <<<APPROACH
YOUR TEACHING APPROACH:

1. NEVER INCLUDE GREETINGS OR MOTIVATIONAL MESSAGES:
   • DO NOT start with "Good morning", "Good evening", etc.
   • DO NOT mention streaks
   • DO NOT add motivational closings
   • JUST ANSWER THE QUESTION DIRECTLY

2. CONTINUATION REQUESTS (MOST CRITICAL RULE):
   When user says ONLY "Yes", "Continue", "Explain", "Tell me more", "Details", "Haan", "Ha", "Ok", "sure", "go ahead":

   STEP 1: Find YOUR IMMEDIATELY PREVIOUS MESSAGE (the last assistant message)
   STEP 2: Identify the TOPIC from that message (look at the heading/title)
   STEP 3: Provide detailed explanation of ONLY THAT TOPIC

   IGNORE all other topics from earlier in the conversation!

   Example conversation:
   - User asked: "Explain human eye" → You answered about HUMAN EYE
   - User asked: "Gandhi birthday" → You answered about GANDHI (Oct 2, 1869)
   - User says: "Yes"
   → You MUST explain GANDHI in detail (your last topic), NOT human eye!

   The rule is simple: "Yes" = expand your LAST response, nothing else

3. SHORT ANSWER FIRST APPROACH:
   • Start with a BRIEF 2-3 line answer
   • Then ask: "Want me to explain in detail?"
   • Only provide detailed explanation if user says yes/continue

4. RESPONSE STRUCTURE:

   For CONCEPT Questions:
   [TOPIC NAME]

   Quick Answer: [2-3 line summary]

   [CONTINUATION PROMPT - see language rules below]

   For CONTINUATION (when user says Yes/Continue/Haan/Ha):
   [SAME TOPIC NAME AS BEFORE]

   Detailed Explanation: [Full explanation of the SAME topic]
   Key Points:
   • Point 1
   • Point 2{$examTipSection}

5. FORMULA FORMATTING:
   • Use subscripts: H₂O, CO₂, C₆H₁₂O₆
   • Use superscripts: x², E=mc²
   • Use arrows: A → B
   • Greek letters: α, β, π, θ, Δ, λ

6. LANGUAGE RULES (CRITICAL):
   • MATCH the language of your entire response based on user's request
   • If user says "in hindi", "hindi me", "hindi mein", "hindi mai" → respond in PURE HINDI (Devanagari script)
   • If response is in Hindi (Devanagari), use Hindi continuation prompt: "विस्तृत व्याख्या चाहिए?"
   • If response is in English, use: "Want detailed explanation?"
   • If response is in Hinglish, use: "Detail mein samjhau?"
   • The continuation prompt MUST match the language of your answer

   Examples:
   - Hindi response ends with: "विस्तृत व्याख्या चाहिए?"
   - English response ends with: "Want detailed explanation?"
   - Hinglish response ends with: "Detail mein samjhau?"
APPROACH;
    }

    /**
     * Update user's study tracking (call after each chat interaction)
     */
    public static function updateStudyTracking(User $user): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Update streak
        if ($user->last_study_date === $yesterday) {
            $user->current_streak = ($user->current_streak ?? 0) + 1;
        } elseif ($user->last_study_date !== $today) {
            // Reset streak if more than 1 day gap
            $user->current_streak = 1;
        }

        // Update last study date
        $user->last_study_date = $today;

        // Increment questions answered
        $user->questions_answered = ($user->questions_answered ?? 0) + 1;

        // Update session message count
        $user->session_message_count = ($user->session_message_count ?? 0) + 1;

        $user->save();
    }

    /**
     * Check if this is a new session (first message)
     */
    public static function isNewSession(User $user): bool
    {
        $sessionStart = $user->session_started_at;

        if (!$sessionStart) {
            return true;
        }

        // Consider it a new session if more than 30 minutes since last activity
        return Carbon::parse($sessionStart)->diffInMinutes(now()) > 30;
    }

    /**
     * Start a new session for user
     */
    public static function startNewSession(User $user): void
    {
        $user->session_started_at = now();
        $user->session_message_count = 0;
        $user->save();
    }

    /**
     * Get milestone celebration if any
     */
    public static function getMilestoneCelebration(User $user): ?string
    {
        $streak = $user->current_streak ?? 0;
        $questions = $user->questions_answered ?? 0;

        // Check streak milestones
        if ($streak === 7) {
            return "🎉 One week streak! You're building a great study habit!";
        }
        if ($streak === 30) {
            return "🏆 30 days! You're unstoppable! Amazing dedication!";
        }
        if ($streak === 100) {
            return "💯 100 day streak! You're a legend! Incredible commitment!";
        }

        // Check questions milestones
        if ($questions === 100) {
            return "💯 100 questions solved! You're making great progress!";
        }
        if ($questions === 500) {
            return "🎯 500 questions! You're a quiz master in the making!";
        }
        if ($questions === 1000) {
            return "🏆 1000 questions! You're absolutely crushing it!";
        }

        return null;
    }

    /**
     * Generate comprehensive personalized prompt with all analytics features
     * This combines basic personality with advanced learning analytics
     * NOTE: Greetings and achievements are NO LONGER included in AI responses
     */
    public static function generateComprehensivePrompt(User $user, string $message): string
    {
        try {
            // CRITICAL FIX: Skip ALL personality/analytics prompts for greetings
            // The AI was responding with "ELECTROMAGNETIC INDUCTION" to "hello" because
            // the extra prompts were confusing it. For greetings, ONLY use the base
            // system prompt from ai_system_prompts table which has proper greeting handling.
            $messageLower = strtolower(trim($message));
            $greetings = ['hi', 'hii', 'hiii', 'hello', 'hey', 'hola', 'namaste', 'namaskar',
                          'good morning', 'good afternoon', 'good evening', 'gm', 'gn', 'sup', 'yo'];
            $isGreeting = in_array($messageLower, $greetings) || strlen($messageLower) <= 4;

            if ($isGreeting) {
                // For greetings, return empty - let the base system prompt handle it
                // This prevents the AI from getting confused by extra context
                return "";
            }

            // IMPORTANT: Never include greeting - AI should just answer questions directly
            // Get base personality prompt WITHOUT greeting
            $basePrompt = self::generateDynamicPrompt($user, false);

            // Get advanced analytics prompt from LearningAnalyticsService
            $analyticsPrompt = LearningAnalyticsService::generateComprehensivePrompt($user->id, $message);

            // Award XP for asking question (5 XP per question)
            LearningAnalyticsService::awardXP($user->id, 5, 'question_asked');

            // Check for achievements silently (don't announce in chat)
            // Achievements will be shown in the app UI separately
            LearningAnalyticsService::checkAndAwardAchievements($user->id);

            // Update session tracking
            self::startNewSession($user);

            return $basePrompt . "\n\n" . $analyticsPrompt;

        } catch (\Exception $e) {
            Log::warning("Failed to generate comprehensive prompt: " . $e->getMessage());
            // Fallback to basic prompt
            return self::generateDynamicPrompt($user, false);
        }
    }

    /**
     * Track message and update analytics
     * Call this after processing a chat message
     */
    public static function trackMessage(User $user, string $message, bool $questionCorrect = null, string $topic = null): void
    {
        try {
            // Update study tracking (streak, questions count)
            self::updateStudyTracking($user);

            // Track in learning analytics
            LearningAnalyticsService::analyzeMessageForLearningStyle($user->id, $message);
            LearningAnalyticsService::detectAndTrackLanguage($user->id, $message);
            LearningAnalyticsService::recordSessionActivity($user->id, $topic);

            // If we have performance data, track it
            if ($questionCorrect !== null && $topic) {
                LearningAnalyticsService::trackPerformance(
                    $user->id,
                    $topic,
                    $user->favorite_subject ?? 'General',
                    $questionCorrect
                );
            }

        } catch (\Exception $e) {
            Log::warning("Failed to track message analytics: " . $e->getMessage());
        }
    }

    /**
     * Get gamification status for a user
     */
    public static function getGamificationStatus(User $user): array
    {
        return [
            'level' => $user->current_level ?? 1,
            'total_xp' => $user->total_xp ?? 0,
            'level_progress' => LearningAnalyticsService::getXPForNextLevel($user->total_xp ?? 0),
            'streak' => $user->current_streak ?? 0,
            'questions_answered' => $user->questions_answered ?? 0,
        ];
    }

    /**
     * Get complete learning profile for API
     */
    public static function getLearningProfile(User $user): array
    {
        return LearningAnalyticsService::getDashboardData($user->id);
    }

    /**
     * End study session and calculate rewards
     */
    public static function endStudySession(User $user): array
    {
        return LearningAnalyticsService::endSession($user->id);
    }
}
