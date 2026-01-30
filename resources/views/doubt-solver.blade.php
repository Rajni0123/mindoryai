@extends('layouts.app')

@section('title', 'AI Doubt Solver - Student Tutor')

@section('content')
<div class="min-h-screen dark:bg-transparent bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold dark:text-white text-gray-900 mb-2">
                AI Doubt Solver
            </h1>
            <p class="text-lg dark:text-gray-400 text-gray-600">
                Your Personal AI Tutor - Ask anything, learn everything!
            </p>
        </div>

        <!-- Subject Selection -->
        <div class="dark:bg-white/[0.02] bg-white rounded-lg shadow-md dark:shadow-none dark:border dark:border-white/[0.06] p-6 mb-6">
            <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">
                Select Subject (Optional)
            </label>
            <div class="flex flex-wrap gap-2">
                <button onclick="setSubject('Mathematics')" class="subject-btn px-4 py-2 dark:bg-blue-500/10 bg-blue-100 dark:text-blue-400 text-blue-700 rounded-lg dark:hover:bg-blue-500/20 hover:bg-blue-200 transition">
                    Mathematics
                </button>
                <button onclick="setSubject('Physics')" class="subject-btn px-4 py-2 dark:bg-green-500/10 bg-green-100 dark:text-green-400 text-green-700 rounded-lg dark:hover:bg-green-500/20 hover:bg-green-200 transition">
                    Physics
                </button>
                <button onclick="setSubject('Chemistry')" class="subject-btn px-4 py-2 dark:bg-purple-500/10 bg-purple-100 dark:text-purple-400 text-purple-700 rounded-lg dark:hover:bg-purple-500/20 hover:bg-purple-200 transition">
                    Chemistry
                </button>
                <button onclick="setSubject('Biology')" class="subject-btn px-4 py-2 dark:bg-pink-500/10 bg-pink-100 dark:text-pink-400 text-pink-700 rounded-lg dark:hover:bg-pink-500/20 hover:bg-pink-200 transition">
                    Biology
                </button>
                <button onclick="setSubject('English')" class="subject-btn px-4 py-2 dark:bg-yellow-500/10 bg-yellow-100 dark:text-yellow-400 text-yellow-700 rounded-lg dark:hover:bg-yellow-500/20 hover:bg-yellow-200 transition">
                    English
                </button>
                <button onclick="setSubject('Hindi')" class="subject-btn px-4 py-2 dark:bg-orange-500/10 bg-orange-100 dark:text-orange-400 text-orange-700 rounded-lg dark:hover:bg-orange-500/20 hover:bg-orange-200 transition">
                    Hindi
                </button>
                <button onclick="setSubject('Computer Science')" class="subject-btn px-4 py-2 dark:bg-indigo-500/10 bg-indigo-100 dark:text-indigo-400 text-indigo-700 rounded-lg dark:hover:bg-indigo-500/20 hover:bg-indigo-200 transition">
                    Computer Science
                </button>
            </div>
            <p class="mt-2 text-sm dark:text-gray-500 text-gray-500">Current Subject: <span id="current-subject" class="font-semibold">None</span></p>
        </div>

        <!-- Feature Tabs -->
        <div class="dark:bg-white/[0.02] bg-white rounded-lg shadow-md dark:shadow-none dark:border dark:border-white/[0.06] mb-6 overflow-x-auto">
            <div class="flex gap-2 p-4 border-b dark:border-white/[0.06] border-gray-200">
                <button onclick="switchTab('doubt')" id="tab-doubt" class="tab-btn active px-4 py-2 rounded-lg font-medium transition">
                    Doubt Solver
                </button>
                <button onclick="switchTab('quiz')" id="tab-quiz" class="tab-btn px-4 py-2 rounded-lg font-medium transition">
                    Quiz
                </button>
                <button onclick="switchTab('practice')" id="tab-practice" class="tab-btn px-4 py-2 rounded-lg font-medium transition">
                    Practice
                </button>
                <button onclick="switchTab('concept')" id="tab-concept" class="tab-btn px-4 py-2 rounded-lg font-medium transition">
                    Concepts
                </button>
                <button onclick="switchTab('hint')" id="tab-hint" class="tab-btn px-4 py-2 rounded-lg font-medium transition">
                    Hints
                </button>
                <button onclick="switchTab('summary')" id="tab-summary" class="tab-btn px-4 py-2 rounded-lg font-medium transition">
                    Summary
                </button>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Content Area (Left - 2/3) -->
            <div class="lg:col-span-2">

                <!-- Tab 1: Doubt Solver -->
                <div id="content-doubt" class="tab-content dark:bg-white/[0.02] bg-white rounded-lg shadow-md dark:shadow-none dark:border dark:border-white/[0.06] overflow-hidden">
                    <!-- Chat Messages -->
                    <div id="chat-messages" class="h-[500px] overflow-y-auto p-6 space-y-4">
                        <!-- Welcome Message -->
                        <div class="text-center dark:text-gray-500 text-gray-500 py-12">
                            <div class="text-6xl mb-4">🎓</div>
                            <p class="text-lg font-medium">Welcome to AI Doubt Solver!</p>
                            <p class="text-sm mt-2">Ask your doubts in text or upload an image</p>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div class="border-t dark:border-white/[0.06] border-gray-200 p-4 dark:bg-white/[0.02] bg-gray-50">
                        <!-- Text Input -->
                        <div class="flex gap-2 mb-3">
                            <textarea
                                id="doubt-input"
                                rows="2"
                                class="flex-1 px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent resize-none dark:bg-white/5 dark:text-white dark:placeholder-gray-500"
                                placeholder="Type your doubt here... (e.g., 'Quadratic equation kya hai?')"
                            ></textarea>
                            <button
                                onclick="sendTextDoubt()"
                                class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition font-medium"
                            >
                                Send
                            </button>
                        </div>

                        <!-- Image Upload -->
                        <div class="flex gap-2 items-center">
                            <label class="flex-1 cursor-pointer">
                                <div class="flex items-center gap-2 px-4 py-2 dark:bg-white/5 bg-white border-2 border-dashed dark:border-white/10 border-gray-300 rounded-lg dark:hover:border-primary/40 hover:border-primary/40 transition">
                                    <svg class="w-5 h-5 dark:text-gray-400 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-sm dark:text-gray-400 text-gray-600">Upload Image (Photo/Screenshot)</span>
                                </div>
                                <input
                                    type="file"
                                    id="image-input"
                                    accept="image/*"
                                    class="hidden"
                                    onchange="handleImageUpload()"
                                >
                            </label>
                            <button
                                onclick="clearHistory()"
                                class="px-4 py-2 dark:bg-red-500/10 bg-red-100 dark:text-red-400 text-red-700 rounded-lg dark:hover:bg-red-500/20 hover:bg-red-200 transition text-sm"
                            >
                                Clear Chat
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Quiz Generator -->
                <div id="content-quiz" class="tab-content hidden dark:bg-white/[0.02] bg-white rounded-lg shadow-md dark:shadow-none dark:border dark:border-white/[0.06] p-6">
                    <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">Generate Quiz</h2>
                    <p class="dark:text-gray-400 text-gray-600 mb-6">Create MCQ quizzes from any topic or upload an image with questions/notes</p>

                    <!-- Mode Toggle -->
                    <div class="flex gap-2 mb-6">
                        <button onclick="setQuizMode('topic')" id="quiz-mode-topic" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition">
                            By Topic
                        </button>
                        <button onclick="setQuizMode('image')" id="quiz-mode-image" class="flex-1 px-4 py-2 dark:bg-white/5 bg-gray-200 dark:text-gray-300 text-gray-700 rounded-lg font-medium transition">
                            From Image
                        </button>
                    </div>

                    <!-- Topic Mode Form -->
                    <div id="quiz-form-topic" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Topic *</label>
                            <input type="text" id="quiz-topic" class="w-full px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary dark:bg-white/5 dark:text-white dark:placeholder-gray-500" placeholder="e.g., Pythagorean Theorem">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Number of Questions</label>
                                <select id="quiz-num" class="w-full px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary dark:bg-white/5 dark:text-white">
                                    <option value="5">5 Questions</option>
                                    <option value="10">10 Questions</option>
                                    <option value="15">15 Questions</option>
                                    <option value="20">20 Questions</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Difficulty</label>
                                <select id="quiz-difficulty" class="w-full px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary dark:bg-white/5 dark:text-white">
                                    <option value="easy">Easy</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                        </div>

                        <button onclick="generateQuiz()" class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition font-medium">
                            Generate Quiz
                        </button>
                    </div>

                    <!-- Image Mode Form -->
                    <div id="quiz-form-image" class="space-y-4 hidden">
                        <div>
                            <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Upload Image *</label>
                            <label class="flex items-center justify-center w-full px-4 py-8 border-2 border-dashed dark:border-white/10 border-gray-300 rounded-lg cursor-pointer dark:hover:border-primary/40 hover:border-primary/40 transition">
                                <div class="text-center">
                                    <svg class="w-10 h-10 dark:text-gray-500 text-gray-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-sm dark:text-gray-400 text-gray-600">Click to upload image</span>
                                    <p class="text-xs dark:text-gray-500 text-gray-500 mt-1">PNG, JPG, GIF, WEBP up to 10MB</p>
                                    <input type="file" id="quiz-image" accept="image/*" class="hidden" onchange="previewQuizImage(this)">
                                </div>
                            </label>
                        </div>

                        <!-- Image Preview -->
                        <div id="quiz-image-preview" class="hidden">
                            <img id="quiz-image-preview-img" class="max-h-40 mx-auto rounded-lg border dark:border-white/10 border-gray-300">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Number of Questions</label>
                                <select id="quiz-num-img" class="w-full px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary dark:bg-white/5 dark:text-white">
                                    <option value="5">5 Questions</option>
                                    <option value="10">10 Questions</option>
                                    <option value="15">15 Questions</option>
                                    <option value="20">20 Questions</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Difficulty</label>
                                <select id="quiz-difficulty-img" class="w-full px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary dark:bg-white/5 dark:text-white">
                                    <option value="easy">Easy</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                        </div>

                        <button onclick="generateQuizFromImage()" class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition font-medium">
                            Generate Quiz from Image
                        </button>
                    </div>

                    <div id="quiz-result" class="mt-6 hidden">
                        <div class="dark:bg-white/5 bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto dark:border dark:border-white/[0.06]">
                            <div id="quiz-content" class="prose prose-sm dark:prose-invert max-w-none"></div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Practice Problems -->
                <div id="content-practice" class="tab-content hidden dark:bg-white/[0.02] bg-white rounded-lg shadow-md dark:shadow-none dark:border dark:border-white/[0.06] p-6">
                    <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">Practice Problems</h2>
                    <p class="dark:text-gray-400 text-gray-600 mb-6">Generate practice problems with hints to improve your skills</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Topic *</label>
                            <input type="text" id="practice-topic" class="w-full px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary dark:bg-white/5 dark:text-white dark:placeholder-gray-500" placeholder="e.g., Quadratic Equations">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Number of Problems</label>
                                <select id="practice-num" class="w-full px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary dark:bg-white/5 dark:text-white">
                                    <option value="3">3 Problems</option>
                                    <option value="5" selected>5 Problems</option>
                                    <option value="8">8 Problems</option>
                                    <option value="10">10 Problems</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Difficulty</label>
                                <select id="practice-difficulty" class="w-full px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary dark:bg-white/5 dark:text-white">
                                    <option value="easy">Easy</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                        </div>

                        <button onclick="generatePractice()" class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition font-medium">
                            Generate Problems
                        </button>
                    </div>

                    <div id="practice-result" class="mt-6 hidden">
                        <div class="dark:bg-white/5 bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto dark:border dark:border-white/[0.06]">
                            <div id="practice-content" class="prose prose-sm dark:prose-invert max-w-none"></div>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Concept Explainer -->
                <div id="content-concept" class="tab-content hidden dark:bg-white/[0.02] bg-white rounded-lg shadow-md dark:shadow-none dark:border dark:border-white/[0.06] p-6">
                    <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">Concept Explainer</h2>
                    <p class="dark:text-gray-400 text-gray-600 mb-6">Get detailed explanations with examples, visual descriptions, and memory tricks</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Concept *</label>
                            <input type="text" id="concept-name" class="w-full px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary dark:bg-white/5 dark:text-white dark:placeholder-gray-500" placeholder="e.g., Photosynthesis, Newton's Laws">
                        </div>

                        <div>
                            <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Understanding Level</label>
                            <select id="concept-level" class="w-full px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary dark:bg-white/5 dark:text-white">
                                <option value="beginner">Beginner (Simple explanation)</option>
                                <option value="intermediate" selected>Intermediate (Detailed)</option>
                                <option value="advanced">Advanced (In-depth)</option>
                            </select>
                        </div>

                        <button onclick="explainConcept()" class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition font-medium">
                            Explain Concept
                        </button>
                    </div>

                    <div id="concept-result" class="mt-6 hidden">
                        <div class="dark:bg-white/5 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6 max-h-96 overflow-y-auto dark:border dark:border-white/[0.06]">
                            <div id="concept-content" class="prose prose-sm dark:prose-invert max-w-none"></div>
                        </div>
                    </div>
                </div>

                <!-- Tab 5: Hint System -->
                <div id="content-hint" class="tab-content hidden dark:bg-white/[0.02] bg-white rounded-lg shadow-md dark:shadow-none dark:border dark:border-white/[0.06] p-6">
                    <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">Progressive Hints</h2>
                    <p class="dark:text-gray-400 text-gray-600 mb-6">Get step-by-step hints to solve problems on your own</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Problem Statement *</label>
                            <textarea id="hint-problem" rows="3" class="w-full px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary dark:bg-white/5 dark:text-white dark:placeholder-gray-500" placeholder="e.g., Find the roots of x² + 5x + 6 = 0"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Hint Level</label>
                            <div class="flex items-center gap-4">
                                <input type="range" id="hint-level" min="0" max="5" value="0" class="flex-1" oninput="updateHintLevel(this.value)">
                                <span id="hint-level-text" class="text-sm font-medium text-primary min-w-32">Level 0: Gentle Nudge</span>
                            </div>
                            <p class="text-xs dark:text-gray-500 text-gray-500 mt-2">Move slider: 0=Gentle hint → 5=Full solution</p>
                        </div>

                        <button onclick="getHint()" class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition font-medium">
                            Get Hint
                        </button>
                    </div>

                    <div id="hint-result" class="mt-6 hidden">
                        <div class="dark:bg-yellow-500/5 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-6">
                            <div id="hint-content" class="prose prose-sm dark:prose-invert max-w-none"></div>
                        </div>
                    </div>
                </div>

                <!-- Tab 6: Topic Summary -->
                <div id="content-summary" class="tab-content hidden dark:bg-white/[0.02] bg-white rounded-lg shadow-md dark:shadow-none dark:border dark:border-white/[0.06] p-6">
                    <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">Quick Topic Summary</h2>
                    <p class="dark:text-gray-400 text-gray-600 mb-6">Get concise revision notes for exam preparation</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium dark:text-gray-300 text-gray-700 mb-2">Topic *</label>
                            <input type="text" id="summary-topic" class="w-full px-4 py-2 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary dark:bg-white/5 dark:text-white dark:placeholder-gray-500" placeholder="e.g., Newton's Laws of Motion">
                        </div>

                        <button onclick="getSummary()" class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition font-medium">
                            Get Summary
                        </button>
                    </div>

                    <div id="summary-result" class="mt-6 hidden">
                        <div class="dark:bg-white/5 bg-gradient-to-br from-green-50 to-teal-50 rounded-lg p-6 max-h-96 overflow-y-auto dark:border dark:border-white/[0.06]">
                            <div id="summary-content" class="prose prose-sm dark:prose-invert max-w-none"></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Features Panel (Right - 1/3) -->
            <div class="space-y-4">

                <!-- All Features -->
                <div class="dark:bg-white/[0.02] bg-white rounded-lg shadow-md dark:shadow-none dark:border dark:border-white/[0.06] p-6">
                    <h3 class="text-lg font-bold dark:text-white text-gray-900 mb-4">All Features</h3>
                    <ul class="space-y-3 text-sm dark:text-gray-400 text-gray-600">
                        <li class="flex items-start gap-2">
                            <span class="text-green-500">✓</span>
                            <span><strong class="dark:text-gray-200">Doubt Solver:</strong> Text & image analysis</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-500">✓</span>
                            <span><strong class="dark:text-gray-200">Quiz Generator:</strong> MCQ with answers</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-500">✓</span>
                            <span><strong class="dark:text-gray-200">Practice:</strong> Problems with hints</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-500">✓</span>
                            <span><strong class="dark:text-gray-200">Concepts:</strong> Detailed explanations</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-500">✓</span>
                            <span><strong class="dark:text-gray-200">Hints:</strong> Progressive guidance</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-500">✓</span>
                            <span><strong class="dark:text-gray-200">Summary:</strong> Quick revision notes</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary">⚡</span>
                            <span>Hindi/Hinglish support</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary">⚡</span>
                            <span>24/7 AI-powered learning</span>
                        </li>
                    </ul>
                </div>

                <!-- Tips -->
                <div class="dark:bg-white/5 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg shadow-md dark:shadow-none dark:border dark:border-white/[0.06] p-6">
                    <h3 class="text-lg font-bold dark:text-white text-gray-900 mb-4">Tips</h3>
                    <ul class="space-y-2 text-sm dark:text-gray-400 text-gray-700">
                        <li>• Be specific with your questions</li>
                        <li>• Upload clear images for better results</li>
                        <li>• Ask follow-up questions for clarity</li>
                        <li>• Set the subject for better context</li>
                    </ul>
                </div>

                <!-- Stats -->
                <div class="dark:bg-white/[0.02] bg-white rounded-lg shadow-md dark:shadow-none dark:border dark:border-white/[0.06] p-6">
                    <h3 class="text-lg font-bold dark:text-white text-gray-900 mb-4">Session Stats</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="dark:text-gray-400 text-gray-600">Doubts Solved:</span>
                            <span id="doubts-count" class="font-semibold text-primary">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="dark:text-gray-400 text-gray-600">Subject:</span>
                            <span id="stats-subject" class="font-semibold text-primary">None</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="dark:bg-[#0f1318] bg-white rounded-lg p-6 flex items-center gap-4 dark:border dark:border-white/10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        <span class="dark:text-gray-300 text-gray-700 font-medium">Solving your doubt...</span>
    </div>
