<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pages Management | Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .card:hover { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.12); }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        @include('admin.partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-semibold text-white">Pages Management</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Create and manage website & app pages</p>
                    </div>
                    <a href="{{ route('admin.pages.create') }}" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 text-sm text-white font-medium rounded-lg hover:opacity-90 transition flex items-center gap-2">
                        <span class="material-icons-outlined text-sm">add</span>
                        Create New Page
                    </a>
                </div>
            </header>

            <div class="p-6">
                @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/20 rounded-lg text-sm text-green-300">
                    {{ session('success') }}
                </div>
                @endif

                <div class="card rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-[#0a0a0a] border-b border-gray-800/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Title</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Slug</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Display</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800/50">
                                @forelse($pages as $page)
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-purple-500/20 text-purple-400 font-semibold text-xs">
                                            {{ $page->order }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-white">{{ $page->title }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <code class="text-blue-400 text-xs bg-blue-500/10 px-2 py-1 rounded">/{{ $page->slug }}</code>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-1.5">
                                            @if($page->show_in_menu)
                                            <span class="px-2 py-0.5 text-[10px] rounded bg-blue-500/20 text-blue-400">Menu</span>
                                            @endif
                                            @if($page->show_in_footer)
                                            <span class="px-2 py-0.5 text-[10px] rounded bg-purple-500/20 text-purple-400">Footer</span>
                                            @endif
                                            @if($page->show_in_app)
                                            <span class="px-2 py-0.5 text-[10px] rounded bg-green-500/20 text-green-400">App</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($page->is_active)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-500/20 text-green-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                            Active
                                        </span>
                                        @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-500/20 text-gray-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            Inactive
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('page.show', $page->slug) }}" target="_blank"
                                               class="p-1.5 rounded bg-gray-500/10 hover:bg-gray-500/20 text-gray-400 hover:text-white transition-colors"
                                               title="View Page">
                                                <span class="material-icons-outlined text-sm">visibility</span>
                                            </a>
                                            <a href="{{ route('admin.pages.edit', $page->id) }}"
                                               class="p-1.5 rounded bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 hover:text-blue-300 transition-colors"
                                               title="Edit">
                                                <span class="material-icons-outlined text-sm">edit</span>
                                            </a>
                                            <form action="{{ route('admin.pages.toggle', $page->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="p-1.5 rounded {{ $page->is_active ? 'bg-yellow-500/10 hover:bg-yellow-500/20 text-yellow-400' : 'bg-green-500/10 hover:bg-green-500/20 text-green-400' }} transition-colors"
                                                        title="{{ $page->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <span class="material-icons-outlined text-sm">{{ $page->is_active ? 'toggle_off' : 'toggle_on' }}</span>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.pages.destroy', $page->id) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this page?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="p-1.5 rounded bg-red-500/10 hover:bg-red-500/20 text-red-400 hover:text-red-300 transition-colors"
                                                        title="Delete">
                                                    <span class="material-icons-outlined text-sm">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <span class="material-icons-outlined text-4xl text-gray-600">article</span>
                                            <p class="text-gray-400">No pages found</p>
                                            <a href="{{ route('admin.pages.create') }}" class="text-blue-400 hover:text-blue-300 text-sm">
                                                Create your first page
                                            </a>
                                        </div>
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
