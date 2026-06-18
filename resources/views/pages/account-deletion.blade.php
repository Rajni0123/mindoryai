@extends('layouts.app')

@section('title', 'Account Deletion - BlinkStudy')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-teal-50 to-cyan-50 py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12">
            <!-- Header -->
            <div class="text-center mb-10">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Account Deletion</h1>
                <p class="text-gray-600">Request to delete your BlinkStudy account and data</p>
            </div>

            <!-- Info Section -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">What happens when you delete your account?</h2>
                <ul class="space-y-3 text-gray-700">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Your account and profile information will be permanently deleted</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span>All your chat history and AI conversations will be erased</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Your quiz history and progress will be removed</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Any active subscription will be cancelled (no refund for remaining period)</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span>This action is irreversible - you cannot recover your account</span>
                    </li>
                </ul>
            </div>

            <!-- Data Retention Info -->
            <div class="bg-gray-50 rounded-xl p-6 mb-8">
                <h3 class="font-semibold text-gray-900 mb-2">Data Retention</h3>
                <p class="text-gray-600 text-sm">
                    Upon account deletion request, your personal data will be deleted within 30 days.
                    Some anonymized data may be retained for analytics purposes.
                    Transaction records may be kept for legal compliance as required by Indian law.
                </p>
            </div>

            <!-- Deletion Request Form -->
            <div class="border-t pt-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Request Account Deletion</h2>

                @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-green-800 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-red-800 font-medium">{{ session('error') }}</p>
                    </div>
                </div>
                @endif

                @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <ul class="text-red-800 text-sm list-disc list-inside">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if(!session('success'))
                <form action="{{ route('account.deletion.request') }}" method="POST" class="space-y-6" id="deletionForm">
                    @csrf

                    <div>
                        <label for="mobile" class="block text-sm font-medium text-gray-700 mb-2">
                            Registered Mobile Number
                        </label>
                        <input type="tel" name="mobile" id="mobile" required value="{{ old('mobile') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                            placeholder="Enter your registered mobile number">
                    </div>

                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Reason for deletion (optional)
                        </label>
                        <select name="reason" id="reason"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Select a reason</option>
                            <option value="not_using" {{ old('reason') == 'not_using' ? 'selected' : '' }}>I'm not using the app anymore</option>
                            <option value="privacy" {{ old('reason') == 'privacy' ? 'selected' : '' }}>Privacy concerns</option>
                            <option value="found_alternative" {{ old('reason') == 'found_alternative' ? 'selected' : '' }}>Found a better alternative</option>
                            <option value="too_expensive" {{ old('reason') == 'too_expensive' ? 'selected' : '' }}>Subscription is too expensive</option>
                            <option value="technical_issues" {{ old('reason') == 'technical_issues' ? 'selected' : '' }}>Technical issues</option>
                            <option value="other" {{ old('reason') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="feedback" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional feedback (optional)
                        </label>
                        <textarea name="feedback" id="feedback" rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                            placeholder="Tell us how we can improve...">{{ old('feedback') }}</textarea>
                    </div>

                    <div class="flex items-start">
                        <input type="checkbox" name="confirm" id="confirm" required
                            class="mt-1 h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                        <label for="confirm" class="ml-3 text-sm text-gray-600">
                            I understand that this action is permanent and all my data will be deleted.
                            I confirm that I want to delete my BlinkStudy account.
                        </label>
                    </div>

                    <button type="submit" id="submitBtn"
                        class="w-full bg-red-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                        <span id="btnText">Request Account Deletion</span>
                        <svg id="btnSpinner" class="hidden animate-spin ml-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>

                <script>
                    document.getElementById('deletionForm').addEventListener('submit', function() {
                        document.getElementById('submitBtn').disabled = true;
                        document.getElementById('btnText').textContent = 'Submitting...';
                        document.getElementById('btnSpinner').classList.remove('hidden');
                    });
                </script>
                @endif
            </div>

            <!-- Contact Info -->
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>Need help? Contact us at <a href="mailto:support@blinkstudy.in" class="text-teal-600 hover:underline">support@blinkstudy.in</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
