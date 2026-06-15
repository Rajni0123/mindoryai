<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GreetingPattern;
use Illuminate\Support\Facades\DB;

class CacheSeedCommand extends Command
{
    protected $signature = 'cache:seed
                            {--patterns : Seed greeting patterns}
                            {--formulas : Seed common formulas}
                            {--force : Force re-seed (delete existing data)}';

    protected $description = 'Seed Smart Cache tables with default data';

    public function handle(): int
    {
        $this->info('🌱 Seeding Smart Cache Data...');
        $this->newLine();

        if ($this->option('patterns') || (!$this->option('patterns') && !$this->option('formulas'))) {
            $this->seedGreetingPatterns();
        }

        if ($this->option('formulas')) {
            $this->seedFormulas();
        }

        $this->newLine();
        $this->info('✅ Seeding complete!');

        return 0;
    }

    private function seedGreetingPatterns(): void
    {
        $this->info('📝 Seeding Greeting Patterns...');

        if ($this->option('force')) {
            GreetingPattern::truncate();
            $this->line('  Cleared existing patterns');
        }

        $patterns = [
            // Greetings - English
            ['pattern' => 'hi', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'hello', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'hey', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'good morning', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'good afternoon', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'good evening', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'good night', 'category' => 'greeting', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'sup', 'category' => 'greeting', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'wassup', 'category' => 'greeting', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'yo', 'category' => 'greeting', 'language' => 'en', 'priority' => 2],

            // Greetings - Hindi/Hinglish
            ['pattern' => 'namaste', 'category' => 'greeting', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'namaskar', 'category' => 'greeting', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'kaise ho', 'category' => 'greeting', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'kya haal hai', 'category' => 'greeting', 'language' => 'hi', 'priority' => 2],

            // Farewells
            ['pattern' => 'bye', 'category' => 'farewell', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'goodbye', 'category' => 'farewell', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'see you', 'category' => 'farewell', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'later', 'category' => 'farewell', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'alvida', 'category' => 'farewell', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'tata', 'category' => 'farewell', 'language' => 'hi', 'priority' => 2],

            // Thanks
            ['pattern' => 'thanks', 'category' => 'thanks', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'thank you', 'category' => 'thanks', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'thanku', 'category' => 'thanks', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'thnx', 'category' => 'thanks', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'thx', 'category' => 'thanks', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'ty', 'category' => 'thanks', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'dhanyawad', 'category' => 'thanks', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'shukriya', 'category' => 'thanks', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'bahut shukriya', 'category' => 'thanks', 'language' => 'hi', 'priority' => 1],

            // Fillers - English
            ['pattern' => 'ok', 'category' => 'filler', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'okay', 'category' => 'filler', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'nice', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'great', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'good', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'awesome', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'cool', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'wow', 'category' => 'filler', 'language' => 'en', 'priority' => 2],
            ['pattern' => 'hmm', 'category' => 'filler', 'language' => 'en', 'priority' => 3],
            ['pattern' => 'umm', 'category' => 'filler', 'language' => 'en', 'priority' => 3],

            // Fillers - Hindi
            ['pattern' => 'accha', 'category' => 'filler', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'achha', 'category' => 'filler', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'acha', 'category' => 'filler', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'theek', 'category' => 'filler', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'theek hai', 'category' => 'filler', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'thik hai', 'category' => 'filler', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'sahi', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'badhiya', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'mast', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'zabardast', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],
            ['pattern' => 'wah', 'category' => 'filler', 'language' => 'hi', 'priority' => 2],

            // Meta Questions
            ['pattern' => 'who are you', 'category' => 'meta', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'what are you', 'category' => 'meta', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'help', 'category' => 'meta', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'help me', 'category' => 'meta', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'what can you do', 'category' => 'meta', 'language' => 'en', 'priority' => 1],
            ['pattern' => 'tum kaun ho', 'category' => 'meta', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'aap kaun ho', 'category' => 'meta', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'madad', 'category' => 'meta', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'madad karo', 'category' => 'meta', 'language' => 'hi', 'priority' => 1],
            ['pattern' => 'kya kar sakte ho', 'category' => 'meta', 'language' => 'hi', 'priority' => 1],
        ];

        $count = 0;
        foreach ($patterns as $pattern) {
            GreetingPattern::updateOrCreate(
                ['pattern' => $pattern['pattern']],
                array_merge($pattern, ['is_active' => true])
            );
            $count++;
        }

        $this->line("  ✅ Seeded {$count} greeting patterns");
    }

    private function seedFormulas(): void
    {
        $this->info('📐 Seeding Common Formulas...');
        $this->warn('  Formula seeding not yet implemented');
        // TODO: Add common physics, chemistry, math formulas
    }
}
