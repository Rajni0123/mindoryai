<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daily Challenges - Admin</title>

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
                        <h1 class="text-base font-semibold text-white flex items-center gap-2">
                            <span class="material-icons-outlined text-yellow-400" style="font-size: 20px;">emoji_events</span>
                            Daily Challenges
                        </h1>
                        <p class="text-xs text-gray-500 mt-0.5">Manage daily quiz challenges for students</p>
                    </div>
                    <a href="{{ route('admin.daily-challenges.create') }}"
                       class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                        <span class="material-icons-outlined" style="font-size: 16px;">add</span>
                        Create Challenge
                    </a>
                </div>
            </header>

            <div class="p-6 space-y-6">
                @if(session('success'))
                <div class="p-3 bg-green-500/10 border border-green-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-green-400" style="font-size: 16px;">check_circle</span>
                    <p class="text-green-300">{{ session('success') }}</p>
                </div>
                @endif
                @if(session('error'))
                <div class="p-3 bg-red-500/10 border border-red-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-red-400" style="font-size: 16px;">error</span>
                    <p class="text-red-300">{{ session('error') }}</p>
                </div>
                @endif

                {{-- Today's Challenge --}}
                <div class="bg-gradient-to-r from-yellow-500/10 to-orange-500/10 border border-yellow-500/20 rounded-lg p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-yellow-400 flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 18px;">today</span>
                                Today's Challenge — {{ now()->format('d M Y') }}
                            </h3>
                            @if($todayChallenge)
                                <p class="text-white mt-2 text-sm font-medium">{{ $todayChallenge->title }}</p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                    <span>{{ $todayChallenge->subject }}</span>
                                    <span>{{ count($todayChallenge->questions) }} Questions</span>
                                    <span>{{ $todayChallenge->getParticipantsCount() }} Participants</span>
                                    <span>Avg: {{ number_format($todayChallenge->getAverageScore(), 1) }}%</span>
                                </div>
                            @else
                                <p class="text-gray-400 mt-2 text-sm">No challenge set for today.
                                    <a href="{{ route('admin.daily-challenges.create') }}" class="text-blue-400 hover:underline">Create one now</a>
                                </p>
                            @endif
                        </div>
                        @if($todayChallenge)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $todayChallenge->is_active ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $todayChallenge->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                {{ $todayChallenge->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Settings --}}
                <div class="bg-white/[0.02] border border-white/5 rounded-lg p-5">
                    <h3 class="text-sm font-semibold text-blue-400 flex items-center gap-2 mb-4">
                        <span class="material-icons-outlined" style="font-size: 16px;">settings</span>
                        Challenge Settings
                    </h3>
                    <form action="{{ route('admin.daily-challenges.update-settings') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="hidden" name="enabled" value="0">
                                    <input type="checkbox" name="enabled" value="1" {{ $settings['enabled'] ? 'checked' : '' }}
                                           class="w-5 h-5 rounded bg-white/5 border-white/20 text-blue-500 focus:ring-blue-500">
                                    <div>
                                        <span class="text-sm text-white font-medium">Enable Daily Challenges</span>
                                        <p class="text-[10px] text-gray-500">Show daily challenge to users in the app</p>
                                    </div>
                                </label>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Questions Per Challenge</label>
                                <input type="number" name="questions_count" value="{{ $settings['questions_count'] }}" min="3" max="20"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Time Limit (seconds)</label>
                                <input type="number" name="time_limit" value="{{ $settings['time_limit'] }}" min="60" max="1800"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Reward Credits</label>
                                <input type="number" name="reward_credits" value="{{ $settings['reward_credits'] }}" min="0" max="50"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">7-Day Streak Bonus</label>
                                <input type="number" name="streak_bonus" value="{{ $settings['streak_bonus'] }}" min="0" max="100"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Default Difficulty</label>
                                <select name="difficulty"
                                        class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500">
                                    <option value="easy" {{ $settings['difficulty'] === 'easy' ? 'selected' : '' }}>Easy</option>
                                    <option value="medium" {{ $settings['difficulty'] === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="hard" {{ $settings['difficulty'] === 'hard' ? 'selected' : '' }}>Hard</option>
                                    <option value="mixed" {{ $settings['difficulty'] === 'mixed' ? 'selected' : '' }}>Mixed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Subjects (comma-separated)</label>
                                <input type="text" name="subjects" value="{{ is_array($settings['subjects']) ? implode(', ', $settings['subjects']) : $settings['subjects'] }}"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500"
                                       placeholder="Mathematics, Science, English">
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="px-5 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Challenges List --}}
                <div class="bg-white/[0.02] border border-white/5 rounded-lg overflow-hidden">
                    <div class="px-5 py-3 border-b border-white/5">
                        <h3 class="text-sm font-semibold text-white">All Challenges</h3>
                    </div>

                    @if($challenges->isEmpty())
                        <div class="p-10 text-center">
                            <span class="material-icons-outlined text-gray-600 mb-3" style="font-size: 48px;">quiz</span>
                            <p class="text-gray-500 text-sm">No challenges created yet</p>
                            <a href="{{ route('admin.daily-challenges.create') }}" class="mt-3 inline-flex items-center gap-1 text-blue-400 text-sm hover:underline">
                                <span class="material-icons-outlined" style="font-size: 14px;">add</span>
                                Create your first challenge
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs text-gray-500 border-b border-white/5">
                                        <th class="px-5 py-3 text-left font-medium">Date</th>
                                        <th class="px-5 py-3 text-left font-medium">Title</th>
                                        <th class="px-5 py-3 text-left font-medium">Subject</th>
                                        <th class="px-5 py-3 text-center font-medium">Questions</th>
                                        <th class="px-5 py-3 text-center font-medium">Participants</th>
                                        <th class="px-5 py-3 text-center font-medium">Avg Score</th>
                                        <th class="px-5 py-3 text-center font-medium">Status</th>
                                        <th class="px-5 py-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach($challenges as $challenge)
                                        <tr class="hover:bg-white/[0.02] transition-colors">
                                            <td class="px-5 py-3">
                                                <span class="text-white font-medium">{{ $challenge->challenge_date->format('d M Y') }}</span>
                                                @if($challenge->challenge_date->isToday())
                                                    <span class="ml-1 px-1.5 py-0.5 bg-yellow-500/20 text-yellow-400 text-[9px] rounded font-bold">TODAY</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3 text-gray-300">{{ Str::limit($challenge->title, 40) }}</td>
                                            <td class="px-5 py-3 text-gray-400">{{ $challenge->subject }}</td>
                                            <td class="px-5 py-3 text-center text-gray-400">{{ count($challenge->questions) }}</td>
                                            <td class="px-5 py-3 text-center text-gray-400">{{ $challenge->getParticipantsCount() }}</td>
                                            <td class="px-5 py-3 text-center text-gray-400">{{ number_format($challenge->getAverageScore(), 1) }}%</td>
                                            <td class="px-5 py-3 text-center">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium {{ $challenge->is_active ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                                    {{ $challenge->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <a href="{{ route('admin.daily-challenges.edit', $challenge) }}"
                                                       class="p-1.5 rounded hover:bg-white/10 text-gray-400 hover:text-blue-400 transition-colors" title="Edit">
                                                        <span class="material-icons-outlined" style="font-size: 16px;">edit</span>
                                                    </a>
                                                    <form action="{{ route('admin.daily-challenges.toggle', $challenge) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="p-1.5 rounded hover:bg-white/10 text-gray-400 hover:text-yellow-400 transition-colors"
                                                                title="{{ $challenge->is_active ? 'Deactivate' : 'Activate' }}">
                                                            <span class="material-icons-outlined" style="font-size: 16px;">{{ $challenge->is_active ? 'visibility_off' : 'visibility' }}</span>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.daily-challenges.destroy', $challenge) }}" method="POST" class="inline" onsubmit="return confirm('Delete this challenge?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-1.5 rounded hover:bg-white/10 text-gray-400 hover:text-red-400 transition-colors" title="Delete">
                                                            <span class="material-icons-outlined" style="font-size: 16px;">delete</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($challenges->hasPages())
                            <div class="px-5 py-3 border-t border-white/5">
                                {{ $challenges->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </main>
    </div>
</body>
</html>
