@extends('layouts.app')

@section('title', 'Home - ' . config('app.name', 'BlinkStudy'))
@section('description', 'Upload an image and get AI-powered educational insights in engaging storytelling style')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <!-- Hero Section -->
    <div class="text-center mb-12 fade-in">
        <h1 class="text-4xl md:text-5xl font-bold dark:text-white text-gray-900 mb-4">
            Welcome to <span class="text-primary">{{ config('app.name', 'BlinkStudy') }}</span>
        </h1>
        <p class="text-lg dark:text-gray-400 text-gray-600 max-w-2xl mx-auto">
            Upload any image and get an educational, engaging explanation powered by AI.
            Learn about anything in a storytelling style!
        </p>
    </div>

    <!-- Chat Interface Container -->
    <div class="dark:bg-white/[0.02] bg-white rounded-2xl shadow-xl dark:shadow-none dark:border dark:border-white/[0.06] overflow-hidden">

        <!-- Chat Messages Area -->
        <div id="chatContainer" class="min-h-[400px] max-h-[600px] overflow-y-auto p-6 space-y-4 dark:bg-white/[0.02] bg-gray-50">

            <!-- Welcome Message -->
            <div class="flex items-start space-x-3 fade-in">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-primary">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="dark:bg-white/5 bg-white rounded-lg shadow-sm dark:shadow-none p-4 border dark:border-white/10 border-gray-200">
                        <p class="dark:text-gray-300 text-gray-800 leading-relaxed">
                            नमस्ते! मैं आपका AI learning companion हूं। आप मुझसे कोई भी सवाल पूछ सकते हैं या कोई image upload कर सकते हैं।
                        </p>
                    </div>
                </div>
            </div>

            <!-- Dynamic messages will be appended here -->

        </div>

        <!-- Input Area -->
        <div class="dark:bg-white/[0.02] bg-white border-t dark:border-white/[0.06] border-gray-200 p-4">
            <form id="imageUploadForm" enctype="multipart/form-data">
                @csrf

                <!-- Image Preview -->
                <div id="imagePreviewContainer" class="hidden mb-4">
                    <div class="relative inline-block">
                        <img id="imagePreview" src="" alt="Preview" class="max-h-48 rounded-lg shadow-lg">
                        <button type="button" id="removeImage" class="absolute -top-2 -right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg transition duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Text Input for Questions -->
                <div class="mb-4">
                    <textarea
                        id="questionInput"
                        name="question"
                        rows="3"
                        placeholder="Type your question here... या कोई भी सवाल पूछें..."
                        class="w-full px-4 py-3 border dark:border-white/10 border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent resize-none dark:bg-white/5 dark:text-white dark:placeholder-gray-500"
                    ></textarea>
                </div>

                <!-- Upload Button and Submit -->
                <div class="flex items-center space-x-3">
                    <!-- File Input (Hidden) -->
                    <input type="file" id="imageInput" name="image" accept="image/*" class="hidden">

                    <!-- Upload Button -->
                    <button type="button" id="uploadButton" class="flex-shrink-0 text-white p-3 rounded-lg transition duration-200 shadow-md hover:shadow-lg bg-primary hover:bg-primary/90" title="Upload Image">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </button>

                    <!-- Submit Button -->
                    <button type="submit" id="analyzeButton" class="flex-1 btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="buttonText">Ask AI / Analyze</span>
                    </button>
                </div>

                <!-- Info Text -->
                <p class="text-xs dark:text-gray-500 text-gray-500 mt-3 text-center">
                    Type a question upload an image (JPG, PNG - Max: 2MB)
                </p>
            </form>
        </div>

    </div>

    <!-- Features Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
        <div class="text-center p-6 dark:bg-white/[0.02] bg-white rounded-xl shadow-sm dark:shadow-none dark:border dark:border-white/[0.06] hover:shadow-md dark:hover:border-white/10 transition duration-200">
            <div class="w-12 h-12 dark:bg-primary/10 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h3 class="font-semibold dark:text-white text-gray-900 mb-2">Educational</h3>
            <p class="text-sm dark:text-gray-400 text-gray-600">Get detailed, informative explanations</p>
        </div>

        <div class="text-center p-6 dark:bg-white/[0.02] bg-white rounded-xl shadow-sm dark:shadow-none dark:border dark:border-white/[0.06] hover:shadow-md dark:hover:border-white/10 transition duration-200">
            <div class="w-12 h-12 dark:bg-purple-500/10 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 dark:text-purple-400 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h3 class="font-semibold dark:text-white text-gray-900 mb-2">Secure</h3>
            <p class="text-sm dark:text-gray-400 text-gray-600">Auto-delete images after 60 seconds</p>
        </div>

        <div class="text-center p-6 dark:bg-white/[0.02] bg-white rounded-xl shadow-sm dark:shadow-none dark:border dark:border-white/[0.06] hover:shadow-md dark:hover:border-white/10 transition duration-200">
            <div class="w-12 h-12 dark:bg-green-500/10 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 dark:text-green-400 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h3 class="font-semibold dark:text-white text-gray-900 mb-2">Fast</h3>
            <p class="text-sm dark:text-gray-400 text-gray-600">AI-powered instant analysis</p>
        </div>
    </div>

</div>

<!-- Loading Overlay -->
@include('components.loading')

@endsection

@push('scripts')
<script src="{{ asset('js/image-upload.js') }}"></script>
@endpush
