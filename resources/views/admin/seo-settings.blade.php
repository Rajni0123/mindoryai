<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SEO Settings - Admin</title>

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
            <!-- Header -->
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-semibold text-white">SEO Settings</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Manage Google Search Console, Meta Tags, Favicon & Analytics</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-green-400" style="font-size: 16px;">check_circle</span>
                    <p class="text-green-300">{{ session('success') }}</p>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-red-400" style="font-size: 16px;">error</span>
                    <p class="text-red-300">{{ session('error') }}</p>
                </div>
                @endif

                @if($errors->any())
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="material-icons-outlined text-red-400" style="font-size: 16px;">error</span>
                        <p class="text-red-300 font-semibold">Validation Errors:</p>
                    </div>
                    <ul class="text-red-300 text-xs ml-6 space-y-1">
                        @foreach($errors->all() as $error)
                        <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Info Box -->
                <div class="mb-6 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                    <div class="flex items-start gap-3">
                        <span class="material-icons-outlined text-blue-400" style="font-size: 20px;">info</span>
                        <div class="text-xs text-blue-300">
                            <p class="font-semibold mb-1">SEO & Google Services Configuration:</p>
                            <p class="text-blue-300/90">Configure site title, meta tags, Google Search Console verification, and Google Analytics for better search visibility and tracking.</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.seo-settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Basic SEO Settings -->
                    <div class="card rounded-lg p-6 mb-6">
                        <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined" style="font-size: 18px;">description</span>
                            Basic SEO Settings
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Site Title *</label>
                                <input type="text" name="site_title" value="{{ old('site_title', $seoSettings['site_title']) }}" required
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="BlinkStudy - AI Knowledge Website">
                                <p class="mt-1 text-[10px] text-gray-500">Appears in browser tab and Google search results (max 60 chars)</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Meta Description *</label>
                                <textarea name="meta_description" rows="3" required
                                          class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                          placeholder="A compelling description of your website...">{{ old('meta_description', $seoSettings['meta_description']) }}</textarea>
                                <p class="mt-1 text-[10px] text-gray-500">Appears in Google search results (max 160 chars recommended)</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Meta Keywords</label>
                                <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $seoSettings['meta_keywords']) }}"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="AI learning, education, doubt solving">
                                <p class="mt-1 text-[10px] text-gray-500">Comma-separated (optional, less important for modern SEO)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Google Services -->
                    <div class="card rounded-lg p-6 mb-6">
                        <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined" style="font-size: 18px;">verified</span>
                            Google Services
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">
                                    <span class="material-icons-outlined align-middle text-green-400" style="font-size: 14px;">check_circle</span>
                                    Google Search Console Verification Code
                                </label>
                                <input type="text" name="google_site_verification" value="{{ old('google_site_verification', $seoSettings['google_site_verification']) }}"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm font-mono focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="abcdefghijklmnopqrstuvwxyz123456">
                                <p class="mt-1 text-[10px] text-gray-500">
                                    Get from <a href="https://search.google.com/search-console" target="_blank" class="text-blue-400 underline">Google Search Console</a> → Settings → Verification → HTML tag
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">
                                    <span class="material-icons-outlined align-middle text-blue-400" style="font-size: 14px;">analytics</span>
                                    Google Analytics ID
                                </label>
                                <input type="text" name="google_analytics_id" value="{{ old('google_analytics_id', $seoSettings['google_analytics_id']) }}"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm font-mono focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="G-XXXXXXXXXX or UA-XXXXXXXXX-X">
                                <p class="mt-1 text-[10px] text-gray-500">
                                    Get from <a href="https://analytics.google.com" target="_blank" class="text-blue-400 underline">Google Analytics</a> → Admin → Property Settings
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Favicon & Images -->
                    <div class="card rounded-lg p-6 mb-6">
                        <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined" style="font-size: 18px;">image</span>
                            Favicon & Images
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Favicon</label>
                                @if($seoSettings['favicon_url'])
                                <div class="mb-2">
                                    <img src="{{ asset($seoSettings['favicon_url']) }}" alt="Favicon" class="h-16 border border-gray-700 p-2 bg-white rounded">
                                </div>
                                @endif
                                <input type="file" name="favicon" accept=".ico,.png,.jpg"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                <p class="mt-1 text-[10px] text-gray-500">32x32 or 64x64 ICO/PNG (max 512KB)</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-2">Open Graph Image (Social Media)</label>
                                @if($seoSettings['og_image'])
                                <div class="mb-2">
                                    <img src="{{ asset($seoSettings['og_image']) }}" alt="OG Image" class="max-w-[200px] border border-gray-700 rounded">
                                </div>
                                @endif
                                <input type="file" name="og_image" accept="image/*"
                                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                <p class="mt-1 text-[10px] text-gray-500">1200x630px for Facebook/Twitter/LinkedIn (max 2MB)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media -->
                    <div class="card rounded-lg p-6 mb-6">
                        <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                            <span class="material-icons-outlined" style="font-size: 18px;">share</span>
                            Social Media
                        </h3>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-2">Twitter/X Handle</label>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 text-sm">@</span>
                                <input type="text" name="twitter_handle" value="{{ old('twitter_handle', $seoSettings['twitter_handle']) }}"
                                       class="flex-1 px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                       placeholder="blinkstudyai">
                            </div>
                            <p class="mt-1 text-[10px] text-gray-500">Your Twitter/X username (without @)</p>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                            <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                            Save SEO Settings
                        </button>
                    </div>
                </form>

                <!-- SEO Tips -->
                <div class="mt-6 p-4 bg-amber-500/10 border border-amber-500/30 rounded-lg">
                    <h4 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
                        <span class="material-icons-outlined text-amber-400" style="font-size: 18px;">lightbulb</span>
                        SEO Best Practices
                    </h4>
                    <ul class="text-xs text-gray-300 space-y-2">
                        <li>✅ Keep title under 60 characters for proper display in search results</li>
                        <li>✅ Write compelling meta descriptions (150-160 characters) that encourage clicks</li>
                        <li>✅ Verify your site with Google Search Console to monitor search performance</li>
                        <li>✅ Use high-quality favicon (32x32 or 64x64 PNG/ICO)</li>
                        <li>✅ Add Open Graph image (1200x630px) for better social media sharing</li>
                        <li>✅ Install Google Analytics to track visitor behavior</li>
                        <li>✅ Submit sitemap: <code class="bg-gray-800 px-2 py-0.5 rounded">{{ url('/sitemap.xml') }}</code></li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