</div>

@endsection

@push('scripts')
<style>
    .tab-btn {
        color: #6B7280;
        background-color: transparent;
    }
    .dark .tab-btn {
        color: #9CA3AF;
    }
    .tab-btn:hover {
        background-color: #F3F4F6;
    }
    .dark .tab-btn:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    .tab-btn.active {
        color: var(--color-primary, #4F46E5);
        background-color: #EEF2FF;
        font-weight: 600;
    }
    .dark .tab-btn.active {
        background-color: rgba(99, 102, 241, 0.1);
    }
</style>
<script>
let doubtCount = 0;

// Tab switching
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('content-' + tabName).classList.remove('hidden');
    document.getElementById('tab-' + tabName).classList.add('active');
}

// Set subject
function setSubject(subject) {
    fetch('{{ route("doubt.set.subject") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ subject: subject })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('current-subject').textContent = subject;
            document.getElementById('stats-subject').textContent = subject;
            showNotification('Subject set to: ' + subject, 'success');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Send text doubt
function sendTextDoubt() {
    const input = document.getElementById('doubt-input');
    const question = input.value.trim();

    if (!question) {
        showNotification('Please enter your doubt', 'warning');
        return;
    }

    addMessage(question, 'user');
    input.value = '';
    showLoading(true);

    fetch('{{ route("doubt.solve.text") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ question: question })
    })
    .then(response => response.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            addMessage(data.response, 'ai');
            doubtCount++;
            document.getElementById('doubts-count').textContent = doubtCount;
        } else {
            showNotification('Error solving doubt', 'error');
        }
    })
    .catch(error => {
        showLoading(false);
        console.error('Error:', error);
        showNotification('Error occurred', 'error');
    });
}

