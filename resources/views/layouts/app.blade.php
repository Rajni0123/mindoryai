<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $siteTitle = \App\Models\FrontendConfig::getValue('seo.site_title', 'BlinkStudy - AI Knowledge Website');
        $metaDescription = \App\Models\FrontendConfig::getValue('seo.meta_description', 'BlinkStudy - Your AI-powered learning companion for instant doubt solving and personalized education');
        $metaKeywords = \App\Models\FrontendConfig::getValue('seo.meta_keywords', '');
        $faviconUrl = \App\Models\FrontendConfig::getValue('seo.favicon_url', '/favicon.ico');
        $googleVerification = \App\Models\FrontendConfig::getValue('seo.google_site_verification', '');
        $googleAnalyticsId = \App\Models\FrontendConfig::getValue('seo.google_analytics_id', '');
        $ogImage = \App\Models\FrontendConfig::getValue('seo.og_image', '');
        $twitterHandle = \App\Models\FrontendConfig::getValue('seo.twitter_handle', '');
        $currentUrl = url()->current();
    @endphp

    <title>@yield('title', $siteTitle)</title>
    <meta name="description" content="@yield('description', $metaDescription)">
    @if($metaKeywords)
    <meta name="keywords" content="{{ $metaKeywords }}">
    @endif

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:title" content="@yield('title', $siteTitle)">
    <meta property="og:description" content="@yield('description', $metaDescription)">
    @if($ogImage)
    @php $ogImagePath = str_starts_with($ogImage, 'http') ? $ogImage : asset($ogImage); @endphp
    <meta property="og:image" content="{{ $ogImagePath }}">
    @endif

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ $currentUrl }}">
    <meta property="twitter:title" content="@yield('title', $siteTitle)">
    <meta property="twitter:description" content="@yield('description', $metaDescription)">
    @if($ogImage)<meta property="twitter:image" content="{{ $ogImagePath }}">@endif
    @if($twitterHandle)<meta property="twitter:site" content="@{{ $twitterHandle }}">@endif

    <!-- Google Search Console Verification -->
    @if($googleVerification)
    <meta name="google-site-verification" content="{{ $googleVerification }}">
    @endif

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ $currentUrl }}">

    <!-- Favicon -->
    @php
        $faviconPath = str_starts_with($faviconUrl, 'http') ? $faviconUrl : asset($faviconUrl);
        $faviconExt = pathinfo($faviconUrl, PATHINFO_EXTENSION);
        $faviconMime = match($faviconExt) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => 'image/x-icon',
        };
    @endphp
    <link rel="icon" type="{{ $faviconMime }}" href="{{ $faviconPath }}">
    <link rel="shortcut icon" href="{{ $faviconPath }}">
    <link rel="apple-touch-icon" href="{{ $faviconPath }}">

    <!-- Robots -->
    <meta name="robots" content="index, follow">
    <meta name="author" content="BlinkStudy">
    <meta name="theme-color" content="#705CF6">

    <!-- TailwindCSS -->
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])

    <!-- Fonts: Outfit + Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Google Analytics -->
    @if($googleAnalyticsId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAnalyticsId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $googleAnalyticsId }}');
    </script>
    @endif

    <!-- Theme init (before paint) -->
    <script>
        (function() {
            const saved = localStorage.getItem('theme') || 'light';
            if (saved === 'light') { document.documentElement.classList.remove('dark'); document.documentElement.classList.add('light'); }
            else if (saved === 'system') {
                if (!window.matchMedia('(prefers-color-scheme: dark)').matches) { document.documentElement.classList.remove('dark'); document.documentElement.classList.add('light'); }
            }
        })();
    </script>

    @stack('styles')

    <style>
        * { font-family: 'Outfit', sans-serif; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeIn 0.3s ease-out; }
    </style>
