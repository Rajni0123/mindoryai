<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions: {{ $exam->name }} | Admin</title>
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
                <div class="flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.exams.index') }}" class="text-gray-500 hover:text-white text-xs mb-1 inline-flex items-center gap-1">
                            <span class="material-icons-outlined text-sm">arrow_back</span> Back to Exams
                        </a>
                        <h1 class="text-base font-semibold text-white">{{ $exam->name }} - Questions</h1>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $questions->total() }} total questions</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Import -->
                        <form action="{{ route('admin.exam-questions.import', $exam) }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                            @csrf
                            <label class="px-3 py-2 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-medium rounded-lg transition-colors cursor-pointer flex items-center gap-1">
                                <span class="material-icons-outlined text-sm">upload_file</span> Import JSON
                                <input type="file" name="file" accept=".json" class="hidden" onchange="this.form.submit()">
                            </label>
                        </form>
                        <a href="{{ route('admin.exam-questions.create', $exam) }}"
                           class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                            <span class="material-icons-outlined text-sm">add</span> Add Question
                        </a>
                    </div>
                </div>
            </header>

            <div class="p-6">
                @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/20 rounded-lg text-green-300 text-sm">{{ session('success') }}</div>
                @endif
                @if(session('warning'))
                <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/20 rounded-lg text-yellow-300 text-sm">{{ session('warning') }}</div>
                @endif

                <!-- Filters -->
                <form method="GET" class="mb-4 flex items-center gap-3">
                    <select name="subject" class="px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                        <option value="">All Subjects</option>
                        @foreach($subjects as $subject)
                        <option value="{{ $subject }}" {{ request('subject') === $subject ? 'selected' : '' }}>{{ $subject }}</option>
                        @endforeach
                    </select>
                    <select name="difficulty" class="px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                        <option value="">All Difficulties</option>
                        @foreach(['easy', 'medium', 'hard'] as $d)
                        <option value="{{ $d }}" {{ request('difficulty') === $d ? 'selected' : '' }}>{{ ucfirst($d) }}</option>
                        @endforeach
                    </select>
                    <select name="year" class="px-3 py-2 rounded-lg bg-gray-800/50 border border-gray-700 text-white text-sm focus:border-blue-500 outline-none transition">
                        <option value="">All Years</option>
                        @foreach($years as $year)
                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 bg-blue-500/10 text-blue-400 rounded-lg hover:bg-blue-500/20 transition-colors text-sm">Filter</button>
                    <a href="{{ route('admin.exam-questions.index', $exam) }}" class="text-xs text-gray-500 hover:text-gray-300">Clear</a>
                </form>

                <div class="bg-gray-900/50 border border-gray-800 rounded-xl overflow-hidden">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-800">
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Question</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Subject</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Year</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Difficulty</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Answer</th>
                                <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($questions as $question)
                            <tr class="border-b border-gray-800/50 hover:bg-white/[0.02] transition-colors">
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $question->id }}</td>
                                <td class="px-4 py-3 text-sm text-gray-300 max-w-md">
                                    <div class="truncate" title="{{ $question->question_text }}">{{ Str::limit($question->question_text, 80) }}</div>
                                    @if($question->topic)
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $question->topic }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-400">{{ $question->subject }}</td>
                                <td class="px-4 py-3 text-sm text-gray-400">{{ $question->year ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-xs rounded-full
                                        {{ $question->difficulty === 'easy' ? 'bg-green-500/10 text-green-400' : '' }}
                                        {{ $question->difficulty === 'medium' ? 'bg-yellow-500/10 text-yellow-400' : '' }}
                                        {{ $question->difficulty === 'hard' ? 'bg-red-500/10 text-red-400' : '' }}
                                    ">{{ ucfirst($question->difficulty) }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-white font-medium">{{ $question->correct_answer }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.exam-questions.edit', $question) }}"
                                           class="p-1.5 rounded-lg hover:bg-white/5 text-gray-400 hover:text-white transition-colors">
                                            <span class="material-icons-outlined text-sm">edit</span>
                                        </a>
                                        <form action="{{ route('admin.exam-questions.destroy', $question) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Delete this question?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-colors">
                                                <span class="material-icons-outlined text-sm">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 text-sm">No questions found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $questions->withQueryString()->links() }}</div>
            </div>
        </main>
    </div>
</body>
</html>
