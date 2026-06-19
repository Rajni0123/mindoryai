<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GreetingPattern;

class GreetingPatternsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding greeting patterns...');

        $patterns = [
            // ========== GREETINGS - ENGLISH ==========
            ['pattern' => 'hi', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'hello', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'hey', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'hii', 'category' => 'greeting', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'hiii', 'category' => 'greeting', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'helloo', 'category' => 'greeting', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'hellooo', 'category' => 'greeting', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'good morning', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'good afternoon', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'good evening', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'good night', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'sup', 'category' => 'greeting', 'language' => 'en', 'priority' => 3],
            ['pattern' => 'wassup', 'category' => 'greeting', 'language' => 'en', 'priority' => 3],
            ['pattern' => 'whats up', 'category' => 'greeting', 'language' => 'en', 'priority' => 3],
            ['pattern' => 'yo', 'category' => 'greeting', 'language' => 'en', 'priority' => 3],

            // ========== GREETINGS - HINDI/HINGLISH ==========
            ['pattern' => 'namaste', 'category' => 'greeting', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'namaskar', 'category' => 'greeting', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'pranam', 'category' => 'greeting', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'kaise ho', 'category' => 'greeting', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'kya haal hai', 'category' => 'greeting', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'kya chal raha hai', 'category' => 'greeting', 'language' => 'hi', 'priority' => 2],

            // ========== FAREWELLS ==========
            ['pattern' => 'bye', 'category' => 'farewell', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'goodbye', 'category' => 'farewell', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'see you', 'category' => 'farewell', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'later', 'category' => 'farewell', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'take care', 'category' => 'farewell', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'alvida', 'category' => 'farewell', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'tata', 'category' => 'farewell', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'phir milenge', 'category' => 'farewell', 'language' => 'hi', 'priority' => 2],

            // ========== THANKS ==========
            ['pattern' => 'thanks', 'category' => 'thanks', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'thank you', 'category' => 'thanks', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'thank you so much', 'category' => 'thanks', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'thanku', 'category' => 'thanks', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'thnx', 'category' => 'thanks', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'thx', 'category' => 'thanks', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'ty', 'category' => 'thanks', 'language' => 'en', 'priority' => 3],
            ['pattern' => 'dhanyawad', 'category' => 'thanks', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'shukriya', 'category' => 'thanks', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'bahut shukriya', 'category' => 'thanks', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'bahut dhanyawad', 'category' => 'thanks', 'language' => 'hi', 'priority' => 1],

            // ========== FILLERS - ENGLISH ==========
            ['pattern' => 'ok', 'category' => 'filler', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'okay', 'category' => 'filler', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'okk', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'okkk', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'k', 'category' => 'filler', 'language' => 'en', 'priority' => 3],
            ['pattern' => 'kk', 'category' => 'filler', 'language' => 'en', 'priority' => 3],
            ['pattern' => 'nice', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'great', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'good', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'awesome', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'amazing', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'cool', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'wow', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'wonderful', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'fantastic', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'hmm', 'category' => 'filler', 'language' => 'en', 'priority' => 3],
            ['pattern' => 'hmmm', 'category' => 'filler', 'language' => 'en', 'priority' => 3],
            ['pattern' => 'umm', 'category' => 'filler', 'language' => 'en', 'priority' => 3],
            ['pattern' => 'ummm', 'category' => 'filler', 'language' => 'en', 'priority' => 3],

            // ========== FILLERS - HINDI ==========
            ['pattern' => 'accha', 'category' => 'filler', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'achha', 'category' => 'filler', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'acha', 'category' => 'filler', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'theek', 'category' => 'filler', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'theek hai', 'category' => 'filler', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'thik hai', 'category' => 'filler', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'sahi', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'sahi hai', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'badhiya', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'mast', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'zabardast', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'kamaal', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'wah', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'waah', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'arre wah', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],

            // ========== CONFIRMATIONS ==========
            ['pattern' => 'yes', 'category' => 'confirmation', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'yeah', 'category' => 'confirmation', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'yep', 'category' => 'confirmation', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'yup', 'category' => 'confirmation', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'ya', 'category' => 'confirmation', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'y', 'category' => 'confirmation', 'language' => 'en', 'priority' => 3],
            ['pattern' => 'sure', 'category' => 'confirmation', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'alright', 'category' => 'confirmation', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'right', 'category' => 'confirmation', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'correct', 'category' => 'confirmation', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'haan', 'category' => 'confirmation', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'ha', 'category' => 'confirmation', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'hnji', 'category' => 'confirmation', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'ji', 'category' => 'confirmation', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'ji haan', 'category' => 'confirmation', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'jee', 'category' => 'confirmation', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'bilkul', 'category' => 'confirmation', 'language' => 'hi', 'priority' => 1],

            // ========== DENIALS ==========
            ['pattern' => 'no', 'category' => 'denial', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'nope', 'category' => 'denial', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'nah', 'category' => 'denial', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'na', 'category' => 'denial', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'nahi', 'category' => 'denial', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'nhi', 'category' => 'denial', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'nahin', 'category' => 'denial', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'mat', 'category' => 'denial', 'language' => 'hi', 'priority' => 2],

            // ========== META QUESTIONS ==========
            ['pattern' => 'who are you', 'category' => 'meta', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'what are you', 'category' => 'meta', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'help', 'category' => 'meta', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'help me', 'category' => 'meta', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'what can you do', 'category' => 'meta', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'how can you help', 'category' => 'meta', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'tum kaun ho', 'category' => 'meta', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'aap kaun ho', 'category' => 'meta', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'madad', 'category' => 'meta', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'madad karo', 'category' => 'meta', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'kya kar sakte ho', 'category' => 'meta', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'kaise madad karoge', 'category' => 'meta', 'language' => 'hi', 'priority' => 1],
        ];

        $count = 0;
        foreach ($patterns as $pattern) {
            GreetingPattern::updateOrCreate(
                ['pattern' => $pattern['pattern']],
                array_merge($pattern, ['is_active' => true])
            );
            $count++;
        }

        $this->command->info("✅ Seeded {$count} greeting patterns");
    }
}
