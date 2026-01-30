<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Activate Your Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-white">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <!-- Logo/Header -->
        <div class="w-full max-w-sm mb-8 text-center">
            <div class="mb-4">
                <svg class="mx-auto h-12 w-12 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </div>
            <h1 class="text-3xl font-semibold text-gray-900 mb-2">Activate Your Account</h1>
            <p class="text-sm text-gray-600">Enter your activation token to start using AI features</p>
        </div>

        <!-- Activation Form -->
        <div class="w-full max-w-sm">
            @if(session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                {{ session('error') }}
            </div>
            @endif

            @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                {{ session('success') }}
            </div>
            @endif

            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Welcome, {{ Auth::user()->name }}!</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Your account has been created successfully. To access AI features, please enter the activation token provided to you.</p>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('activate.token.submit') }}" class="space-y-4">
                @csrf

                <!-- Activation Token -->
                <div>
                    <label for="activation_token" class="block text-sm font-medium text-gray-700 mb-1.5">Activation Token</label>
                    <input
                        type="text"
                        id="activation_token"
                        name="activation_token"
                        value="{{ old('activation_token') }}"
                        required
                        autofocus
                        class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all"
                        placeholder="Enter your activation token"
                    />
                    @error('activation_token')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-gray-500">Contact your administrator if you don't have an activation token.</p>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full py-3 px-4 bg-black hover:bg-gray-800 text-white font-medium rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black"
                >
                    Activate Account
                </button>
            </form>

            <!-- Footer Links -->
            <div class="mt-6 text-center text-sm text-gray-600">
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="font-medium text-black hover:underline">
                        Sign out
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-auto pt-12 pb-6 text-center text-xs text-gray-500">
            <a href="{{ route('home') }}" class="hover:underline">Terms of use</a>
            <span class="mx-2">|</span>
            <a href="{{ route('privacy') }}" class="hover:underline">Privacy policy</a>
        </div>
    </div>
</body>
</html>
