<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
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
            <div class="w-24 h-24 dark:bg-red-500/10 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-12 h-12 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>

        <!-- Error Code -->
        <h1 class="text-6xl font-bold dark:text-white text-gray-900 mb-4">500</h1>

        <!-- Error Message -->
        <h2 class="text-2xl font-semibold dark:text-white text-gray-900 mb-4">Server Error</h2>
        <p class="dark:text-gray-400 text-gray-600 mb-8 leading-relaxed">
            Oops! Something went wrong on our end. We're working to fix it.
        </p>

        <!-- What to Do -->
        <div class="dark:bg-primary/5 bg-blue-50 border dark:border-primary/20 border-blue-200 rounded-lg p-4 mb-8 text-left">
            <h3 class="font-semibold dark:text-white text-gray-900 mb-2">What you can do:</h3>
            <ul class="text-sm dark:text-gray-300 text-gray-700 space-y-1 list-disc list-inside">
                <li>Refresh the page</li>
                <li>Try again in a few minutes</li>
                <li>Contact support if the problem persists</li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-3">
            <button onclick="location.reload()" class="inline-block w-full bg-primary hover:bg-primary/90 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-md hover:shadow-lg">
                Refresh Page
            </button>
            <a href="{{ url('/') }}" class="inline-block w-full dark:bg-white/5 bg-white dark:hover:bg-white/10 hover:bg-gray-50 dark:text-gray-300 text-gray-700 font-semibold py-3 px-6 rounded-lg border dark:border-white/10 border-gray-300 transition duration-200">
                Go to Homepage
            </a>
        </div>

        <!-- Error ID -->
        @if(isset($exception))
        <div class="mt-6 pt-6 border-t dark:border-white/[0.06] border-gray-200">
            <p class="text-xs dark:text-gray-500 text-gray-500">
                Error ID: {{ md5($exception->getMessage() . time()) }}
            </p>
        </div>
        @endif
    </div>
</body>
</html>
