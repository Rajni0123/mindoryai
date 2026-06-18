<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Smart Cache System Configuration
    |--------------------------------------------------------------------------
    | Feature-wise cache settings for Mindory/BlinkStudy AI
    */

    // =========================================
    // NEW: Bug-Proof Caching Settings
    // =========================================
    'conversation_active_window' => env('CACHE_ACTIVE_WINDOW', 600), // 10 minutes
    'min_response_length_to_store' => 100,
    'enabled' => env('SMART_CACHE_ENABLED', true),

    'blocked_message_types' => [
        'continuation',
        'greeting',
        'context_dependent',
    ],

    // Features with caching ENABLED (with TTL in days)
    'cacheable_features' => [
        'ai_doubt' => ['enabled' => true, 'ttl_days' => 90],
        'scan_solve' => ['enabled' => true, 'ttl_days' => 90],
        'concept_explain' => ['enabled' => true, 'ttl_days' => 180],
        'pdf_doubt' => ['enabled' => true, 'ttl_days' => 60],
        'exam_prep' => ['enabled' => true, 'ttl_days' => 60],
        'formula_query' => ['enabled' => true, 'ttl_days' => 365],
        'definition' => ['enabled' => true, 'ttl_days' => 365],
        'summary' => ['enabled' => true, 'ttl_days' => 180],
        'ncert_content' => ['enabled' => true, 'ttl_days' => 365],
        'video_storyboard' => ['enabled' => true, 'ttl_days' => 90],
        'video_images' => ['enabled' => true, 'ttl_days' => 30],
    ],

    // Features that should NEVER be cached
    'never_cache' => [
        'quiz_generate',
        'study_battle',
        'daily_challenge',
        'chat_followup',
        'leaderboard',
        'live_test',
        'mock_test',
    ],

    // Greeting filter configuration
    'greeting_filter' => [
        'enabled' => true,
        'max_word_count' => 5,
        'collapse_repeated_chars' => true,
    ],

    // Expanded greeting keywords (used by SmartGreetingFilter)
    'greeting_keywords' => [
        // English greetings
        'hi', 'hello', 'hey', 'hii', 'hiii', 'hiiii', 'helloo', 'hellooo',
        'good morning', 'good afternoon', 'good evening', 'good night',
        'whats up', 'wassup', 'wazzup', 'sup', 'yo', 'howdy',
        // Hindi/Regional greetings
        'namaste', 'namaskar', 'pranam', 'ram ram', 'jai hind',
        'kaise ho', 'kya haal hai', 'kya haal', 'kem cho',
        'sat sri akal', 'vanakkam', 'assalam', 'salam',
        // Casual
        'bhai', 'bro', 'dost', 'yaar',
    ],

    // Expanded continuation/follow-up keywords
    'continuation_keywords' => [
        // English confirmations
        'yes', 'yeah', 'yep', 'yup', 'ya', 'y', 'ok', 'okay', 'okk', 'okkk',
        'sure', 'right', 'correct', 'exactly', 'got it', 'understood',
        'continue', 'go ahead', 'go on', 'next', 'proceed', 'keep going',
        'tell more', 'what next', 'then what', 'and then', 'alright', 'fine',
        // Hindi/Hinglish confirmations
        'haan', 'ha', 'haa', 'ji', 'ji ha', 'ji haan', 'hnji', 'jee',
        'theek hai', 'thik hai', 'accha', 'achha', 'acha', 'sahi', 'sahi hai',
        'bilkul', 'samjha', 'samjh gaya', 'samajh gaya',
        // Continue requests
        'aur batao', 'aage', 'aage batao', 'phir', 'fir', 'toh', 'aur',
        'karo', 'bolo', 'batao', 'phir kya', 'aur sunao',
        // Short reactions
        'k', 'kk', 'hmm', 'hmmm', 'hmmmm', 'ohh', 'ohhh',
        'wow', 'nice', 'great', 'good', 'awesome', 'amazing', 'cool',
        'thanks', 'thanku', 'thnx', 'thx', 'ty', 'dhanyavaad', 'shukriya',
    ],

    // Matching configuration
    'matching' => [
        'exact_hash' => true,
        'fulltext' => true,
        'keyword' => true,
        'similarity_threshold' => 0.80,        // was 0.75 - stricter
        'min_fulltext_relevance' => 1.5,       // was 1.0 - stricter
        'max_keyword_candidates' => 10,
        'min_chars_for_fuzzy' => 20,           // NEW - minimum chars for fuzzy matching
        'min_words_for_fuzzy' => 4,            // NEW - minimum words for fuzzy matching
    ],

    // Cost tracking (in INR per call)
    'costs' => [
        'gemini_flash' => 0.12,
        'gemini_vision' => 0.15,
        'image_gen' => 0.08,
        'openai_gpt4' => 0.50,
        'openai_gpt35' => 0.10,
    ],

    // Text normalization settings
    'normalization' => [
        'remove_stopwords' => true,
        'hinglish_mapping' => true,
        'lowercase' => true,
        'remove_punctuation' => true,
        'collapse_whitespace' => true,
    ],

    // Quality gate settings
    'quality_gate' => [
        'min_characters' => 8,                // Lowered to 8 to allow "human eye" (9 chars)
        'min_words' => 2,                     // FIXED: 2 words allows "What is X?" questions
        'require_academic_keyword' => true,
        'min_chars_for_cache_lookup' => 15,   // Safety net - blocks ultra-short (bypassed for academic keywords)
        'bypass_char_limit_if_academic' => true,  // Allow short messages if they have academic keywords
    ],

    // Follow-up detection settings - TIGHTENED
    'followup_detection' => [
        'enabled' => true,
        'max_chars_without_keyword' => 15,    // was 20 - stricter
        'check_pronouns' => true,
        'check_continuations' => true,        // NEW
    ],

    // Analytics settings
    'analytics' => [
        'enabled' => true,
        'aggregate_daily' => true,
    ],

    // Hinglish to English mapping - EXPANDED
    'hinglish_map' => [
        // Common verbs
        'samjhao' => 'explain',
        'batao' => 'tell',
        'nikalo' => 'find',
        'likho' => 'write',
        'sikhao' => 'teach',
        'padho' => 'read',
        'bujho' => 'understand',
        'samajh' => 'understand',
        'karo' => 'do',
        'dikhao' => 'show',
        'hisab lagao' => 'calculate',
        // Question words
        'kya hai' => 'what is',
        'kaise' => 'how',
        'kyu' => 'why',
        'kyun' => 'why',
        'kab' => 'when',
        'kaha' => 'where',
        'kaun' => 'who',
        'kitna' => 'how much',
        'kitne' => 'how many',
        // Subjects - NEW additions
        'padhai' => 'study',
        'pariksha' => 'exam',
        'kaksha' => 'class',
        'adhyay' => 'chapter',
        'samasya' => 'problem',
        'hisab' => 'math',
        'angrezi' => 'english',
        'hindi' => 'hindi',
        'itihas' => 'history',
        'bhugol' => 'geography',
        'arthshastra' => 'economics',
        'nagrik shastra' => 'civics',
        'pustak' => 'book',
        'panna' => 'page',
        'sawal' => 'question',
        'jawab' => 'answer',
        // Existing mappings
        'paribhasha' => 'definition',
        'sutra' => 'formula',
        'ganit' => 'mathematics',
        'bhautiki' => 'physics',
        'rasayan' => 'chemistry',
        'jeev' => 'biology',
        'vigyan' => 'science',
        'prashna' => 'question',
        'uttar' => 'answer',
        'hal' => 'solution',
        'matlab' => 'meaning',
        'arth' => 'meaning',
        'uddaharan' => 'example',
        'prakar' => 'type',
        'vibhinna' => 'different',
        'vishleshan' => 'analysis',
        'sankhya' => 'number',
        'rekhaganak' => 'geometry',
        'beejganit' => 'algebra',
        'trigonmiti' => 'trigonometry',
        'kalculas' => 'calculus',
        'prakriya' => 'process',
        'siddhant' => 'theory',
        'niyam' => 'law',
        'pramanik' => 'standard',
        'ekak' => 'unit',
        'maan' => 'value',
        'guna' => 'property',
        'visheshta' => 'characteristic',
    ],

    // English stopwords
    'stopwords_en' => [
        'a', 'an', 'the', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'to', 'of', 'in', 'for', 'on', 'at', 'by', 'with', 'from', 'about',
        'please', 'sir', 'mam', 'madam', 'can', 'you', 'could', 'would', 'should',
        'i', 'me', 'my', 'we', 'our', 'do', 'does', 'did', 'have', 'has', 'had',
        'will', 'shall', 'may', 'might', 'must', 'need', 'want', 'like',
        'just', 'also', 'very', 'really', 'so', 'too', 'much', 'more',
        'this', 'that', 'these', 'those', 'there', 'here', 'where', 'when',
        'and', 'or', 'but', 'if', 'then', 'else', 'because', 'as', 'while',
    ],

    // Hindi/Hinglish stopwords
    // NOTE: 'batao', 'bata', 'karo', 'aur', 'bolo' removed - they are continuation keywords!
    'stopwords_hi' => [
        'ka', 'ke', 'ki', 'ko', 'hai', 'hain', 'ho', 'tha', 'thi', 'the',
        'toh', 'bhi', 'ya', 'par', 'pe', 'se', 'mein', 'me',
        'mujhe', 'muje', 'bhai', 'yaar', 'sir', 'mam', 'didi',
        'please', 'plz', 'pls', 'krdo', 'kardo', 'kro',
        'de', 'do', 'dijiye', 'dena', 'lena',
        'ek', 'yeh', 'ye', 'woh', 'wo', 'kya', 'kaun', 'kab', 'kaha',
        'abhi', 'jaldi', 'thoda', 'bahut', 'zyada', 'kam', 'bilkul',
        'na', 'mat', 'nahi', 'nhi',
    ],

    // Academic keywords for quality gate
    'academic_keywords_en' => [
        // Physics
        'physics', 'force', 'energy', 'momentum', 'velocity', 'acceleration',
        'mass', 'weight', 'gravity', 'friction', 'motion', 'wave', 'light',
        'electric', 'magnetic', 'current', 'voltage', 'resistance', 'circuit',
        'atom', 'electron', 'proton', 'neutron', 'nucleus', 'quantum',
        'thermodynamics', 'heat', 'temperature', 'pressure', 'volume',

        // Chemistry
        'chemistry', 'element', 'compound', 'molecule', 'atom', 'ion',
        'reaction', 'acid', 'base', 'salt', 'ph', 'oxidation', 'reduction',
        'organic', 'inorganic', 'carbon', 'hydrogen', 'oxygen', 'nitrogen',
        'periodic', 'table', 'valence', 'bond', 'covalent', 'ionic',
        'mole', 'molarity', 'concentration', 'solution', 'solute', 'solvent',

        // Biology
        'biology', 'cell', 'tissue', 'organ', 'system', 'organism',
        'dna', 'rna', 'gene', 'chromosome', 'protein', 'enzyme',
        'photosynthesis', 'respiration', 'digestion', 'circulation',
        'reproduction', 'evolution', 'ecology', 'ecosystem', 'species',
        'bacteria', 'virus', 'plant', 'animal', 'human', 'body',
        'eye', 'ear', 'heart', 'brain', 'lung', 'kidney', 'liver', 'stomach',
        'blood', 'nerve', 'muscle', 'bone', 'skin', 'mitochondria', 'nucleus',

        // Mathematics
        'math', 'mathematics', 'algebra', 'geometry', 'calculus', 'trigonometry',
        'trigonometric', 'ratio', 'ratios', 'sine', 'cosine', 'tangent',
        'equation', 'formula', 'function', 'variable', 'constant', 'coefficient',
        'derivative', 'integral', 'limit', 'series', 'sequence', 'matrix',
        'vector', 'polynomial', 'quadratic', 'linear', 'exponential', 'logarithm',
        'angle', 'triangle', 'circle', 'area', 'perimeter', 'volume',
        'probability', 'statistics', 'mean', 'median', 'mode', 'deviation',

        // General academic
        'explain', 'define', 'describe', 'compare', 'contrast', 'analyze',
        'calculate', 'solve', 'prove', 'derive', 'find', 'determine',
        'law', 'theorem', 'principle', 'theory', 'concept', 'definition',
        'example', 'application', 'formula', 'equation', 'diagram',
    ],

    'academic_keywords_hi' => [
        // Physics
        'bhautiki', 'bal', 'urja', 'sanveg', 'veg', 'tvarann',
        'dravyaman', 'bhar', 'gurutvakarshan', 'ghatiya', 'gati',

        // Chemistry
        'rasayan', 'tatva', 'yaugik', 'anu', 'parmanu',
        'abhikriya', 'aml', 'ksharak', 'lavan',

        // Biology
        'jeev', 'koshika', 'utak', 'ang', 'tantra',
        'prakash sanshleshan', 'shwasan', 'pachan',

        // Mathematics
        'ganit', 'beejganit', 'rekhaganak', 'trigonmiti', 'kalculas',
        'samikaran', 'sutra', 'phalan', 'char', 'acharak',

        // General
        'samjhao', 'paribhasha', 'varnan', 'tulna', 'vishleshan',
        'ganana', 'hal', 'siddh', 'nikalo', 'gyat',
        'niyam', 'prameya', 'siddhant', 'avdharna', 'paribhasha',
    ],
];
