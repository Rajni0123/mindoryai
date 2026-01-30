@extends('layouts.app')

@section('title', 'Cookie Preferences - ' . config('app.name', 'Mindory'))

@section('content')
<div class="min-h-[60vh] py-12 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold dark:text-white text-gray-900 mb-2">Cookie Preferences</h1>
            <p class="dark:text-gray-400 text-gray-500">Manage how we use cookies on our website</p>
        </div>

        <!-- Cookie Information -->
        <div class="dark:bg-white/[0.02] bg-white rounded-xl p-6 mb-6 border dark:border-white/[0.06] border-gray-200 shadow-sm">
            <h2 class="text-xl font-semibold dark:text-white text-gray-900 mb-4">What are cookies?</h2>
            <p class="dark:text-gray-300 text-gray-600 mb-4">
                Cookies are small text files that are placed on your device when you visit our website. They help us provide you with a better experience by remembering your preferences and understanding how you use our service.
            </p>
        </div>

        <!-- Cookie Categories -->
        <div class="space-y-4">
            <!-- Essential Cookies -->
            <div class="dark:bg-white/[0.02] bg-white rounded-xl p-6 border dark:border-white/[0.06] border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-lg font-semibold dark:text-white text-gray-900 mb-1">Essential Cookies</h3>
                        <p class="text-sm dark:text-gray-400 text-gray-500">Required for the website to function properly</p>
                    </div>
                    <div class="px-3 py-1 bg-emerald-500/10 text-emerald-500 text-sm rounded-full font-medium">
                        Always Active
                    </div>
                </div>
                <p class="dark:text-gray-300 text-gray-600 text-sm">
                    These cookies are necessary for the website to function and cannot be disabled. They include session cookies, authentication tokens, and security features.
                </p>
            </div>

            <!-- Functional Cookies -->
            <div class="dark:bg-white/[0.02] bg-white rounded-xl p-6 border dark:border-white/[0.06] border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold dark:text-white text-gray-900 mb-1">Functional Cookies</h3>
                        <p class="text-sm dark:text-gray-400 text-gray-500">Remember your preferences and settings</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-11 h-6 dark:bg-gray-600 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>
                <p class="dark:text-gray-300 text-gray-600 text-sm">
                    These cookies remember your AI model preferences, chat history preferences, and other personalization settings to improve your experience.
                </p>
            </div>

            <!-- Analytics Cookies -->
            <div class="dark:bg-white/[0.02] bg-white rounded-xl p-6 border dark:border-white/[0.06] border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold dark:text-white text-gray-900 mb-1">Analytics Cookies</h3>
                        <p class="text-sm dark:text-gray-400 text-gray-500">Help us understand how you use our service</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer">
                        <div class="w-11 h-6 dark:bg-gray-600 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>
                <p class="dark:text-gray-300 text-gray-600 text-sm">
                    These cookies collect anonymous information about how visitors use our website, helping us improve the service and user experience.
                </p>
            </div>

            <!-- Marketing Cookies -->
            <div class="dark:bg-white/[0.02] bg-white rounded-xl p-6 border dark:border-white/[0.06] border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold dark:text-white text-gray-900 mb-1">Marketing Cookies</h3>
                        <p class="text-sm dark:text-gray-400 text-gray-500">Used to deliver relevant advertisements</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer">
                        <div class="w-11 h-6 dark:bg-gray-600 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>
                <p class="dark:text-gray-300 text-gray-600 text-sm">
                    These cookies track your activity across websites to deliver personalized advertisements and measure campaign effectiveness.
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 flex gap-4">
            <button class="flex-1 bg-primary hover:bg-primary/90 text-white font-medium py-3 px-6 rounded-lg transition-colors">
                Save Preferences
            </button>
            <button class="flex-1 dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 dark:text-white text-gray-800 font-medium py-3 px-6 rounded-lg transition-colors">
                Accept All
            </button>
        </div>

        <!-- Additional Information -->
        <div class="mt-8 dark:bg-white/[0.02] bg-white rounded-xl p-6 border dark:border-white/[0.06] border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold dark:text-white text-gray-900 mb-3">More Information</h3>
            <p class="dark:text-gray-300 text-gray-600 text-sm mb-3">
                For more details about how we handle your data, please read our <a href="{{ route('privacy') }}" class="text-primary hover:text-primary/80 underline">Privacy Policy</a>.
            </p>
            <p class="dark:text-gray-300 text-gray-600 text-sm">
                You can change your cookie preferences at any time by returning to this page.
            </p>
        </div>
    </div>
</div>
@endsection
