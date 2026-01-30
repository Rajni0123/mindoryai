@extends('layouts.app')

@section('title', 'AI Doubt Solver - Alternative')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">
                🤖 AI Doubt Solver
            </h1>
            <p class="text-lg text-gray-600">
                Alternative Implementation - Direct OpenAI API
            </p>
            <p class="text-sm text-gray-500 mt-1">
                Text, Image & PDF Support
            </p>
        </div>

        <!-- Feature Tabs -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="flex gap-2 p-4 border-b border-gray-200">
                <button onclick="switchMode('text')" id="tab-text" class="mode-btn active px-6 py-3 rounded-lg font-medium transition">
                    📝 Text Doubt
                </button>
                <button onclick="switchMode('image')" id="tab-image" class="mode-btn px-6 py-3 rounded-lg font-medium transition">
                    🖼️ Image Doubt
                </button>
                <button onclick="switchMode('pdf')" id="tab-pdf" class="mode-btn px-6 py-3 rounded-lg font-medium transition">
                    📄 PDF Doubt
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">

            <!-- Text Mode -->
            <div id="mode-text" class="mode-content p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">📝 Ask Your Doubt (Text)</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject (Optional)</label>
                        <select id="text-subject" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Select Subject --</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Physics">Physics</option>
                            <option value="Chemistry">Chemistry</option>
                            <option value="Biology">Biology</option>
                            <option value="English">English</option>
                            <option value="Computer Science">Computer Science</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Your Question *</label>
                        <textarea id="text-question" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Type your doubt here... (e.g., 'What is photosynthesis?' or 'Explain Newton's laws')"></textarea>
                    </div>

                    <button onclick="solveTextDoubt()" class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium text-lg">
                        Get Answer
                    </button>
                </div>

                <!-- Answer Display -->
                <div id="text-answer" class="hidden mt-6">
                    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-6 border-l-4 border-green-500">
                        <h3 class="text-lg font-bold text-gray-900 mb-3">✅ Answer:</h3>
                        <div id="text-answer-content" class="prose prose-sm max-w-none"></div>
                        <p class="text-xs text-gray-500 mt-4">Tokens used: <span id="text-tokens">0</span></p>
                    </div>
                </div>
            </div>

            <!-- Image Mode -->
            <div id="mode-image" class="mode-content hidden p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">🖼️ Upload Image with Problem</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject (Optional)</label>
                        <select id="image-subject" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Select Subject --</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Physics">Physics</option>
                            <option value="Chemistry">Chemistry</option>
                            <option value="Biology">Biology</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Image *</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-indigo-400 transition">
                            <input type="file" id="image-file" accept="image/*" class="hidden" onchange="previewImage(this)">
                            <label for="image-file" class="cursor-pointer">
                                <div id="image-preview-area">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600">Click to upload image</p>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional Question (Optional)</label>
                        <textarea id="image-question" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Any specific question about the image? (optional)"></textarea>
                    </div>

                    <button onclick="solveImageDoubt()" class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium text-lg">
                        Analyze Image
                    </button>
                </div>

                <!-- Answer Display -->
                <div id="image-answer" class="hidden mt-6">
                    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-6 border-l-4 border-green-500">
                        <h3 class="text-lg font-bold text-gray-900 mb-3">✅ Analysis:</h3>
                        <div id="image-answer-content" class="prose prose-sm max-w-none"></div>
                        <p class="text-xs text-gray-500 mt-4">Tokens used: <span id="image-tokens">0</span></p>
                    </div>
                </div>
            </div>

            <!-- PDF Mode -->
            <div id="mode-pdf" class="mode-content hidden p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">📄 Upload PDF Document</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject (Optional)</label>
                        <select id="pdf-subject" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Select Subject --</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Physics">Physics</option>
                            <option value="Chemistry">Chemistry</option>
                            <option value="Biology">Biology</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload PDF *</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-indigo-400 transition">
                            <input type="file" id="pdf-file" accept=".pdf" class="hidden" onchange="showPdfName(this)">
                            <label for="pdf-file" class="cursor-pointer">
                                <div id="pdf-preview-area">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600">Click to upload PDF</p>
                                    <p class="text-xs text-gray-500">PDF up to 10MB</p>
                                    <p id="pdf-file-name" class="text-sm text-indigo-600 font-medium mt-2"></p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Your Question (Optional)</label>
                        <textarea id="pdf-question" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Ask a specific question about the PDF content (optional)"></textarea>
                    </div>

                    <button onclick="solvePdfDoubt()" class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium text-lg">
                        Analyze PDF
                    </button>
                </div>

                <!-- Answer Display -->
                <div id="pdf-answer" class="hidden mt-6">
                    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-6 border-l-4 border-green-500">
                        <h3 class="text-lg font-bold text-gray-900 mb-3">✅ Analysis:</h3>
                        <div id="pdf-answer-content" class="prose prose-sm max-w-none"></div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Info Card -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-bold text-blue-900 mb-2">💡 How to use:</h3>
            <ul class="text-sm text-blue-800 space-y-1">
                <li><strong>Text:</strong> Type any doubt in English or Hindi/Hinglish</li>
                <li><strong>Image:</strong> Upload handwritten notes, diagrams, or equations</li>
                <li><strong>PDF:</strong> Upload study material for concept explanations</li>
            </ul>
        </div>

    </div>
</div>

<!-- Loading Overlay -->
<div id="loading" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 flex items-center gap-4">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
        <span class="text-gray-700 font-medium text-lg">Processing your request...</span>
    </div>
</div>

@endsection

@push('scripts')
<style>
    .mode-btn {
        color: #6B7280;
        background-color: transparent;
    }
    .mode-btn:hover {
        background-color: #F3F4F6;
    }
    .mode-btn.active {
        color: #4F46E5;
        background-color: #EEF2FF;
        font-weight: 600;
        border-bottom: 3px solid #4F46E5;
    }
</style>
<script>
// Switch between modes
function switchMode(mode) {
    // Hide all modes
    document.querySelectorAll('.mode-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.mode-btn').forEach(el => el.classList.remove('active'));

    // Show selected mode
    document.getElementById('mode-' + mode).classList.remove('hidden');
    document.getElementById('tab-' + mode).classList.add('active');
}

// Show loading
function showLoading(show) {
    document.getElementById('loading').classList.toggle('hidden', !show);
}

// Format and display content
function displayContent(elementId, content) {
    const element = document.getElementById(elementId);
    let formatted = content
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/```(.*?)```/gs, '<pre class="bg-gray-800 text-white p-3 rounded my-2 overflow-x-auto"><code>$1</code></pre>')
        .replace(/\n\n/g, '</p><p class="mt-2">')
        .replace(/\n/g, '<br>');
    element.innerHTML = '<p>' + formatted + '</p>';
}

