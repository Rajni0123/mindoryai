<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                    },
                },
            },
        };
    </script>
</head>
<body class="bg-[#05080a] flex items-center justify-center min-h-screen px-4 text-white">
    <div class="max-w-md w-full text-center">
        <div class="mb-8">
            <div class="w-24 h-24 bg-red-500/10 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>

        <h1 class="text-6xl font-bold mb-4">500</h1>
        <h2 class="text-2xl font-semibold mb-4">Server Error</h2>
        <p class="text-gray-400 mb-8 leading-relaxed">
            Oops! Something went wrong on our end. We're working to fix it.
        </p>

        <div class="bg-indigo-500/5 border border-indigo-500/20 rounded-lg p-4 mb-8 text-left">
            <h3 class="font-semibold mb-2">What you can do:</h3>
            <ul class="text-sm text-gray-300 space-y-1 list-disc list-inside">
                <li>Refresh the page</li>
                <li>Try again in a few minutes</li>
                <li>Contact support if the problem persists</li>
            </ul>
        </div>

        <div class="space-y-3">
            <button onclick="location.reload()" class="inline-block w-full bg-primary hover:bg-indigo-500 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                Refresh Page
            </button>
            <a href="{{ url('/') }}" class="inline-block w-full bg-white/5 hover:bg-white/10 text-gray-300 font-semibold py-3 px-6 rounded-lg border border-white/10 transition duration-200">
                Go to Homepage
            </a>
        </div>

        @if(isset($exception))
        <div class="mt-6 pt-6 border-t border-white/10">
            <p class="text-xs text-gray-500">
                Error ID: {{ md5($exception->getMessage() . time()) }}
            </p>
        </div>
        @endif
    </div>
</body>
</html>
