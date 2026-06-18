<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Select Your Class - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
        }

        .class-card {
            background: linear-gradient(145deg, #2a2a2e 0%, #35353a 100%);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(16, 185, 129, 0.3);
            border-color: rgba(16, 185, 129, 0.5);
        }

        .class-card.selected {
            border-color: #10b981;
            background: linear-gradient(145deg, #3a3a3e 0%, #45454a 100%);
        }

        .submit-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transition: all 0.3s ease;
        }

        .submit-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.4), 0 4px 8px rgba(5, 150, 105, 0.3);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-4xl">
        <!-- Selection Card -->
        <div class="class-card rounded-2xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Welcome! Select Your Class</h1>
                <p class="text-gray-400 text-sm">Choose your current class to get personalized AI assistance</p>
            </div>

            <!-- Alert Messages -->
            <div id="message-container" class="hidden mb-4"></div>

            <!-- Class Selection Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
                <!-- Class 1 -->
                <div class="class-option class-card rounded-xl p-6 border-2 border-gray-700" onclick="selectClass('Class 1')">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-400 mb-2">1st</div>
                        <div class="text-sm text-gray-400">Class</div>
                    </div>
                </div>

                <!-- Class 2 -->
                <div class="class-option class-card rounded-xl p-6 border-2 border-gray-700" onclick="selectClass('Class 2')">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-400 mb-2">2nd</div>
                        <div class="text-sm text-gray-400">Class</div>
                    </div>
                </div>

                <!-- Class 3 -->
                <div class="class-option class-card rounded-xl p-6 border-2 border-gray-700" onclick="selectClass('Class 3')">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-400 mb-2">3rd</div>
                        <div class="text-sm text-gray-400">Class</div>
                    </div>
                </div>

                <!-- Class 4 -->
                <div class="class-option class-card rounded-xl p-6 border-2 border-gray-700" onclick="selectClass('Class 4')">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-400 mb-2">4th</div>
                        <div class="text-sm text-gray-400">Class</div>
                    </div>
                </div>

                <!-- Class 5 -->
                <div class="class-option class-card rounded-xl p-6 border-2 border-gray-700" onclick="selectClass('Class 5')">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-400 mb-2">5th</div>
                        <div class="text-sm text-gray-400">Class</div>
                    </div>
                </div>

                <!-- Class 6 -->
                <div class="class-option class-card rounded-xl p-6 border-2 border-gray-700" onclick="selectClass('Class 6')">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-400 mb-2">6th</div>
                        <div class="text-sm text-gray-400">Class</div>
                    </div>
                </div>

                <!-- Class 7 -->
                <div class="class-option class-card rounded-xl p-6 border-2 border-gray-700" onclick="selectClass('Class 7')">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-400 mb-2">7th</div>
                        <div class="text-sm text-gray-400">Class</div>
                    </div>
                </div>

                <!-- Class 8 -->
                <div class="class-option class-card rounded-xl p-6 border-2 border-gray-700" onclick="selectClass('Class 8')">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-400 mb-2">8th</div>
                        <div class="text-sm text-gray-400">Class</div>
                    </div>
                </div>

                <!-- Class 9 -->
                <div class="class-option class-card rounded-xl p-6 border-2 border-gray-700" onclick="selectClass('Class 9')">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-400 mb-2">9th</div>
                        <div class="text-sm text-gray-400">Class</div>
                    </div>
                </div>

                <!-- Class 10 -->
                <div class="class-option class-card rounded-xl p-6 border-2 border-gray-700" onclick="selectClass('Class 10')">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-400 mb-2">10th</div>
                        <div class="text-sm text-gray-400">Class</div>
                    </div>
                </div>

                <!-- Class 11 -->
                <div class="class-option class-card rounded-xl p-6 border-2 border-gray-700" onclick="selectClass('Class 11')">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-400 mb-2">11th</div>
                        <div class="text-sm text-gray-400">Class</div>
                    </div>
                </div>

                <!-- Class 12 -->
                <div class="class-option class-card rounded-xl p-6 border-2 border-gray-700" onclick="selectClass('Class 12')">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-400 mb-2">12th</div>
                        <div class="text-sm text-gray-400">Class</div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button
                type="button"
                id="submit-btn"
                onclick="submitClass()"
                disabled
                class="submit-btn w-full py-4 px-4 rounded-lg text-white font-semibold text-lg"
            >
                Continue to Dashboard
            </button>

            <!-- Skip Option -->
            <div class="text-center mt-4">
                <button type="button" onclick="skipClassSelection()" class="text-sm text-gray-400 hover:text-white transition-colors">
                    Skip for now
                </button>
            </div>
        </div>
    </div>

    <script>
        let selectedClass = null;

        function showMessage(message, type = 'error') {
            const container = document.getElementById('message-container');
            container.className = `mb-4 p-3 border rounded-lg text-sm ${
                type === 'success'
                    ? 'bg-green-500/20 border-green-500/50 text-green-300'
                    : 'bg-red-500/20 border-red-500/50 text-red-300'
            }`;
            container.textContent = message;
            container.classList.remove('hidden');
        }

        function hideMessage() {
            document.getElementById('message-container').classList.add('hidden');
        }

        function selectClass(className) {
            hideMessage();
            selectedClass = className;

            // Remove selected class from all options
            document.querySelectorAll('.class-option').forEach(el => {
                el.classList.remove('selected');
            });

            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');

            // Enable submit button
            document.getElementById('submit-btn').disabled = false;
        }

        async function submitClass() {
            if (!selectedClass) {
                showMessage('Please select a class');
                return;
            }

            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

            try {
                const response = await fetch('{{ route("class.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ student_class: selectedClass })
                });

                const data = await response.json();

                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = '{{ route("chat") }}';
                    }, 1000);
                } else {
                    showMessage(data.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Continue to Dashboard';
                }
            } catch (error) {
                showMessage('Failed to save class. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Continue to Dashboard';
            }
        }

        function skipClassSelection() {
            window.location.href = '{{ route("chat") }}';
        }
    </script>
</body>
</html>
