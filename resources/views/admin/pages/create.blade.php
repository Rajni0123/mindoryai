<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Page | Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        @include('admin.partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.pages.index') }}" class="text-gray-500 hover:text-white text-xs mb-1 inline-flex items-center gap-1">
                            <span class="material-icons-outlined text-sm">arrow_back</span>
                            Back to Pages
                        </a>
                        <h1 class="text-base font-semibold text-white">Create New Page</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Add a new page to your website and app</p>
                    </div>
                </div>
            </header>

            <div class="p-6">
                @if($errors->any())
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg">
                    <ul class="list-disc list-inside text-red-300 text-sm">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('admin.pages.store') }}" method="POST" class="card rounded-lg p-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Page Title *</label>
                            <input type="text" name="title" value="{{ old('title') }}" required
                                   class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                   placeholder="e.g., About Us, Terms of Service">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">URL Slug (auto-generated if empty)</label>
                            <input type="text" name="slug" value="{{ old('slug') }}"
                                   class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                   placeholder="e.g., about-us">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Page Content *</label>
                        <textarea name="content" rows="12" required
                                  class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition font-mono"
                                  placeholder="Write your page content here. HTML formatting is supported.">{{ old('content') }}</textarea>
                        <p class="text-gray-600 text-[10px] mt-1">HTML formatting is supported</p>
                    </div>

                    <div class="mb-4 p-3 bg-white/5 rounded-lg">
                        <h3 class="text-xs font-medium text-gray-400 mb-3">Display Options</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" checked
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Active</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="show_in_menu" value="1"
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Show in Menu</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="show_in_footer" value="1" checked
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Show in Footer</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="show_in_app" value="1" checked
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Show in App</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Display Order</label>
                            <input type="number" name="order" value="{{ old('order', 0) }}"
                                   class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Meta Title (SEO)</label>
                            <input type="text" name="meta_title" value="{{ old('meta_title') }}"
                                   class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                   placeholder="SEO title">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Meta Description (SEO)</label>
                            <input type="text" name="meta_description" value="{{ old('meta_description') }}"
                                   class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                   placeholder="Brief description (max 160 chars)">
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-4 border-t border-gray-800/50">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                            <span class="material-icons-outlined text-sm">save</span>
                            Create Page
                        </button>
                        <a href="{{ route('admin.pages.index') }}"
                           class="px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-medium rounded-lg transition-colors">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
