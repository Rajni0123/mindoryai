<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    protected $table = 'questions_bank';

    protected $fillable = [
        'original_question',
        'normalized_question',
        'answer',
        'subject',
        'topic',
        'exam_type',
        'difficulty',
        'question_hash',
        'keywords',
        'hit_count',
        'api_cost_saved',
        'ai_model',
    ];

    protected $casts = [
        'keywords' => 'array',
        'hit_count' => 'integer',
        'api_cost_saved' => 'integer',
    ];

    /**
     * Search for matching question - 3 level matching
     * Level 1: Exact hash match
     * Level 2: Fulltext search match
     * Level 3: Keyword similarity match
     */
    public static function findMatch(string $question): ?self
    {
        $normalized = self::normalize($question);
        $hash = hash('sha256', $normalized);

        // Level 1: Exact match via hash
        $exact = self::where('question_hash', $hash)->first();
        if ($exact) {
            $exact->increment('hit_count');
            $exact->increment('api_cost_saved', 15); // ~15 paisa per API call saved
            return $exact;
        }

        // Level 2: MySQL Fulltext search
        $fulltextMatch = self::whereRaw(
            "MATCH(original_question, normalized_question) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$normalized]
        )
        ->selectRaw("*, MATCH(original_question, normalized_question) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score", [$normalized])
        ->having('relevance_score', '>', 5) // threshold - tune this based on testing
        ->orderByDesc('relevance_score')
        ->first();

        if ($fulltextMatch) {
            $fulltextMatch->increment('hit_count');
            $fulltextMatch->increment('api_cost_saved', 15);
            return $fulltextMatch;
        }

        // Level 3: Keyword similarity
        $keywords = self::extractKeywords($normalized);
        if (count($keywords) >= 2) {
            $keywordMatch = self::where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('normalized_question', 'LIKE', "%{$keyword}%");
                }
            })
            ->get()
            ->map(function ($item) use ($keywords) {
                $itemKeywords = $item->keywords ?? [];
                $commonKeywords = array_intersect($keywords, $itemKeywords);
                $totalKeywords = array_unique(array_merge($keywords, $itemKeywords));
                $item->similarity = count($totalKeywords) > 0
                    ? count($commonKeywords) / count($totalKeywords)
                    : 0;
                return $item;
            })
            ->filter(fn($item) => $item->similarity >= 0.4) // 40% similarity threshold
            ->sortByDesc('similarity')
            ->first();

            if ($keywordMatch) {
                $keywordMatch->increment('hit_count');
                $keywordMatch->increment('api_cost_saved', 15);
                return $keywordMatch;
            }
        }

        return null; // No match found, need API call
    }

    /**
     * Store new question-answer pair
     */
    public static function storeAnswer(string $question, string $answer, array $meta = []): self
    {
        $normalized = self::normalize($question);

        return self::create([
            'original_question' => $question,
            'normalized_question' => $normalized,
            'answer' => $answer,
            'question_hash' => hash('sha256', $normalized),
            'keywords' => self::extractKeywords($normalized),
            'subject' => $meta['subject'] ?? null,
            'topic' => $meta['topic'] ?? null,
            'exam_type' => $meta['exam_type'] ?? null,
            'difficulty' => $meta['difficulty'] ?? null,
            'ai_model' => $meta['ai_model'] ?? 'gemini',
        ]);
    }

    /**
     * Normalize question text for consistent matching
     */
    public static function normalize(string $question): string
    {
        $text = mb_strtolower(trim($question));

        // Remove special characters but keep important math symbols
        $text = preg_replace('/[^\w\s\+\-\=\(\)\^\/\.\,\?\:]+/u', ' ', $text);

        // Common Hindi-English replacements
        $replacements = [
            'kya hai' => 'what is',
            'kya hota hai' => 'what is',
            'samjhao' => 'explain',
            'samjhaiye' => 'explain',
            'batao' => 'tell',
            'bataiye' => 'tell',
            'likho' => 'write',
            'kaise' => 'how',
            'kyun' => 'why',
            'kyon' => 'why',
            'paribhasha' => 'definition',
            'sutra' => 'formula',
            'niyam' => 'law',
            'udaharan' => 'example',
            'ka matlab' => 'meaning of',
        ];

        foreach ($replacements as $hindi => $english) {
            $text = str_replace($hindi, $english, $text);
        }

        // Remove stopwords
        $stopwords = ['the', 'is', 'a', 'an', 'of', 'in', 'to', 'and', 'for', 'with', 'on', 'at',
                       'ka', 'ki', 'ke', 'ko', 'me', 'se', 'hai', 'hain', 'ye', 'yeh', 'wo', 'woh',
                       'aur', 'ya', 'par', 'pe', 'ne', 'karo', 'kare', 'do', 'de', 'please', 'sir',
                       'mujhe', 'mera', 'mere', 'humko', 'hume'];

        $words = explode(' ', $text);
        $words = array_filter($words, fn($w) => !in_array($w, $stopwords) && strlen($w) > 1);

        // Remove extra spaces
        $text = preg_replace('/\s+/', ' ', implode(' ', $words));

        return trim($text);
    }

    /**
     * Extract important keywords from normalized text
     */
    public static function extractKeywords(string $normalizedText): array
    {
        $words = explode(' ', $normalizedText);

        // Scientific/important terms get priority
        $importantPatterns = [
            'newton', 'einstein', 'bohr', 'coulomb', 'ohm', 'kirchhoff', 'faraday',
            'thermodynamic', 'kinetic', 'potential', 'electric', 'magnetic', 'nuclear',
            'organic', 'inorganic', 'periodic', 'reaction', 'equilibrium', 'catalyst',
            'cell', 'dna', 'rna', 'protein', 'enzyme', 'photosynthesis', 'mitosis',
            'derivative', 'integral', 'matrix', 'vector', 'probability', 'trigonometry',
            'formula', 'equation', 'theorem', 'law', 'principle', 'definition',
            'acid', 'base', 'salt', 'solution', 'compound', 'element', 'atom', 'molecule',
            'force', 'energy', 'momentum', 'velocity', 'acceleration', 'gravity',
            'wave', 'frequency', 'wavelength', 'amplitude', 'refraction', 'diffraction',
        ];

        $keywords = array_filter($words, function ($word) use ($importantPatterns) {
            if (strlen($word) < 3) return false;
            foreach ($importantPatterns as $pattern) {
                if (str_contains($word, $pattern)) return true;
            }
            return strlen($word) >= 4; // Keep longer words as keywords
        });

        return array_values(array_unique($keywords));
    }
}
