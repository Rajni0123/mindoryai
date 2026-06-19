<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Management | Admin</title>
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
                        <h1 class="text-base font-semibold text-white">Exam Management</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Manage exams and previous year questions</p>
                    </div>
                    <a href="{{ route('admin.exams.create') }}"
                       class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                        <span class="material-icons-outlined text-sm">add</span>
                        Add Exam
                    </a>
                </div>
            </header>

            <div class="p-6">
                @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/20 rounded-lg text-green-300 text-sm">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-300 text-sm">
                    {{ session('error') }}
                </div>
                @endif

                <div class="bg-gray-900/50 border border-gray-800 rounded-xl overflow-hidden">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-800">
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Order</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Questions</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($exams as $exam)
                            <tr class="border-b border-gray-800/50 hover:bg-white/[0.02] transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-400">{{ $exam->order }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-white">{{ $exam->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $exam->slug }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        {{ $exam->category === 'engineering' ? 'bg-blue-500/10 text-blue-400' : '' }}
                                        {{ $exam->category === 'medical' ? 'bg-green-500/10 text-green-400' : '' }}
                                        {{ $exam->category === 'government' ? 'bg-yellow-500/10 text-yellow-400' : '' }}
                                        {{ $exam->category === 'board' ? 'bg-purple-500/10 text-purple-400' : '' }}
                                        {{ $exam->category === 'other' ? 'bg-gray-500/10 text-gray-400' : '' }}
                                    ">{{ ucfirst($exam->category) }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-400">
                                    <a href="{{ route('admin.exam-questions.index', $exam) }}" class="text-blue-400 hover:underline">
                                        {{ $exam->questions_count ?? 0 }} questions
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    @if($exam->is_active)
                                    <span class="px-2 py-1 text-xs bg-green-500/10 text-green-400 rounded-full">Active</span>
                                    @else
                                    <span class="px-2 py-1 text-xs bg-gray-500/10 text-gray-400 rounded-full">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.exam-questions.index', $exam) }}"
                                           class="p-1.5 rounded-lg hover:bg-white/5 text-gray-400 hover:text-white transition-colors" title="Questions">
                                            <span class="material-icons-outlined text-sm">list</span>
                                        </a>
                                        <a href="{{ route('admin.exams.edit', $exam) }}"
                                           class="p-1.5 rounded-lg hover:bg-white/5 text-gray-400 hover:text-white transition-colors" title="Edit">
                                            <span class="material-icons-outlined text-sm">edit</span>
                                        </a>
                                        <form action="{{ route('admin.exams.destroy', $exam) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Are you sure you want to delete this exam?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-colors" title="Delete">
                                                <span class="material-icons-outlined text-sm">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-sm">
                                    No exams found. Create your first exam.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
