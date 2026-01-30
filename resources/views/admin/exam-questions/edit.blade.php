<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question | Admin</title>
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
                    <h1 class="text-base font-semibold text-white">Edit Question #{{ $question->id }}</h1>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $exam->name }}</p>
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

                <form action="{{ route('admin.exam-questions.update', $question) }}" method="POST" class="max-w-4xl">
                    @csrf
                    @method('PUT')
                    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 space-y-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Subject *</label>
                                <input type="text" name="subject" value="{{ old('subject', $question->subject) }}" required
                                       class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Topic</label>
                                <input type="text" name="topic" value="{{ old('topic', $question->topic) }}"
                                       class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Year</label>
                                <input type="number" name="year" value="{{ old('year', $question->year) }}" min="1990" max="2030"
                                       class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Difficulty *</label>
                                <select name="difficulty" required
                                        class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                                    @foreach(['easy', 'medium', 'hard'] as $d)
                                    <option value="{{ $d }}" {{ old('difficulty', $question->difficulty) === $d ? 'selected' : '' }}>{{ ucfirst($d) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Question Type *</label>
                                <select name="type" required
                                        class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                                    @foreach(['mcq', 'numerical', 'assertion_reason', 'true_false'] as $t)
                                    <option value="{{ $t }}" {{ old('type', $question->type) === $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Language</label>
                                <input type="text" name="language" value="{{ old('language', $question->language) }}"
                                       class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Question Text *</label>
                            <textarea name="question_text" rows="4" required
                                      class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">{{ old('question_text', $question->question_text) }}</textarea>
                        </div>

                        <!-- Options -->
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Options</label>
                            <div class="space-y-2">
                                @php $opts = old('options', $question->options ?? [['label'=>'A','text'=>''],['label'=>'B','text'=>''],['label'=>'C','text'=>''],['label'=>'D','text'=>'']]); @endphp
                                @foreach($opts as $i => $opt)
                                <div class="flex gap-2 items-center">
                                    <span class="text-sm font-medium text-white w-6">{{ $opt['label'] ?? chr(65+$i) }}.</span>
                                    <input type="hidden" name="options[{{ $i }}][label]" value="{{ $opt['label'] ?? chr(65+$i) }}">
                                    <input type="text" name="options[{{ $i }}][text]" value="{{ $opt['text'] ?? '' }}"
                                           class="flex-1 px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Correct Answer *</label>
                            <input type="text" name="correct_answer" value="{{ old('correct_answer', $question->correct_answer) }}" required
                                   class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Explanation</label>
                            <textarea name="explanation" rows="3"
                                      class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">{{ old('explanation', $question->explanation) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Solution Steps</label>
                            <textarea name="solution_steps" rows="3"
                                      class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">{{ old('solution_steps', $question->solution_steps) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Tags (comma-separated)</label>
                            <input type="text" name="tags" value="{{ old('tags', is_array($question->tags) ? implode(', ', $question->tags) : $question->tags) }}"
                                   class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $question->is_active) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-sm text-gray-300">Active</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-3 pt-4 border-t border-gray-800">
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <span class="material-icons-outlined text-sm">save</span> Update Question
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
