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
                        <p class="text-xs text-gray-500 mt-0.5">Add a new quiz challenge for students</p>
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
                                <input type="date" name="challenge_date" value="{{ old('challenge_date', now()->format('Y-m-d')) }}" required
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Title *</label>
                                <input type="text" name="title" value="{{ old('title') }}" required placeholder="Math Monday Challenge"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Subject *</label>
                                <select name="subject" required
                                        class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject }}">{{ $subject }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Difficulty *</label>
                                <select name="difficulty" required
                                        class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                    <option value="mixed" selected>Mixed</option>
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

                    {{-- Questions --}}
                    <div class="bg-white/[0.02] border border-white/5 rounded-lg p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-green-400 flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 16px;">quiz</span>
                                Questions (<span x-text="questions.length"></span>)
                            </h3>
                            <button type="button" @click="addQuestion()"
                                    class="px-3 py-1.5 bg-green-500/20 hover:bg-green-500/30 text-green-400 text-xs font-medium rounded-lg transition-colors flex items-center gap-1">
                                <span class="material-icons-outlined" style="font-size: 14px;">add</span>
                                Add Question
                            </button>
                        </div>

                        <template x-for="(q, qIndex) in questions" :key="qIndex">
                            <div class="bg-white/[0.02] border border-white/5 rounded-lg p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-400">Question <span x-text="qIndex + 1"></span></span>
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
                                                   :checked="q.correct_answer === oIndex"
                                                   @change="q.correct_answer = oIndex"
                                                   class="w-4 h-4 text-green-500 bg-white/5 border-white/20 focus:ring-green-500">
                                            <input type="text" x-model="q.options[oIndex]"
                                                   class="flex-1 px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500"
                                                   :placeholder="'Option ' + (oIndex + 1)">
                                        </div>
                                    </template>
                                </div>
                                <p class="text-[10px] text-gray-500">Select the radio next to the correct answer</p>
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
                        <button type="submit" class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
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
            questions: [
                { question: '', options: ['', '', '', ''], correct_answer: 0, explanation: '' },
                { question: '', options: ['', '', '', ''], correct_answer: 0, explanation: '' },
                { question: '', options: ['', '', '', ''], correct_answer: 0, explanation: '' },
                { question: '', options: ['', '', '', ''], correct_answer: 0, explanation: '' },
                { question: '', options: ['', '', '', ''], correct_answer: 0, explanation: '' },
            ],
            addQuestion() {
                this.questions.push({ question: '', options: ['', '', '', ''], correct_answer: 0, explanation: '' });
            },
            removeQuestion(index) {
                if (this.questions.length > 3) this.questions.splice(index, 1);
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
