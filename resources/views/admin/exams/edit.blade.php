<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exam: {{ $exam->name }} | Admin</title>
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
                    <a href="{{ route('admin.exams.index') }}" class="text-gray-500 hover:text-white text-xs mb-1 inline-flex items-center gap-1">
                        <span class="material-icons-outlined text-sm">arrow_back</span> Back to Exams
                    </a>
                    <h1 class="text-base font-semibold text-white">Edit Exam: {{ $exam->name }}</h1>
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

                <form action="{{ route('admin.exams.update', $exam) }}" method="POST" class="max-w-4xl">
                    @csrf
                    @method('PUT')
                    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Exam Name *</label>
                                <input type="text" name="name" value="{{ old('name', $exam->name) }}" required
                                       class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Category *</label>
                                <select name="category" required
                                        class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                                    @foreach(['engineering', 'medical', 'government', 'board', 'other'] as $cat)
                                    <option value="{{ $cat }}" {{ old('category', $exam->category) === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Description</label>
                            <textarea name="description" rows="2"
                                      class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">{{ old('description', $exam->description) }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Level</label>
                                <select name="level"
                                        class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                                    <option value="">Select level</option>
                                    @foreach(['national', 'state', 'board'] as $lvl)
                                    <option value="{{ $lvl }}" {{ old('level', $exam->level) === $lvl ? 'selected' : '' }}>{{ ucfirst($lvl) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Display Order</label>
                                <input type="number" name="order" value="{{ old('order', $exam->order) }}" min="0"
                                       class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                        </div>

                        <!-- Subjects -->
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Subjects</label>
                            <div id="subjects-container" class="space-y-2">
                                @php $subjectsList = old('subjects', $exam->subjects ?? ['']); @endphp
                                @foreach($subjectsList as $subject)
                                <div class="flex gap-2 subject-item">
                                    <input type="text" name="subjects[]" value="{{ $subject }}"
                                           class="flex-1 px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition"
                                           placeholder="e.g., Physics">
                                    <button type="button" onclick="removeSubject(this)"
                                            class="px-3 py-2 bg-red-500/10 text-red-400 rounded-lg hover:bg-red-500/20 transition-colors">
                                        <span class="material-icons-outlined text-sm">delete</span>
                                    </button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" onclick="addSubject()"
                                    class="mt-2 inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500/10 text-blue-400 rounded-lg hover:bg-blue-500/20 transition-colors text-sm">
                                <span class="material-icons-outlined text-sm">add</span> Add Subject
                            </button>
                        </div>

                        <!-- Exam Config -->
                        <div>
                            <h3 class="text-sm font-medium text-white mb-3">Exam Configuration</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-[10px] text-gray-500 mb-1">Duration (min)</label>
                                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $exam->config['duration_minutes'] ?? 60) }}" min="1"
                                           class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500 mb-1">Total Questions</label>
                                    <input type="number" name="total_questions" value="{{ old('total_questions', $exam->config['total_questions'] ?? 50) }}" min="1"
                                           class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500 mb-1">Correct Marks</label>
                                    <input type="number" name="correct_marks" value="{{ old('correct_marks', $exam->config['marking_scheme']['correct'] ?? 4) }}" step="0.5"
                                           class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500 mb-1">Wrong Marks</label>
                                    <input type="number" name="wrong_marks" value="{{ old('wrong_marks', $exam->config['marking_scheme']['wrong'] ?? -1) }}" step="0.5"
                                           class="w-full px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $exam->is_active) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-sm text-gray-300">Active</span>
                            </label>
                        </div>

                        <!-- Quick Link -->
                        <div class="p-3 bg-blue-500/5 border border-blue-500/10 rounded-lg">
                            <a href="{{ route('admin.exam-questions.index', $exam) }}" class="text-blue-400 hover:underline text-sm flex items-center gap-2">
                                <span class="material-icons-outlined text-sm">list</span>
                                Manage Questions for this Exam ({{ $exam->questions()->count() }} questions)
                            </a>
                        </div>

                        <div class="flex items-center gap-3 pt-4 border-t border-gray-800">
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <span class="material-icons-outlined text-sm">save</span> Update Exam
                            </button>
                            <a href="{{ route('admin.exams.index') }}"
                               class="px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-medium rounded-lg transition-colors">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function addSubject() {
            const container = document.getElementById('subjects-container');
            const item = document.createElement('div');
            item.className = 'flex gap-2 subject-item';
            item.innerHTML = `
                <input type="text" name="subjects[]"
                       class="flex-1 px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition"
                       placeholder="e.g., Chemistry">
                <button type="button" onclick="removeSubject(this)"
                        class="px-3 py-2 bg-red-500/10 text-red-400 rounded-lg hover:bg-red-500/20 transition-colors">
                    <span class="material-icons-outlined text-sm">delete</span>
                </button>
            `;
            container.appendChild(item);
        }

        function removeSubject(btn) {
            const container = document.getElementById('subjects-container');
            if (container.querySelectorAll('.subject-item').length > 1) {
                btn.closest('.subject-item').remove();
            }
        }
    </script>
</body>
</html>
