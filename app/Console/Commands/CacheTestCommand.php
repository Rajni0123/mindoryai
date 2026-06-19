<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Cache\SmartCacheService;
use App\Services\Cache\SmartGreetingFilter;
use App\Services\Cache\FollowUpDetector;
use App\Services\Cache\MessageQualityGate;
use App\Services\Cache\TextNormalizer;

class CacheTestCommand extends Command
{
    protected $signature = 'cache:test
                            {question : The question to test}
                            {--type=ai_doubt : Source type (ai_doubt, scan_solve, etc.)}
                            {--subject= : Optional subject filter}
                            {--exam= : Optional exam type filter}
                            {--first : Treat as first message in conversation}
                            {--detailed : Show detailed filter analysis}';

    protected $description = 'Test Smart Cache lookup with a question';

    private SmartCacheService $cacheService;
    private SmartGreetingFilter $greetingFilter;
    private FollowUpDetector $followUpDetector;
    private MessageQualityGate $qualityGate;
    private TextNormalizer $normalizer;

    public function __construct(
        SmartCacheService $cacheService,
        SmartGreetingFilter $greetingFilter,
        FollowUpDetector $followUpDetector,
        MessageQualityGate $qualityGate,
        TextNormalizer $normalizer
    ) {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->greetingFilter = $greetingFilter;
        $this->followUpDetector = $followUpDetector;
        $this->qualityGate = $qualityGate;
        $this->normalizer = $normalizer;
    }

    public function handle(): int
    {
        $question = $this->argument('question');
        $sourceType = $this->option('type');
        $subject = $this->option('subject');
        $examType = $this->option('exam');
        $isFirstMessage = $this->option('first');

        $this->info('🧪 Smart Cache Test');
        $this->newLine();

        $this->line("Question: \"{$question}\"");
        $this->line("Type: {$sourceType}");
        $this->line("First Message: " . ($isFirstMessage ? 'Yes' : 'No'));
        $this->newLine();

        // Show detailed filter analysis
        if ($this->option('detailed')) {
            $this->showDetailedAnalysis($question, $isFirstMessage);
        }

        // Run cache lookup
        $this->info('📍 Running Cache Lookup...');
        $startTime = microtime(true);

        $result = $this->cacheService->lookup(
            $question,
            $sourceType,
            $subject,
            $examType,
            $isFirstMessage
        );

        $elapsed = round((microtime(true) - $startTime) * 1000, 2);

        $this->newLine();

        if ($result['hit']) {
            $this->info("✅ CACHE HIT ({$result['match_level']}) in {$elapsed}ms");
            $this->newLine();
            $this->line("Entry ID: {$result['entry_id']}");
            $this->newLine();
            $this->line("Cached Answer:");
            $this->line("─────────────────────────────────────────");
            $this->line(mb_substr($result['answer'], 0, 500) . (mb_strlen($result['answer']) > 500 ? '...' : ''));
            $this->line("─────────────────────────────────────────");
        } else {
            $this->warn("❌ CACHE MISS in {$elapsed}ms");
            $this->line("Reason: {$result['reason']}");
            $this->line("Message: {$result['message']}");
        }

        $this->newLine();

        // Show hash
        $hash = $this->normalizer->hash($question, $sourceType);
        $this->line("Hash: " . substr($hash, 0, 16) . '...');

        return 0;
    }

    private function showDetailedAnalysis(string $question, bool $isFirstMessage): void
    {
        $this->info('🔍 Detailed Filter Analysis:');
        $this->newLine();

        // Normalization
        $normalized = $this->normalizer->normalize($question);
        $keywords = $this->normalizer->extractKeywords($question);
        $language = $this->normalizer->detectLanguage($question);
        $wordCount = $this->normalizer->wordCount($question);

        $this->line("📝 Text Analysis:");
        $this->line("  Normalized: \"{$normalized}\"");
        $this->line("  Keywords: " . implode(', ', $keywords));
        $this->line("  Language: {$language}");
        $this->line("  Word Count: {$wordCount}");
        $this->newLine();

        // Layer 1: Greeting Filter
        $greetingResult = $this->greetingFilter->shouldSkipCache($question);
        $this->line("🎯 Layer 1 - Greeting Filter:");
        $this->line("  Skip: " . ($greetingResult['skip'] ? '✅ Yes' : '❌ No'));
        $this->line("  Reason: {$greetingResult['reason']}");
        if ($greetingResult['category']) {
            $this->line("  Category: {$greetingResult['category']}");
        }
        $this->newLine();

        // Layer 2: Follow-Up Detector
        $followUpAnalysis = $this->followUpDetector->analyze($question, $isFirstMessage);
        $this->line("🔄 Layer 2 - Follow-Up Detector:");
        $this->line("  Is Follow-Up: " . ($followUpAnalysis['is_followup'] ? '✅ Yes' : '❌ No'));
        $this->line("  Word Count: {$followUpAnalysis['word_count']}");
        $this->line("  Has Academic: " . ($followUpAnalysis['has_academic_keyword'] ? 'Yes' : 'No'));
        $this->line("  Has Context Pronoun: " . ($followUpAnalysis['has_context_pronoun'] ? 'Yes' : 'No'));
        $this->line("  Recommendation: {$followUpAnalysis['recommendation']}");
        $this->newLine();

        // Layer 3: Quality Gate
        $qualityResult = $this->qualityGate->meetsQuality($question);
        $this->line("⚖️ Layer 3 - Quality Gate:");
        $this->line("  Passes: " . ($qualityResult['passes'] ? '✅ Yes' : '❌ No'));
        $this->line("  Reason: {$qualityResult['reason']}");
        if (!empty($qualityResult['details'])) {
            foreach ($qualityResult['details'] as $key => $value) {
                $this->line("  {$key}: " . (is_array($value) ? json_encode($value) : $value));
            }
        }
        $this->newLine();

        // Summary
        $willCache = !$greetingResult['skip']
            && !$followUpAnalysis['is_followup']
            && $qualityResult['passes'];

        $this->info("📋 Summary:");
        $this->line("  Will Cache/Lookup: " . ($willCache ? '✅ Yes' : '❌ No'));
        $this->newLine();
    }
}
