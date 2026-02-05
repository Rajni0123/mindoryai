<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Daily Challenge - Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        .shimmer { background: linear-gradient(90deg, transparent 25%, rgba(59,130,246,0.1) 50%, transparent 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        @include('admin.partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.daily-challenges.index') }}" class="p-1.5 rounded hover:bg-white/10 text-gray-400 hover:text-white transition-colors">
                        <span class="material-icons-outlined" style="font-size: 20px;">arrow_back</span>
                    </a>
                    <div>
                        <h1 class="text-base font-semibold text-white">Create Daily Challenge</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Add questions manually or generate with AI</p>
                    </div>
                </div>
            </header>

            <div class="p-6" x-data="challengeForm()">
                @if(session('error'))
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-red-400" style="font-size: 16px;">error</span>
                    <p class="text-red-300">{{ session('error') }}</p>
                </div>
                @endif

                @if($errors->any())
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-sm">
                    <ul class="list-disc list-inside text-red-300">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('admin.daily-challenges.store') }}" method="POST" @submit="prepareSubmit" class="space-y-6">
                    @csrf

                    {{-- Basic Info --}}
                    <div class="bg-white/[0.02] border border-white/5 rounded-lg p-5 space-y-4">
                        <h3 class="text-sm font-semibold text-blue-400 flex items-center gap-2">
                            <span class="material-icons-outlined" style="font-size: 16px;">info</span>
                            Challenge Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Challenge Date *</label>
                                <input type="date" name="challenge_date" x-model="challengeDate" required
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Title *</label>
                                <input type="text" name="title" x-model="title" required placeholder="Math Monday Challenge"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Subject *</label>
                                <select name="subject" x-model="subject" required
                                        class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject }}">{{ $subject }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Difficulty *</label>
                                <select name="difficulty" x-model="difficulty" required
                                        class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                    <option value="mixed">Mixed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Time Limit (seconds)</label>
                                <input type="number" name="time_limit_seconds" value="{{ old('time_limit_seconds', 300) }}" min="60" max="1800"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Reward Credits</label>
                                <input type="number" name="reward_credits" value="{{ old('reward_credits', 2) }}" min="0" max="50"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    {{-- AI Generation Panel --}}
                    <div class="bg-gradient-to-r from-purple-500/5 to-blue-500/5 border border-purple-500/20 rounded-lg p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-purple-400 flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 16px;">auto_awesome</span>
                                AI Question Generator
                            </h3>
                            <span class="text-[10px] text-purple-400/60 bg-purple-500/10 px-2 py-0.5 rounded-full">Powered by Gemini</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Topic (optional)</label>
                                <input type="text" x-model="aiTopic"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-purple-500"
                                       placeholder="e.g. Algebra, Photosynthesis, WW2">
                                <p class="mt-1 text-[10px] text-gray-600">Leave blank for general subject questions</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Class Level</label>
                                <select x-model="aiClassLevel"
                                        class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-purple-500">
                                    <option value="Class 6">Class 6</option>
                                    <option value="Class 7">Class 7</option>
                                    <option value="Class 8">Class 8</option>
                                    <option value="Class 9">Class 9</option>
                                    <option value="Class 10" selected>Class 10</option>
                                    <option value="Class 11">Class 11</option>
                                    <option value="Class 12">Class 12</option>
                                    <option value="Competitive Exam">Competitive Exam</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Number of Questions</label>
                                <input type="number" x-model="aiCount" min="3" max="20"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-purple-500">
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="button" @click="generateAI()"
                                    :disabled="aiLoading"
                                    class="px-5 py-2.5 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-all flex items-center gap-2">
                                <template x-if="!aiLoading">
                                    <span class="flex items-center gap-2">
                                        <span class="material-icons-outlined" style="font-size: 16px;">auto_awesome</span>
                                        Generate with AI
                                    </span>
                                </template>
                                <template x-if="aiLoading">
                                    <span class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        Generating...
                                    </span>
                                </template>
                            </button>
                            <span x-show="aiLoading" class="text-xs text-gray-500">This may take 10-15 seconds...</span>
                        </div>

                        {{-- AI Status Messages --}}
                        <div x-show="aiError" x-cloak class="p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-sm text-red-400 flex items-center gap-2">
                            <span class="material-icons-outlined" style="font-size: 16px;">error</span>
                            <span x-text="aiError"></span>
                        </div>
                        <div x-show="aiSuccess" x-cloak class="p-3 bg-green-500/10 border border-green-500/20 rounded-lg text-sm text-green-400 flex items-center gap-2">
                            <span class="material-icons-outlined" style="font-size: 16px;">check_circle</span>
                            <span x-text="aiSuccess"></span>
                        </div>
                    </div>

                    {{-- Loading Shimmer --}}
                    <div x-show="aiLoading" x-cloak class="space-y-3">
                        <div class="bg-white/[0.02] border border-white/5 rounded-lg p-4 shimmer h-32"></div>
                        <div class="bg-white/[0.02] border border-white/5 rounded-lg p-4 shimmer h-32"></div>
                        <div class="bg-white/[0.02] border border-white/5 rounded-lg p-4 shimmer h-32"></div>
                    </div>

                    {{-- Questions Section --}}
                    <div x-show="!aiLoading" class="bg-white/[0.02] border border-white/5 rounded-lg p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-green-400 flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 16px;">quiz</span>
                                Questions (<span x-text="questions.length"></span>)
                            </h3>
                            <button type="button" @click="addQuestion()"
                                    class="px-3 py-1.5 bg-green-500/20 hover:bg-green-500/30 text-green-400 text-xs font-medium rounded-lg transition-colors flex items-center gap-1">
                                <span class="material-icons-outlined" style="font-size: 14px;">add</span>
                                Add Manual Question
                            </button>
                        </div>

                        <template x-for="(q, qIndex) in questions" :key="qIndex">
                            <div class="bg-white/[0.02] border border-white/5 rounded-lg p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-400">
                                        Question <span x-text="qIndex + 1"></span>
                                        <span x-show="q.ai_generated" class="ml-1.5 px-1.5 py-0.5 bg-purple-500/20 text-purple-400 text-[9px] rounded font-medium">AI</span>
                                    </span>
                                    <button type="button" @click="removeQuestion(qIndex)" x-show="questions.length > 3"
                                            class="p-1 rounded hover:bg-red-500/20 text-gray-500 hover:text-red-400 transition-colors">
                                        <span class="material-icons-outlined" style="font-size: 16px;">close</span>
                                    </button>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Question Text *</label>
                                    <textarea x-model="q.question" rows="2"
                                              class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500"
                                              placeholder="What is 2 + 2?"></textarea>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <template x-for="(opt, oIndex) in q.options" :key="oIndex">
                                        <div class="flex items-center gap-2">
                                            <input type="radio" :name="'correct_' + qIndex" :value="oIndex"
                                                   :checked="q.correct_answer == oIndex"
                                                   @change="q.correct_answer = parseInt(oIndex)"
                                                   class="w-4 h-4 text-green-500 bg-white/5 border-white/20 focus:ring-green-500">
                                            <input type="text" x-model="q.options[oIndex]"
                                                   class="flex-1 px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500"
                                                   :class="q.correct_answer == oIndex ? 'border-green-500/30 bg-green-500/5' : ''"
                                                   :placeholder="'Option ' + (oIndex + 1)">
                                        </div>
                                    </template>
                                </div>
                                <p class="text-[10px] text-gray-500">Select the radio next to the correct answer. <span class="text-green-500">Green border = correct</span></p>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Explanation (optional)</label>
                                    <input type="text" x-model="q.explanation"
                                           class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500"
                                           placeholder="The answer is 4 because...">
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.daily-challenges.index') }}" class="px-5 py-2.5 text-gray-400 hover:text-white text-sm font-medium transition-colors">Cancel</a>
                        <button type="submit" :disabled="aiLoading"
                                class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                            <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                            Create Challenge
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
    function challengeForm() {
        return {
            challengeDate: '{{ old("challenge_date", now()->format("Y-m-d")) }}',
            title: '{{ old("title", "") }}',
            subject: '{{ $subjects[0] ?? "Mathematics" }}',
            difficulty: 'mixed',
            aiTopic: '',
            aiClassLevel: 'Class 10',
            aiCount: 5,
            aiLoading: false,
            aiError: '',
            aiSuccess: '',
            questions: [
                { question: '', options: ['', '', '', ''], correct_answer: 0, explanation: '', ai_generated: false },
                { question: '', options: ['', '', '', ''], correct_answer: 0, explanation: '', ai_generated: false },
                { question: '', options: ['', '', '', ''], correct_answer: 0, explanation: '', ai_generated: false },
                { question: '', options: ['', '', '', ''], correct_answer: 0, explanation: '', ai_generated: false },
                { question: '', options: ['', '', '', ''], correct_answer: 0, explanation: '', ai_generated: false },
            ],

            addQuestion() {
                this.questions.push({ question: '', options: ['', '', '', ''], correct_answer: 0, explanation: '', ai_generated: false });
            },
            removeQuestion(index) {
                if (this.questions.length > 3) this.questions.splice(index, 1);
            },

            async generateAI() {
                this.aiLoading = true;
                this.aiError = '';
                this.aiSuccess = '';

                try {
                    const response = await fetch('{{ route("admin.daily-challenges.generate-ai") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            subject: this.subject,
                            difficulty: this.difficulty,
                            count: parseInt(this.aiCount),
                            topic: this.aiTopic,
                            class_level: this.aiClassLevel,
                        })
                    });

                    const data = await response.json();

                    if (data.success && data.questions) {
                        this.questions = data.questions.map(q => ({
                            ...q,
                            correct_answer: parseInt(q.correct_answer),
                            ai_generated: true
                        }));

                        if (data.title_suggestion && !this.title) {
                            this.title = data.title_suggestion;
                        }

                        this.aiSuccess = `Generated ${data.count} questions successfully! Review and edit below before saving.`;
                    } else {
                        this.aiError = data.error || 'Failed to generate questions. Please try again.';
                    }
                } catch (err) {
                    this.aiError = 'Network error: ' + err.message;
                } finally {
                    this.aiLoading = false;
                }
            },

            prepareSubmit(e) {
                const form = e.target;
                form.querySelectorAll('.dq-input').forEach(el => el.remove());
                this.questions.forEach((q, qi) => {
                    const add = (name, value) => {
                        const input = document.createElement('input');
                        input.type = 'hidden'; input.name = name; input.value = value;
                        input.className = 'dq-input'; form.appendChild(input);
                    };
                    add(`questions[${qi}][question]`, q.question);
                    q.options.forEach((opt, oi) => add(`questions[${qi}][options][${oi}]`, opt));
                    add(`questions[${qi}][correct_answer]`, q.correct_answer);
                    add(`questions[${qi}][explanation]`, q.explanation || '');
                });
            }
        };
    }
    </script>
</body>
</html>