// Handle image upload
function handleImageUpload() {
    const input = document.getElementById('image-input');
    const file = input.files[0];

    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
        showNotification('Image size should be less than 10MB', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('image', file);

    showLoading(true);
    addMessage('Analyzing uploaded image...', 'user');

    fetch('{{ route("doubt.solve.image") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            addMessage(data.response, 'ai');
            doubtCount++;
            document.getElementById('doubts-count').textContent = doubtCount;
        } else {
            showNotification('Error analyzing image', 'error');
        }
    })
    .catch(error => {
        showLoading(false);
        console.error('Error:', error);
        showNotification('Error occurred', 'error');
    })
    .finally(() => {
        input.value = '';
    });
}

// Clear chat history
function clearHistory() {
    if (!confirm('Are you sure you want to clear the chat history?')) return;

    fetch('{{ route("doubt.clear.history") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('chat-messages').innerHTML = `
                <div class="text-center dark:text-gray-500 text-gray-500 py-12">
                    <div class="text-6xl mb-4">🎓</div>
                    <p class="text-lg font-medium">Chat cleared! Ask a new doubt.</p>
                </div>
            `;
            doubtCount = 0;
            document.getElementById('doubts-count').textContent = '0';
            showNotification('Chat cleared', 'success');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Add message to chat
function addMessage(text, sender) {
    const chatMessages = document.getElementById('chat-messages');

    if (chatMessages.querySelector('.text-center')) {
        chatMessages.innerHTML = '';
    }

    const messageDiv = document.createElement('div');
    messageDiv.className = sender === 'user'
        ? 'flex justify-end'
        : 'flex justify-start';

    const bubble = document.createElement('div');
    bubble.className = sender === 'user'
        ? 'max-w-[80%] px-4 py-3 bg-primary text-white rounded-lg rounded-tr-none'
        : 'max-w-[80%] px-4 py-3 dark:bg-white/5 bg-gray-100 dark:text-gray-300 text-gray-800 rounded-lg rounded-tl-none prose prose-sm dark:prose-invert';

    let formattedText = text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/```(.*?)```/gs, '<pre class="bg-gray-800 text-white p-2 rounded my-2 overflow-x-auto"><code>$1</code></pre>')
        .replace(/\n/g, '<br>');

    bubble.innerHTML = formattedText;
    messageDiv.appendChild(bubble);
    chatMessages.appendChild(messageDiv);

    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Show loading overlay
function showLoading(show) {
    document.getElementById('loading-overlay').classList.toggle('hidden', !show);
}

// Show notification
function showNotification(message, type) {
    alert(message);
}

// Set quiz mode
function setQuizMode(mode) {
    const topicBtn = document.getElementById('quiz-mode-topic');
    const imageBtn = document.getElementById('quiz-mode-image');
    const topicForm = document.getElementById('quiz-form-topic');
    const imageForm = document.getElementById('quiz-form-image');

    if (mode === 'topic') {
        topicBtn.className = 'flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition';
        imageBtn.className = 'flex-1 px-4 py-2 dark:bg-white/5 bg-gray-200 dark:text-gray-300 text-gray-700 rounded-lg font-medium transition';
        topicForm.classList.remove('hidden');
        imageForm.classList.add('hidden');
    } else {
        imageBtn.className = 'flex-1 px-4 py-2 bg-primary text-white rounded-lg font-medium transition';
        topicBtn.className = 'flex-1 px-4 py-2 dark:bg-white/5 bg-gray-200 dark:text-gray-300 text-gray-700 rounded-lg font-medium transition';
        imageForm.classList.remove('hidden');
        topicForm.classList.add('hidden');
    }

    document.getElementById('quiz-result').classList.add('hidden');
}

// Preview quiz image
function previewQuizImage(input) {
    const previewContainer = document.getElementById('quiz-image-preview');
    const previewImg = document.getElementById('quiz-image-preview-img');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewContainer.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        previewContainer.classList.add('hidden');
    }
}

// Enter key to send
document.getElementById('doubt-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendTextDoubt();
    }
});

function generateQuiz() {
    const topic = document.getElementById('quiz-topic').value.trim();
    const numQuestions = document.getElementById('quiz-num').value;
    const difficulty = document.getElementById('quiz-difficulty').value;

    if (!topic) { showNotification('Please enter a topic', 'warning'); return; }

    showLoading(true);
    document.getElementById('quiz-result').classList.add('hidden');

    fetch('{{ route("doubt.generate.quiz") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ topic, num_questions: numQuestions, difficulty })
    })
    .then(r => r.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            displayFormattedContent('quiz-content', data.quiz);
            document.getElementById('quiz-result').classList.remove('hidden');
        } else { showNotification('Error generating quiz', 'error'); }
    })
    .catch(e => { showLoading(false); console.error(e); showNotification('Error occurred', 'error'); });
}

function generateQuizFromImage() {
    const imageInput = document.getElementById('quiz-image');
    const numQuestions = document.getElementById('quiz-num-img').value;
    const difficulty = document.getElementById('quiz-difficulty-img').value;

    if (!imageInput.files || !imageInput.files[0]) { showNotification('Please select an image', 'warning'); return; }

    showLoading(true);
    document.getElementById('quiz-result').classList.add('hidden');

    const formData = new FormData();
    formData.append('image', imageInput.files[0]);
    formData.append('num_questions', numQuestions);
    formData.append('difficulty', difficulty);

    fetch('{{ route("doubt.generate.quiz") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            displayFormattedContent('quiz-content', data.quiz);
            document.getElementById('quiz-result').classList.remove('hidden');
        } else { showNotification('Error generating quiz from image', 'error'); }
    })
    .catch(e => { showLoading(false); console.error(e); showNotification('Error occurred', 'error'); });
}

function generatePractice() {
    const topic = document.getElementById('practice-topic').value.trim();
    const numProblems = document.getElementById('practice-num').value;
    const difficulty = document.getElementById('practice-difficulty').value;

    if (!topic) { showNotification('Please enter a topic', 'warning'); return; }

    showLoading(true);
    document.getElementById('practice-result').classList.add('hidden');

    fetch('{{ route("doubt.generate.practice") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ topic, num_problems: numProblems, difficulty })
    })
    .then(r => r.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            displayFormattedContent('practice-content', data.problems);
            document.getElementById('practice-result').classList.remove('hidden');
        } else { showNotification('Error generating practice problems', 'error'); }
    })
    .catch(e => { showLoading(false); console.error(e); showNotification('Error occurred', 'error'); });
}

function explainConcept() {
    const concept = document.getElementById('concept-name').value.trim();
    const level = document.getElementById('concept-level').value;

    if (!concept) { showNotification('Please enter a concept', 'warning'); return; }

    showLoading(true);
    document.getElementById('concept-result').classList.add('hidden');

    fetch('{{ route("doubt.explain.concept") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ concept, level })
    })
    .then(r => r.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            displayFormattedContent('concept-content', data.explanation);
            document.getElementById('concept-result').classList.remove('hidden');
        } else { showNotification('Error explaining concept', 'error'); }
    })
    .catch(e => { showLoading(false); console.error(e); showNotification('Error occurred', 'error'); });
}

function getHint() {
    const problem = document.getElementById('hint-problem').value.trim();
    const step = document.getElementById('hint-level').value;

    if (!problem) { showNotification('Please enter a problem', 'warning'); return; }

    showLoading(true);
    document.getElementById('hint-result').classList.add('hidden');

    fetch('{{ route("doubt.get.hint") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ problem, step })
    })
    .then(r => r.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            displayFormattedContent('hint-content', data.hint);
            document.getElementById('hint-result').classList.remove('hidden');
        } else { showNotification('Error getting hint', 'error'); }
    })
    .catch(e => { showLoading(false); console.error(e); showNotification('Error occurred', 'error'); });
}

function getSummary() {
    const topic = document.getElementById('summary-topic').value.trim();

    if (!topic) { showNotification('Please enter a topic', 'warning'); return; }

    showLoading(true);
    document.getElementById('summary-result').classList.add('hidden');

    fetch('{{ route("doubt.summarize.topic") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ topic })
    })
    .then(r => r.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            displayFormattedContent('summary-content', data.summary);
            document.getElementById('summary-result').classList.remove('hidden');
        } else { showNotification('Error generating summary', 'error'); }
    })
    .catch(e => { showLoading(false); console.error(e); showNotification('Error occurred', 'error'); });
}

function updateHintLevel(value) {
    const labels = [
        'Level 0: Gentle Nudge',
        'Level 1: First Step',
        'Level 2: Formula/Approach',
        'Level 3: Detailed Guidance',
        'Level 4: Near Solution',
        'Level 5: Full Solution'
    ];
    document.getElementById('hint-level-text').textContent = labels[value];
}

function displayFormattedContent(elementId, content) {
    const element = document.getElementById(elementId);
    let formattedText = content
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/```(.*?)```/gs, '<pre class="bg-gray-800 text-white p-2 rounded my-2 overflow-x-auto"><code>$1</code></pre>')
        .replace(/\n\n/g, '</p><p class="mt-2">')
        .replace(/\n/g, '<br>');
    element.innerHTML = '<p>' + formattedText + '</p>';
}
</script>
@endpush
