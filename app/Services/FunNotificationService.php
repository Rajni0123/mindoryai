<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class FunNotificationService
{
    /**
     * Zomato-style funny notification templates by education level
     */
    private array $notificationTemplates = [
        'school' => [
            'study_reminder' => [
                ["title" => "Padhai ka time! 📚", "body" => "Homework khud se hota toh magic hota... Chalo shuru karte hain!"],
                ["title" => "Bhai padh le! 🎒", "body" => "Tera phone miss nahi karega, but exam zaroor karega 😅"],
                ["title" => "Break khatam! ⏰", "body" => "YouTube shorts ne bohot time le liya... Ab BlinkStudy ki baari!"],
                ["title" => "Hey Champion! 🏆", "body" => "Topper banna hai ya sirf topper ki copy karni hai? Choice is yours!"],
                ["title" => "Alert! Alert! 🚨", "body" => "Mummy poochegi 'Padhai ki?' Tab kya bolega? Abhi kar le!"],
                ["title" => "Ek minute! ✋", "body" => "Game pause kar, life mein level up karne ka time hai!"],
            ],
            'streak_motivation' => [
                ["title" => "🔥 {streak} din ki streak!", "body" => "Arre wah! Consistency level: Pro! Keep going champ!"],
                ["title" => "Streak alert! 🎯", "body" => "{streak} din ho gaye! Tera dedication dekh ke teacher bhi impress!"],
                ["title" => "Fire hai tu! 🔥", "body" => "{streak} days streak! Class ka sabse consistent student ban raha hai!"],
            ],
            'comeback' => [
                ["title" => "Kahan gayab ho? 😢", "body" => "2 din se dikhe nahi... Hum bhi miss karte hain!"],
                ["title" => "Hello? Testing 1-2-3 📞", "body" => "Tera phone chal raha hai na? BlinkStudy bhi chala de!"],
                ["title" => "Wapas aaja yaar! 🥺", "body" => "Padhai ruk gayi, AI bore ho raha hai... Please come back!"],
            ],
            'achievement' => [
                ["title" => "Topper material! 🌟", "body" => "{count} questions solve kar liye! Parents ko dikhana mat bhoolna!"],
                ["title" => "Genius alert! 🧠", "body" => "Itna padh liya? Tera dimag toh supercomputer hai!"],
            ],
            'night_owl' => [
                ["title" => "Raat ke 11 baj gaye! 🌙", "body" => "Padhai important hai, but neend bhi! Kal fresh mind se milte hain."],
                ["title" => "Chand bhi so gaya! 😴", "body" => "Bohot mehnat kar li aaj. Good night, champion!"],
            ],
            'morning' => [
                ["title" => "Good Morning Star! ☀️", "body" => "Early bird gets the marks! Ready for some quick revision?"],
                ["title" => "Uth gaya? Great! 🌅", "body" => "Morning mein padha hua yaad rehta hai. Let's go!"],
            ],
        ],

        'high_school' => [
            'study_reminder' => [
                ["title" => "Board exam calling! 📞", "body" => "Phone uthao nahi, books uthao! Time is running out..."],
                ["title" => "JEE/NEET prep time! 🎯", "body" => "Har minute important hai. Coaching fees waste mat karo!"],
                ["title" => "Bhai serious ho ja! 😤", "body" => "12th marks life decide karenge. Insta baad mein, padhai abhi!"],
                ["title" => "Future IITian! 🚀", "body" => "Sapne dekh rahe ho ya pursue kar rahe ho? Action time!"],
                ["title" => "Competition tough hai! 💪", "body" => "Jab tu relax kar raha hai, koi aur mehnat kar raha hai..."],
                ["title" => "Quick revision? ⚡", "body" => "15 minute mein ek chapter revise kar le. Har bit counts!"],
            ],
            'streak_motivation' => [
                ["title" => "🔥 {streak} days strong!", "body" => "This consistency will take you to IIT/AIIMS! Keep grinding!"],
                ["title" => "Dedication level 💯", "body" => "{streak} din continuous! Rankers aise hi bante hain!"],
                ["title" => "Unstoppable! 🎯", "body" => "{streak} day streak! Tu toh exam crack kar hi lega!"],
            ],
            'comeback' => [
                ["title" => "Syllabus ruk nahi raha! 📚", "body" => "Tu absent hai but competition nahi. Come back strong!"],
                ["title" => "Miss kar rahe hain! 🥺", "body" => "Tere bina formulas bhi sad feel kar rahe hain..."],
                ["title" => "Exam date fix hai! ⏰", "body" => "Time wait nahi karta, aur exam bhi nahi. Let's restart!"],
            ],
            'achievement' => [
                ["title" => "Rank improve hoga! 📈", "body" => "{count} problems solved! Mock test mein dekhna result."],
                ["title" => "You're killing it! 🔥", "body" => "Is pace se toh definitely crack kar lega!"],
            ],
            'exam_stress' => [
                ["title" => "Stress mat le! 🧘", "body" => "Deep breath. Tu prepared hai. Bas revise kar aur relax kar."],
                ["title" => "You got this! 💪", "body" => "Itni mehnat ki hai, ab result bhi acha hi aayega!"],
            ],
            'night_owl' => [
                ["title" => "Raat bohot ho gayi! 🌙", "body" => "Late night study se zyada early morning study kaam aati hai. Sleep now!"],
                ["title" => "Brain ko rest de! 😴", "body" => "Overloaded brain se kuch yaad nahi rehta. Good night, future ranker!"],
            ],
        ],

        'university' => [
            'study_reminder' => [
                ["title" => "Attendance kam hai! 📊", "body" => "At least BlinkStudy pe toh regular reh le 😅"],
                ["title" => "Placement season coming! 💼", "body" => "CGPA matter karta hai bhai. Abhi nahi toh kab?"],
                ["title" => "Semester end ho jayega! 📅", "body" => "Raat ko jaag ke padhne se better hai ab se thoda-thoda padh le."],
                ["title" => "Internship ke liye! 🎯", "body" => "Skills build kar. Competition bohot hai bahar!"],
                ["title" => "Project pending hai! 💻", "body" => "Last minute submission ki aadat chhod de. Start now!"],
                ["title" => "Mess ka khana skip kar! 🍽️", "body" => "...actually mat kar, but padhai zaroor kar!"],
            ],
            'streak_motivation' => [
                ["title" => "🔥 {streak} days consistency!", "body" => "Yeh dedication placement interview mein dikhna chahiye!"],
                ["title" => "College ka star! ⭐", "body" => "{streak} din! Department mein sabse consistent student!"],
            ],
            'comeback' => [
                ["title" => "Bunk maar liya? 😏", "body" => "College toh kar liya, BlinkStudy bhi kar le. Knowledge doesn't bunk!"],
                ["title" => "Break khatam kar! ☕", "body" => "Canteen se wapas aa. Padhai wait kar rahi hai."],
            ],
            'career' => [
                ["title" => "Dream company! 🏢", "body" => "Amazon, Google, Microsoft... Sapne hai? Mehnat bhi kar!"],
                ["title" => "Resume mein kya daalega? 📄", "body" => "Skills add kar. Time hai abhi bhi!"],
            ],
            'night_owl' => [
                ["title" => "All-nighter? Bad idea! 🌙", "body" => "Sleep deprivation se memory weak hoti hai. Rest kar!"],
            ],
        ],

        'graduate' => [
            'study_reminder' => [
                ["title" => "Research pending! 📑", "body" => "Thesis khud nahi likhega. Coffee leke baith ja!"],
                ["title" => "Publication target! 📚", "body" => "Paper publish karna hai? Writing start kar!"],
                ["title" => "Deadline approaching! ⏳", "body" => "Supervisor poochega progress. Kuch toh dikhana padega!"],
                ["title" => "Knowledge upgrade time! 🎓", "body" => "Industry evolve ho raha hai. Tu bhi evolve ho!"],
                ["title" => "PhD wala dedication! 🔬", "body" => "Research toh patience maangti hai. Aaj ka kaam aaj kar!"],
            ],
            'streak_motivation' => [
                ["title" => "🔥 {streak} days of learning!", "body" => "Lifelong learner mindset! This is how experts are made."],
                ["title" => "Researcher goals! 🎯", "body" => "{streak} din consistent study. Your thesis is getting stronger!"],
            ],
            'comeback' => [
                ["title" => "Break extend ho gaya? 😅", "body" => "Conference deadline wait nahi karegi. Let's get back!"],
                ["title" => "Missing you! 📖", "body" => "Tera research paper incomplete feel kar raha hai..."],
            ],
            'career' => [
                ["title" => "Industry ya Academia? 🤔", "body" => "Jo bhi choose karo, prepared rehna padega. Study time!"],
                ["title" => "Expert banna hai? 🏆", "body" => "10,000 hours rule yaad hai? Counting start kar!"],
            ],
        ],
    ];

    /**
     * Generic notifications for all users
     */
    private array $genericNotifications = [
        'daily_challenge' => [
            ["title" => "Daily Challenge Ready! 🎯", "body" => "Aaj ka challenge complete kar aur coins jeet!"],
            ["title" => "New Challenge Unlocked! 🔓", "body" => "Kya tu aaj ka challenge crack kar payega?"],
        ],
        'quiz_reminder' => [
            ["title" => "Quiz time! 🧠", "body" => "5 minute ka quick quiz? Test your knowledge!"],
            ["title" => "Brain workout! 💪", "body" => "Ek chhota sa quiz, bohot bada confidence boost!"],
        ],
        'feature_discovery' => [
            ["title" => "Naya feature try kiya? ✨", "body" => "AI Doubt Solver se instant answers mil sakte hain!"],
            ["title" => "Pro tip! 💡", "body" => "Camera se scan karke bhi doubt puch sakte ho. Try it!"],
        ],
        'weekend' => [
            ["title" => "Weekend Study Plan! 📅", "body" => "Chill karo but thoda padh bhi lo. Balance is key!"],
            ["title" => "Sunday Funday... aur Study! 🎉", "body" => "1 hour padhai, baaki din masti. Deal?"],
        ],
    ];

    /**
     * Get a random notification for user
     */
    public function getNotification(User $user, string $type = 'study_reminder'): array
    {
        $educationLevel = $user->education_level ?? $user->student_class ?? 'school';

        // Map student class to education level if needed
        if (is_numeric($educationLevel)) {
            $class = (int) $educationLevel;
            if ($class <= 10) {
                $educationLevel = 'school';
            } else {
                $educationLevel = 'high_school';
            }
        }

        // Ensure valid education level
        if (!isset($this->notificationTemplates[$educationLevel])) {
            $educationLevel = 'school';
        }

        // Get templates for this education level and type
        $templates = $this->notificationTemplates[$educationLevel][$type] ??
                     $this->genericNotifications[$type] ??
                     $this->notificationTemplates[$educationLevel]['study_reminder'];

        // Pick random notification
        $notification = $templates[array_rand($templates)];

        // Replace placeholders
        $notification['title'] = $this->replacePlaceholders($notification['title'], $user);
        $notification['body'] = $this->replacePlaceholders($notification['body'], $user);

        return $notification;
    }

    /**
     * Get notification based on user behavior
     */
    public function getSmartNotification(User $user): array
    {
        $hour = (int) date('H');
        $daysSinceLastActivity = $this->getDaysSinceLastActivity($user);

        // Determine notification type based on context
        if ($daysSinceLastActivity >= 2) {
            return $this->getNotification($user, 'comeback');
        }

        if ($hour >= 23 || $hour < 5) {
            return $this->getNotification($user, 'night_owl');
        }

        if ($hour >= 5 && $hour < 9) {
            return $this->getNotification($user, 'morning');
        }

        // Check streak
        if ($user->current_streak >= 7) {
            return $this->getNotification($user, 'streak_motivation');
        }

        // Check achievements
        if ($user->questions_answered > 0 && $user->questions_answered % 50 === 0) {
            return $this->getNotification($user, 'achievement');
        }

        // Weekend check
        $dayOfWeek = (int) date('w');
        if ($dayOfWeek === 0 || $dayOfWeek === 6) {
            if (rand(0, 1) === 1) {
                return $this->getNotification($user, 'weekend');
            }
        }

        // Default study reminder
        return $this->getNotification($user, 'study_reminder');
    }

    /**
     * Get all notification types for a user's education level
     */
    public function getAvailableTypes(User $user): array
    {
        $educationLevel = $user->education_level ?? 'school';

        if (!isset($this->notificationTemplates[$educationLevel])) {
            $educationLevel = 'school';
        }

        return array_keys($this->notificationTemplates[$educationLevel]);
    }

    /**
     * Replace placeholders in notification text
     */
    private function replacePlaceholders(string $text, User $user): string
    {
        $replacements = [
            '{name}' => $user->name ?? 'Student',
            '{firstName}' => explode(' ', $user->name ?? 'Student')[0],
            '{streak}' => $user->current_streak ?? 0,
            '{count}' => $user->questions_answered ?? 0,
            '{level}' => ucfirst($user->education_level ?? 'student'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * Calculate days since last activity
     */
    private function getDaysSinceLastActivity(User $user): int
    {
        $lastActivity = $user->last_active_at ?? $user->updated_at;

        if (!$lastActivity) {
            return 0;
        }

        return now()->diffInDays($lastActivity);
    }

    /**
     * Schedule notification for user (to be called by scheduler)
     */
    public function scheduleSmartNotification(User $user): ?array
    {
        // Check if user has notifications enabled
        if (!$this->shouldSendNotification($user)) {
            return null;
        }

        $notification = $this->getSmartNotification($user);

        // Log for debugging
        Log::info('Smart notification generated', [
            'user_id' => $user->id,
            'education_level' => $user->education_level,
            'notification' => $notification,
        ]);

        return $notification;
    }

    /**
     * Check if we should send notification to user
     */
    private function shouldSendNotification(User $user): bool
    {
        // Check user preferences (can be expanded)
        $preferences = $user->notification_preferences ?? [];

        if (isset($preferences['study_reminders']) && !$preferences['study_reminders']) {
            return false;
        }

        // Don't send too many notifications
        $hour = (int) date('H');
        if ($hour < 7 || $hour > 22) {
            return false; // Quiet hours
        }

        return true;
    }
}
