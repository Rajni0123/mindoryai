@extends('layouts.app')

@section('title', 'About Us - Mindory')
@section('description', 'Learn about Mindory - Your AI-powered educational companion')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <!-- Header -->
    <div class="text-center mb-12 fade-in">
        <h1 class="text-4xl md:text-5xl font-bold dark:text-white text-gray-900 mb-4">
            About <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-purple-500">Mindory</span>
        </h1>
        <p class="text-lg dark:text-gray-400 text-gray-600">Your AI-powered knowledge companion</p>
    </div>

    <!-- Content -->
    <div class="dark:bg-white/[0.02] bg-white rounded-2xl shadow-xl dark:shadow-none dark:border dark:border-white/[0.06] p-8 md:p-12 space-y-8">

        <!-- Mission -->
        <section>
            <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">Our Mission</h2>
            <p class="dark:text-gray-400 text-gray-700 leading-relaxed">
                Mindory is dedicated to making education accessible, engaging, and enjoyable for everyone.
                We believe that learning should be as natural as having a conversation with a knowledgeable friend.
                That's why we've created an AI-powered platform that explains complex topics in simple, story-driven ways.
            </p>
        </section>

        <!-- How It Works -->
        <section>
            <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">How It Works</h2>
            <div class="space-y-4">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 w-10 h-10 dark:bg-primary/10 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-primary font-bold">1</span>
                    </div>
                    <div>
                        <h3 class="font-semibold dark:text-white text-gray-900 mb-1">Upload an Image</h3>
                        <p class="dark:text-gray-400 text-gray-600">Choose any image you're curious about - a historical photo, scientific diagram, artwork, or anything else.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 w-10 h-10 dark:bg-primary/10 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-primary font-bold">2</span>
                    </div>
                    <div>
                        <h3 class="font-semibold dark:text-white text-gray-900 mb-1">AI Analysis</h3>
                        <p class="dark:text-gray-400 text-gray-600">Our advanced AI vision technology analyzes your image and understands its context, content, and significance.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 w-10 h-10 dark:bg-primary/10 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-primary font-bold">3</span>
                    </div>
                    <div>
                        <h3 class="font-semibold dark:text-white text-gray-900 mb-1">Get Insights</h3>
                        <p class="dark:text-gray-400 text-gray-600">Receive a detailed, engaging explanation that makes learning fun and memorable - just like Dhruv Rathee's storytelling style!</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section>
            <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">Why Choose Us?</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="dark:bg-white/5 bg-gray-50 rounded-lg p-6 dark:border dark:border-white/[0.06]">
                    <h3 class="font-semibold dark:text-white text-gray-900 mb-2">Educational & Engaging</h3>
                    <p class="dark:text-gray-400 text-gray-600 text-sm">Complex topics explained in simple, story-driven format that keeps you interested.</p>
                </div>

                <div class="dark:bg-white/5 bg-gray-50 rounded-lg p-6 dark:border dark:border-white/[0.06]">
                    <h3 class="font-semibold dark:text-white text-gray-900 mb-2">Privacy First</h3>
                    <p class="dark:text-gray-400 text-gray-600 text-sm">Your images are automatically deleted after 60 seconds. We respect your privacy.</p>
                </div>

                <div class="dark:bg-white/5 bg-gray-50 rounded-lg p-6 dark:border dark:border-white/[0.06]">
                    <h3 class="font-semibold dark:text-white text-gray-900 mb-2">Advanced AI</h3>
                    <p class="dark:text-gray-400 text-gray-600 text-sm">Powered by cutting-edge GPT-4 Vision technology for accurate and insightful analysis.</p>
                </div>

                <div class="dark:bg-white/5 bg-gray-50 rounded-lg p-6 dark:border dark:border-white/[0.06]">
                    <h3 class="font-semibold dark:text-white text-gray-900 mb-2">Secure Access</h3>
                    <p class="dark:text-gray-400 text-gray-600 text-sm">IP whitelisting ensures that only authorized users can access the platform.</p>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="text-center dark:bg-white/5 bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg p-8 dark:border dark:border-white/[0.06]">
            <h2 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">Ready to Start Learning?</h2>
            <p class="dark:text-gray-400 text-gray-600 mb-6">Upload your first image and discover the power of AI-driven education.</p>
            <a href="{{ route('home') }}" class="inline-block bg-primary hover:bg-primary/90 text-white font-semibold py-3 px-8 rounded-lg transition duration-200 shadow-md hover:shadow-lg">
                Get Started
            </a>
        </section>

    </div>

</div>
@endsection
