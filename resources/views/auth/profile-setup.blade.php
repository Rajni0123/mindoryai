<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-black min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-gray-900 rounded-2xl shadow-2xl p-8 border border-gray-800">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-green-500 bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-icons text-green-500 text-4xl">account_circle</span>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">Profile information</h1>
                <p class="text-gray-400 text-sm">Manage your basic profile details</p>
            </div>

            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-500 bg-opacity-10 border border-green-500 rounded-lg">
                    <p class="text-green-400 text-sm">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('profile.setup.update') }}" method="POST" id="profileForm">
                @csrf

                <!-- Full Name -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-semibold text-gray-300 mb-2">
                        Full name
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $user->name) }}"
                           class="w-full px-4 py-3.5 bg-gray-800 bg-opacity-50 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                           placeholder="e.g. John Smith"
                           required
                           autocomplete="name">
                    @error('name')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone Number -->
                <div class="mb-8">
                    <label for="mobile" class="block text-sm font-semibold text-gray-300 mb-2">
                        Phone
                    </label>
                    <div class="flex gap-3">
                        <div class="flex items-center gap-2 px-4 py-3.5 bg-gray-800 bg-opacity-50 border border-gray-700 rounded-xl">
                            <span class="text-xl">🇮🇳</span>
                            <span class="text-white font-medium text-sm">+91</span>
                            <span class="material-icons text-gray-400 text-sm">arrow_drop_down</span>
                        </div>
                        <input type="tel"
                               id="mobile"
                               name="mobile"
                               value="{{ old('mobile', $user->mobile) }}"
                               class="flex-1 px-4 py-3.5 bg-gray-800 bg-opacity-50 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition {{ $user->mobile ? 'opacity-60 cursor-not-allowed' : '' }}"
                               placeholder="e.g. 98765 43210"
                               maxlength="10"
                               pattern="[6-9][0-9]{9}"
                               autocomplete="tel"
                               {{ $user->mobile ? 'readonly' : '' }}>
                    </div>
                    @if($user->mobile)
                        <p class="mt-2 text-xs text-gray-500 italic">Phone number is pre-filled from your login</p>
                    @endif
                    @error('mobile')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="w-full bg-green-500 hover:bg-green-600 text-black font-semibold py-3.5 px-4 rounded-xl transition duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:ring-offset-gray-900">
                    Update profile
                </button>

                <!-- Skip Button (for email/google users) -->
                @if($user->email && !$user->mobile)
                <button type="button"
                        onclick="skipProfile()"
                        class="w-full mt-3 bg-transparent hover:bg-gray-800 text-gray-400 hover:text-white font-semibold py-3.5 px-4 rounded-xl border border-gray-700 hover:border-gray-600 transition duration-200">
                    Skip for now
                </button>
                @endif
            </form>
        </div>
    </div>

    <script>
        function skipProfile() {
            if (confirm('Are you sure you want to skip profile setup? You can complete it later from settings.')) {
                window.location.href = @json(\App\Support\ChatSubdomainUrl::appUrl());
            }
        }

        // Validate phone number on input
        const mobileInput = document.getElementById('mobile');
        if (mobileInput && !mobileInput.hasAttribute('readonly')) {
            mobileInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    </script>
</body>
</html>
