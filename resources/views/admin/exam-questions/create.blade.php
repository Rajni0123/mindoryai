<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question: {{ $exam->name }} | Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
                <div>
                    <a href="{{ route('admin.exam-questions.index', $exam) }}" class="text-gray-500 hover:text-white text-xs mb-1 inline-flex items-center gap-1">
                        <span class="material-icons-outlined text-sm">arrow_back</span> Back to Questions
                    </a>
                    <h1 class="text-base font-semibold text-white">Add Question - {{ $exam->name }}</h1>
                </div>
            </header>

            <div class="p-6">
                @if($errors->any())
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg">
                    <ul class="list-disc list-inside text-red-300 text-sm">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('admin.exam-questions.store', $exam) }}" method="POST" class="max-w-4xl">
                    @csrf
                    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 space-y-6">
                        <!-- Meta -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Subject *</label>
                                <input type="text" name="subject" value="{{ old('subject') }}" required
                                       class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition"
                                       placeholder="Physics">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Topic</label>
                                <input type="text" name="topic" value="{{ old('topic') }}"
                                       class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition"
                                       placeholder="Mechanics">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Year</label>
                                <input type="number" name="year" value="{{ old('year') }}" min="1990" max="2030"
                                       class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition"
                                       placeholder="2024">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Difficulty *</label>
                                <select name="difficulty" required
                                        class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                                    <option value="easy" {{ old('difficulty') === 'easy' ? 'selected' : '' }}>Easy</option>
                                    <option value="medium" {{ old('difficulty', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="hard" {{ old('difficulty') === 'hard' ? 'selected' : '' }}>Hard</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Question Type *</label>
                                <select name="type" required id="question-type"
                                        class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                                    <option value="mcq" {{ old('type', 'mcq') === 'mcq' ? 'selected' : '' }}>MCQ</option>
                                    <option value="numerical" {{ old('type') === 'numerical' ? 'selected' : '' }}>Numerical</option>
                                    <option value="assertion_reason" {{ old('type') === 'assertion_reason' ? 'selected' : '' }}>Assertion & Reason</option>
                                    <option value="true_false" {{ old('type') === 'true_false' ? 'selected' : '' }}>True/False</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Language</label>
                                <input type="text" name="language" value="{{ old('language', 'english') }}"
                                       class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                        </div>

                        <!-- Question -->
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Question Text *</label>
                            <textarea name="question_text" rows="4" required
                                      class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition"
                                      placeholder="Enter the question text...">{{ old('question_text') }}</textarea>
                        </div>

                        <!-- Options -->
                        <div id="options-section">
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Options</label>
                            <div id="options-container" class="space-y-2">
                                @foreach(['A', 'B', 'C', 'D'] as $label)
                                <div class="flex gap-2 items-center">
                                    <span class="text-sm font-medium text-white w-6">{{ $label }}.</span>
                                    <input type="hidden" name="options[{{ $loop->index }}][label]" value="{{ $label }}">
                                    <input type="text" name="options[{{ $loop->index }}][text]" value="{{ old("options.{$loop->index}.text") }}"
                                           class="flex-1 px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition"
                                           placeholder="Option {{ $label }}">
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Correct Answer -->
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Correct Answer *</label>
                            <input type="text" name="correct_answer" value="{{ old('correct_answer') }}" required
                                   class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition"
                                   placeholder="A, B, C, D or numeric value">
                        </div>

                        <!-- Explanation -->
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Explanation</label>
                            <textarea name="explanation" rows="3"
                                      class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition"
                                      placeholder="Explain the correct answer...">{{ old('explanation') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Solution Steps</label>
                            <textarea name="solution_steps" rows="3"
                                      class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition"
                                      placeholder="Step-by-step solution...">{{ old('solution_steps') }}</textarea>
                        </div>

                        <!-- Tags -->
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Tags (comma-separated)</label>
                            <input type="text" name="tags" value="{{ old('tags') }}"
                                   class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition"
                                   placeholder="pyq, 2024, shift-1">
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-sm text-gray-300">Active</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-3 pt-4 border-t border-gray-800">
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <span class="material-icons-outlined text-sm">save</span> Add Question
                            </button>
                            <a href="{{ route('admin.exam-questions.index', $exam) }}"
                               class="px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-medium rounded-lg transition-colors">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
