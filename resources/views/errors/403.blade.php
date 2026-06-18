<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied</title>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
        </div>

        <!-- Error Code -->
        <h1 class="text-6xl font-bold dark:text-white text-gray-900 mb-4">403</h1>

        <!-- Error Message -->
        <h2 class="text-2xl font-semibold dark:text-white text-gray-900 mb-4">Access Denied</h2>
        <p class="dark:text-gray-400 text-gray-600 mb-2 leading-relaxed">
            Your IP address is not authorized to access this website.
        </p>

        <!-- IP Address Display -->
        <div class="dark:bg-white/[0.02] bg-white rounded-lg shadow-sm dark:shadow-none dark:border dark:border-white/[0.06] p-4 mb-8">
            <p class="text-sm dark:text-gray-500 text-gray-500 mb-1">Your IP Address:</p>
            <p class="text-lg font-mono font-semibold dark:text-white text-gray-900">{{ request()->ip() }}</p>
        </div>

        <!-- Additional Info -->
        <div class="dark:bg-primary/5 bg-blue-50 border dark:border-primary/20 border-blue-200 rounded-lg p-4 mb-8">
            <p class="text-sm dark:text-gray-300 text-gray-700 leading-relaxed">
                If you believe this is an error, please contact the administrator to whitelist your IP address.
            </p>
        </div>

        <!-- Security Notice -->
        <p class="text-xs dark:text-gray-500 text-gray-500">
            This website uses IP whitelisting for security purposes.
        </p>
    </div>
</body>
</html>
