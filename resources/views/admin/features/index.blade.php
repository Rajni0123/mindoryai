<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Feature Management | Admin Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "neon-blue": "#3ddcff",
                        "neon-violet": "#9d5bff",
                        "soft-grey": "#d1d1d1"
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"],
                        "body": ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="font-body text-white">
    <div class="flex h-screen">
        <!-- Sidebar -->
        @include('admin.partials.sidebar')

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-gray-900/30 backdrop-blur-sm border-b border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold font-display text-white">Feature Management</h2>
                        <p class="text-soft-grey/70 text-sm mt-1">Manage your feature highlights section</p>
                    </div>
                    <a href="{{ route('admin.features.create') }}"
                       class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-neon-blue to-neon-violet text-black font-bold rounded-lg hover:shadow-[0_0_20px_0_rgba(61,220,255,0.5)] transition-all">
                        <span class="material-icons-outlined">add</span>
                        Add Feature
                    </a>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6 space-y-6">
                @if(session('success'))
                <div class="bg-green-500/20 border border-green-500/50 rounded-lg p-4 flex items-center gap-3">
                    <span class="material-icons-outlined text-green-400">check_circle</span>
                    <p class="text-green-300">{{ session('success') }}</p>
                </div>
                @endif

                <!-- Features Table -->
                <div class="bg-gray-900/50 backdrop-blur-sm border border-gray-800 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-800/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-neon-blue">Order</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-neon-blue">Icon</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-neon-blue">Title</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-neon-blue">Description</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-neon-blue">Color</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-neon-blue">Status</th>
                                    <th class="px-6 py-4 text-right text-sm font-semibold text-neon-blue">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800">
                                @forelse($features as $feature)
                                <tr class="hover:bg-gray-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-700 text-white font-semibold text-sm">
                                            {{ $feature->order }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-{{ $feature->color === 'blue' ? 'neon-blue' : 'neon-violet' }}/20 border border-{{ $feature->color === 'blue' ? 'neon-blue' : 'neon-violet' }}/30">
                                            <span class="material-symbols-outlined text-{{ $feature->color === 'blue' ? 'neon-blue' : 'neon-violet' }}">{{ $feature->icon }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-white font-semibold">{{ $feature->title }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-soft-grey text-sm line-clamp-2">{{ $feature->description }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold
                                            {{ $feature->color === 'blue' ? 'bg-blue-500/20 text-blue-400' : 'bg-purple-500/20 text-purple-400' }}">
                                            {{ ucfirst($feature->color) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($feature->is_active)
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-semibold">
                                            <span class="material-icons-outlined text-sm">check_circle</span>
                                            Active
                                        </span>
                                        @else
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-500/20 text-gray-400 rounded-full text-xs font-semibold">
                                            <span class="material-icons-outlined text-sm">cancel</span>
                                            Inactive
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.features.edit', $feature) }}"
                                               class="inline-flex items-center gap-1 px-4 py-2 bg-neon-blue/20 text-neon-blue rounded-lg hover:bg-neon-blue/30 transition-colors">
                                                <span class="material-icons-outlined text-sm">edit</span>
                                                Edit
                                            </a>
                                            <form action="{{ route('admin.features.destroy', $feature) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Are you sure you want to delete this feature?');"
                                                  class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors">
                                                    <span class="material-icons-outlined text-sm">delete</span>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <span class="material-icons-outlined text-6xl text-gray-700 mb-4 block">stars</span>
                                        <p class="text-soft-grey text-lg">No features found</p>
                                        <a href="{{ route('admin.features.create') }}"
                                           class="inline-block mt-4 px-6 py-2 bg-neon-blue/20 text-neon-blue rounded-lg hover:bg-neon-blue/30 transition-colors">
                                            Add your first feature
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