</head>
<body class="bg-[#F8FAFF] text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Header -->
    @include('components.header')

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    @include('components.footer')

    @stack('scripts')

    <!-- Profile Completion Popup (first-time login) -->
    @if(session('show_profile_popup') && auth()->check() && (!auth()->user()->hasCompletedProfile() || auth()->user()->needsPhoneCollection()))
    @php
        $profileUser = auth()->user();
        $needsPhone = $profileUser->needsPhoneCollection();
        $profileEmail = $profileUser->profileEmail();
        $profileName = $profileUser->name ?? '';
        if (preg_match('/^User \d{4}$/', trim($profileName))) {
            $profileName = '';
        }
    @endphp
    <div id="profile-popup-overlay" class="fixed inset-0 bg-black/55 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 transition-opacity duration-300" style="opacity:0">
        <div id="profile-popup" class="w-full max-w-md bg-white dark:bg-[#1a1f2e] rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 ease-out">

            <div class="px-6 py-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white text-center">Profile Information</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center mt-1 mb-6">
                    {{ $needsPhone ? 'Add your phone number to continue' : 'Manage your basic profile details' }}
                </p>

                <form id="profile-popup-form" class="space-y-4">
                    @if(!$needsPhone)
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Full Name</label>
                        <input type="text" id="popup-name" name="name" value="{{ $profileName }}"
                            placeholder="Enter your name"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none transition text-sm"
                            required minlength="2" maxlength="50">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="email" id="popup-email" name="email" value="{{ $profileEmail }}"
                            placeholder="your@email.com"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none transition text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Phone</label>
                        <div class="flex items-center gap-2 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700">
                            <span class="text-sm text-gray-500 dark:text-gray-400">+91</span>
                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ $profileUser->mobile }}</span>
                            <span class="ml-auto material-symbols-outlined text-green-500 text-base">verified</span>
                        </div>
                    </div>
                    @else
                    <input type="hidden" id="popup-name" value="{{ $profileName ?: $profileUser->name }}">

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                        <input type="email" id="popup-email" value="{{ $profileEmail }}" readonly
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 outline-none text-sm cursor-default">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Phone</label>
                        <div class="flex items-center gap-2">
                            <span class="px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-sm text-gray-500 dark:text-gray-400">+91</span>
                            <input type="tel" id="popup-mobile" inputmode="numeric" maxlength="10"
                                placeholder="Enter 10-digit mobile number" required pattern="[6-9][0-9]{9}"
                                class="flex-1 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none transition text-sm">
                        </div>
                    </div>
                    @endif

                    <button type="submit" id="popup-submit-btn"
                        class="w-full py-3.5 mt-2 rounded-full bg-gradient-to-r from-violet-500 to-indigo-500 text-white font-bold text-sm shadow-lg shadow-violet-500/25 hover:shadow-violet-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all duration-200">
                        {{ $needsPhone ? 'Continue' : 'Update Profile' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var overlay = document.getElementById('profile-popup-overlay');
        var popup = document.getElementById('profile-popup');
        var needsPhone = @json($needsPhone);
        if (!overlay || !popup) return;

        setTimeout(function() {
            overlay.style.opacity = '1';
            popup.style.transform = 'scale(1)';
            popup.style.opacity = '1';
        }, 500);

        document.getElementById('profile-popup-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('popup-submit-btn');
            var nameEl = document.getElementById('popup-name');
            var name = nameEl ? nameEl.value.trim() : '';
            var emailEl = document.getElementById('popup-email');
            var email = emailEl ? emailEl.value.trim() : '';
            var mobileEl = document.getElementById('popup-mobile');
            var mobile = mobileEl ? mobileEl.value.replace(/\D/g, '') : '';

            if (name.length < 2) return;
            if (needsPhone && !/^[6-9]\d{9}$/.test(mobile)) {
                alert('Enter a valid 10-digit mobile number');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Updating...';

            var payload = { name: name, email: email || null };
            if (needsPhone) payload.mobile = mobile;

            fetch('/profile/complete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    btn.textContent = 'Done!';
                    btn.classList.remove('from-violet-500', 'to-indigo-500');
                    btn.classList.add('bg-green-500');
                    setTimeout(function() {
                        popup.style.transform = 'scale(0.95)';
                        popup.style.opacity = '0';
                        overlay.style.opacity = '0';
                        setTimeout(function() {
                            overlay.remove();
                            window.location.href = @json(\App\Support\ChatSubdomainUrl::appUrl());
                        }, 350);
                    }, 500);
                } else {
                    alert(data.message || 'Could not update profile');
                    btn.disabled = false;
                    btn.textContent = needsPhone ? 'Continue' : 'Update Profile';
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.textContent = needsPhone ? 'Continue' : 'Update Profile';
            });
        });
    })();
    </script>
    @php session()->forget('show_profile_popup'); @endphp
    @endif

</body>
</html>
