// Image Upload and Question Handler
(function() {
    'use strict';

    // DOM Elements
    const imageInput = document.getElementById('imageInput');
    const questionInput = document.getElementById('questionInput');
    const uploadButton = document.getElementById('uploadButton');
    const analyzeButton = document.getElementById('analyzeButton');
    const buttonText = document.getElementById('buttonText');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const removeImageBtn = document.getElementById('removeImage');
    const uploadForm = document.getElementById('imageUploadForm');
    const chatContainer = document.getElementById('chatContainer');
    const loadingOverlay = document.getElementById('loadingOverlay');

    // Selected image file
    let selectedFile = null;

    // Check for pending question from landing page
    function checkPendingQuestion() {
        const pendingQuestion = sessionStorage.getItem('pending_question');

        if (pendingQuestion && pendingQuestion.trim()) {
            // Set the question in the input
            questionInput.value = pendingQuestion;

            // Clear from sessionStorage
            sessionStorage.removeItem('pending_question');

            // Update button state
            updateButtonState();

            // Auto-submit after a short delay to allow UI to settle
            setTimeout(() => {
                uploadForm.dispatchEvent(new Event('submit'));
            }, 500);
        }
    }

    // Call this on page load
    setTimeout(checkPendingQuestion, 100);

    // Enable/disable button based on input
    function updateButtonState() {
        const hasQuestion = questionInput.value.trim().length > 0;
        const hasImage = selectedFile !== null;

        analyzeButton.disabled = !(hasQuestion || hasImage);

        // Update button text
        if (hasImage && hasQuestion) {
            buttonText.textContent = 'Analyze Image with Question';
        } else if (hasImage) {
            buttonText.textContent = 'Analyze Image';
        } else if (hasQuestion) {
            buttonText.textContent = 'Ask AI';
        } else {
            buttonText.textContent = 'Ask AI / Analyze';
        }
    }

    // Listen to text input changes
    questionInput.addEventListener('input', updateButtonState);

    // Upload button click - trigger file input
    uploadButton.addEventListener('click', () => {
        imageInput.click();
    });

    // File input change - show preview
    imageInput.addEventListener('change', (e) => {
        const file = e.target.files[0];

        if (file) {
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                showNotification('Please select a valid image file (JPG, PNG, GIF, WebP)', 'error');
                return;
            }

            // Validate file size (10MB max)
            const maxSize = 10 * 1024 * 1024; // 10MB in bytes
            if (file.size > maxSize) {
                showNotification('File size must be less than 10MB', 'error');
                return;
            }

            // Store selected file
            selectedFile = file;

            // Show preview
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.src = e.target.result;
                imagePreviewContainer.classList.remove('hidden');
                imagePreviewContainer.classList.add('fade-in');
                updateButtonState();
            };
            reader.readAsDataURL(file);
        }
    });

    // Remove image button
    removeImageBtn.addEventListener('click', () => {
        clearImagePreview();
    });

    // Form submission - analyze image or answer question
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const question = questionInput.value.trim();
        const hasQuestion = question.length > 0;
        const hasImage = selectedFile !== null;

        if (!hasQuestion && !hasImage) {
            showNotification('Please enter a question or upload an image', 'error');
            return;
        }

        // Show loading overlay
        showLoading();

        // Disable analyze button
        analyzeButton.disabled = true;
        const originalText = buttonText.textContent;
        buttonText.textContent = 'Processing...';

        try {
            if (hasImage) {
                // Handle image upload (with optional question)
                await handleImageAnalysis(question);
            } else {
                // Handle text-only question
                await handleTextQuestion(question);
            }
        } catch (error) {
            console.error('Error:', error);
            hideLoading();
            showNotification('An error occurred. Please try again.', 'error');
            analyzeButton.disabled = false;
            buttonText.textContent = originalText;
        }
    });

    // Handle image analysis
    async function handleImageAnalysis(question) {
        const formData = new FormData();
        formData.append('image', selectedFile);
        if (question) {
            formData.append('question', question);
        }
        formData.append('_token', document.querySelector('input[name="_token"]').value);

        const response = await fetch('/analyze-image', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        const data = await response.json();
        hideLoading();

        if (data.success) {
            // Show user message (image uploaded)
            if (question) {
                addUserMessage(question, imagePreview.src);
            } else {
                addUserMessage(null, imagePreview.src);
            }

            // Show AI response
            addAIMessage(data.data.response);

            // Clear inputs
            clearImagePreview();
            questionInput.value = '';
            updateButtonState();

            // Show success notification
            showNotification('Image analyzed successfully! (Will be auto-deleted in 60 seconds)', 'success');
        } else {
            showNotification(data.message || 'Failed to analyze image', 'error');
            analyzeButton.disabled = false;
            buttonText.textContent = 'Ask AI / Analyze';
        }
    }

    // Handle text-only question
    async function handleTextQuestion(question) {
        const formData = new FormData();
        formData.append('question', question);
        formData.append('_token', document.querySelector('input[name="_token"]').value);

        const response = await fetch('/ask-question', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        const data = await response.json();
        hideLoading();

        if (data.success) {
            // Show user message
            addUserMessage(question, null);

            // Show AI response
            addAIMessage(data.data.response);

            // Clear input
            questionInput.value = '';
            updateButtonState();

            // Show success notification
            showNotification('Question answered successfully!', 'success');
        } else {
            showNotification(data.message || 'Failed to get answer', 'error');
            analyzeButton.disabled = false;
            buttonText.textContent = 'Ask AI / Analyze';
        }
    }

    // Clear image preview
    function clearImagePreview() {
        imageInput.value = '';
        selectedFile = null;
        imagePreview.src = '';
        imagePreviewContainer.classList.add('hidden');
        updateButtonState();
    }

    // Add user message to chat
    function addUserMessage(text, imageSrc) {
        let messageContent = '';

        if (text && imageSrc) {
            messageContent = `
                <div class="inline-block bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-md p-3">
                    <p class="text-white mb-2">${escapeHtml(text)}</p>
                    <img src="${imageSrc}" alt="Uploaded" class="max-h-48 rounded">
                </div>
            `;
        } else if (imageSrc) {
            messageContent = `
                <div class="inline-block bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-md p-3">
                    <img src="${imageSrc}" alt="Uploaded" class="max-h-48 rounded">
                </div>
            `;
        } else {
            messageContent = `
                <div class="inline-block bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-md p-4">
                    <p class="text-white">${escapeHtml(text)}</p>
                </div>
            `;
        }

        const messageHTML = `
            <div class="flex items-start space-x-3 justify-end fade-in">
                <div class="flex-1 text-right">
                    ${messageContent}
                </div>
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>
            </div>
        `;

        chatContainer.insertAdjacentHTML('beforeend', messageHTML);
        scrollToBottom();
    }

    // Add AI message to chat
    function addAIMessage(text) {
        const messageHTML = `
            <div class="flex items-start space-x-3 fade-in">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                        <p class="text-gray-800 leading-relaxed whitespace-pre-line">${escapeHtml(text)}</p>
                    </div>
                </div>
            </div>
        `;

        chatContainer.insertAdjacentHTML('beforeend', messageHTML);
        scrollToBottom();
    }

    // Show loading overlay
    function showLoading() {
        loadingOverlay.classList.remove('hidden');
    }

    // Hide loading overlay
    function hideLoading() {
        loadingOverlay.classList.add('hidden');
    }

    // Scroll chat to bottom
    function scrollToBottom() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    // Show notification
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 max-w-md p-4 rounded-lg shadow-lg fade-in ${
            type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'
        } text-white`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})();