// Solve Text Doubt
function solveTextDoubt() {
    const question = document.getElementById('text-question').value.trim();
    const subject = document.getElementById('text-subject').value;

    if (!question) {
        alert('⚠️ Please enter your question');
        return;
    }

    showLoading(true);
    document.getElementById('text-answer').classList.add('hidden');

    fetch('{{ route("ai.doubt.solve.text") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            question: question,
            subject: subject
        })
    })
    .then(res => res.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            displayContent('text-answer-content', data.answer);
            document.getElementById('text-tokens').textContent = data.tokens_used || 0;
            document.getElementById('text-answer').classList.remove('hidden');
        } else {
            alert('❌ Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        showLoading(false);
        alert('❌ Error: ' + err.message);
    });
}

// Preview Image
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image-preview-area').innerHTML = `
                <img src="${e.target.result}" class="max-h-64 mx-auto rounded-lg border-2 border-gray-300">
                <p class="mt-2 text-sm text-green-600 font-medium">✅ Image uploaded</p>
            `;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Solve Image Doubt
function solveImageDoubt() {
    const fileInput = document.getElementById('image-file');
    const subject = document.getElementById('image-subject').value;
    const question = document.getElementById('image-question').value;

    if (!fileInput.files || !fileInput.files[0]) {
        alert('⚠️ Please upload an image');
        return;
    }

    const formData = new FormData();
    formData.append('image', fileInput.files[0]);
    if (subject) formData.append('subject', subject);
    if (question) formData.append('question', question);

    showLoading(true);
    document.getElementById('image-answer').classList.add('hidden');

    fetch('{{ route("ai.doubt.solve.image") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            displayContent('image-answer-content', data.answer);
            document.getElementById('image-tokens').textContent = data.tokens_used || 0;
            document.getElementById('image-answer').classList.remove('hidden');
        } else {
            alert('❌ Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        showLoading(false);
        alert('❌ Error: ' + err.message);
    });
}

// Show PDF name
function showPdfName(input) {
    if (input.files && input.files[0]) {
        document.getElementById('pdf-file-name').textContent = '✅ ' + input.files[0].name;
    }
}

// Solve PDF Doubt
function solvePdfDoubt() {
    const fileInput = document.getElementById('pdf-file');
    const subject = document.getElementById('pdf-subject').value;
    const question = document.getElementById('pdf-question').value;

    if (!fileInput.files || !fileInput.files[0]) {
        alert('⚠️ Please upload a PDF');
        return;
    }

    const formData = new FormData();
    formData.append('pdf', fileInput.files[0]);
    if (subject) formData.append('subject', subject);
    if (question) formData.append('question', question);

    showLoading(true);
    document.getElementById('pdf-answer').classList.add('hidden');

    fetch('{{ route("ai.doubt.solve.pdf") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            displayContent('pdf-answer-content', data.answer);
            document.getElementById('pdf-answer').classList.remove('hidden');
        } else {
            alert('❌ Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        showLoading(false);
        alert('❌ Error: ' + err.message);
    });
}
</script>
@endpush
