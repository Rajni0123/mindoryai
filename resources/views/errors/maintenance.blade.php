<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - Maintenance Mode</title>
    @vite(['resources/css/app.css'])
    <script>
        (function() {
            const saved = localStorage.getItem('theme') || 'dark';
            if (saved === 'light') { document.documentElement.classList.remove('dark'); document.documentElement.classList.add('light'); }
        })();
    </script>
</head>
<body class="dark:bg-[#05080a] bg-gray-50 flex items-center justify-center min-h-screen px-4">
    <div class="max-w-md w-full text-center">
        <!-- Icon -->
        <div class="mb-8">
            <div class="w-24 h-24 dark:bg-yellow-500/10 bg-yellow-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-12 h-12 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
        </div>

        <!-- Error Code -->
        <h1 class="text-6xl font-bold dark:text-white text-gray-900 mb-4">503</h1>

        <!-- Error Message -->
        <h2 class="text-2xl font-semibold dark:text-white text-gray-900 mb-4">Under Maintenance</h2>
        <p class="dark:text-gray-400 text-gray-600 mb-8 leading-relaxed">
            {{ $message ?? 'We are currently performing scheduled maintenance. Please check back soon.' }}
        </p>

        <!-- Refresh Button -->
        <div class="space-y-3">
            <button onclick="window.location.reload()" class="inline-block w-full bg-primary hover:bg-primary/90 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-md hover:shadow-lg">
                Refresh Page
            </button>
            <a href="{{ url('/') }}" class="inline-block w-full dark:bg-white/5 bg-white dark:hover:bg-white/10 hover:bg-gray-50 dark:text-gray-300 text-gray-700 font-semibold py-3 px-6 rounded-lg border dark:border-white/10 border-gray-300 transition duration-200">
                Go to Homepage
            </a>
        </div>

        <!-- Additional Info -->
        <div class="mt-8 pt-8 border-t dark:border-white/[0.06] border-gray-200">
            <p class="text-sm dark:text-gray-500 text-gray-500">
                We'll be back soon. Thank you for your patience.
            </p>
        </div>
    </div>
</body>
</html>
