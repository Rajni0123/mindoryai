<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Testimonials Management | Mindory</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #09090b;
            min-height: 100vh;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-effect:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .star-rating {
            display: inline-flex;
            gap: 2px;
        }

        .star {
            color: #fbbf24;
            font-size: 18px;
        }

        .star-input {
            cursor: pointer;
            transition: transform 0.2s;
        }

        .star-input:hover {
            transform: scale(1.2);
        }
    </style>
</head>
<body class="text-white">
    <div class="min-h-screen p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white">
                    Testimonials Management
                </h1>
                <p class="text-gray-400 mt-2 text-sm">Manage user testimonials displayed in mobile app</p>
            </div>

            <!-- Success Message -->
            @if(session('success'))
            <div class="mb-6 p-4 bg-green-500/10 border border-green-500/30 rounded-lg flex items-center gap-3">
                <span class="material-icons-outlined text-green-400">check_circle</span>
                <p class="text-green-300">{{ session('success') }}</p>
            </div>
            @endif

            <!-- Add/Edit Form -->
            <div class="glass-effect rounded-xl p-6 mb-8">
                <h2 class="text-xl font-semibold mb-6" id="formTitle">Add New Testimonial</h2>
                <form id="testimonialForm" action="{{ route('admin.testimonials.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" value="POST" id="formMethod">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- User Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                User Name <span class="text-red-400">*</span>
                            </label>
                            <input type="text"
                                   name="user_name"
                                   id="user_name"
                                   required
                                   class="w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors"
                                   placeholder="Enter user name">
                            @error('user_name')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Rating -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Rating <span class="text-red-400">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="rating" id="rating" value="5">
                                <div class="star-rating" id="starRating">
                                    <span class="material-icons-outlined star star-input" onclick="setRating(1)">star</span>
                                    <span class="material-icons-outlined star star-input" onclick="setRating(2)">star</span>
                                    <span class="material-icons-outlined star star-input" onclick="setRating(3)">star</span>
                                    <span class="material-icons-outlined star star-input" onclick="setRating(4)">star</span>
                                    <span class="material-icons-outlined star star-input" onclick="setRating(5)">star</span>
                                </div>
                                <span class="text-gray-400 text-sm ml-2" id="ratingText">5 stars</span>
                            </div>
                        </div>

                        <!-- Order -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Display Order
                            </label>
                            <input type="number"
                                   name="order"
                                   id="order"
                                   min="0"
                                   value="0"
                                   class="w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors"
                                   placeholder="0">
                            <p class="text-gray-400 text-xs mt-1">Lower numbers appear first</p>
                        </div>

                        <!-- User Photo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                User Photo
                            </label>
                            <input type="file"
                                   name="user_photo"
                                   id="user_photo"
                                   accept="image/*"
                                   onchange="previewImage(event)"
                                   class="w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-500 file:text-white hover:file:bg-blue-600">
                            <p class="text-gray-400 text-xs mt-1">Max 2MB, formats: JPG, PNG, WEBP</p>
                            <img id="imagePreview" class="mt-2 rounded-lg max-h-32 hidden">
                        </div>

                        <!-- Active Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Status
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       name="is_active"
                                       id="is_active"
                                       checked
                                       class="w-5 h-5 rounded bg-white/5 border-white/10 text-blue-500 focus:ring-blue-500 focus:ring-offset-0">
                                <span class="text-gray-300">Active (visible in app)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Testimonial Message <span class="text-red-400">*</span>
                        </label>
                        <textarea name="message"
                                  id="message"
                                  required
                                  rows="4"
                                  maxlength="500"
                                  class="w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors"
                                  placeholder="Enter testimonial message (max 500 characters)"></textarea>
                        <p class="text-gray-400 text-xs mt-1">
                            <span id="charCount">0</span> / 500 characters
                        </p>
                        @error('message')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Form Buttons -->
                    <div class="flex items-center gap-3 mt-6">
                        <button type="submit"
                                class="px-5 py-2.5 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                            <span class="material-icons-outlined text-sm">save</span>
                            <span id="submitText">Save Testimonial</span>
                        </button>
                        <button type="button"
                                onclick="resetForm()"
                                class="px-5 py-2.5 bg-gray-500/10 hover:bg-gray-500/20 text-gray-300 border border-gray-500/20 font-medium rounded-lg transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Testimonials List -->
            <div class="glass-effect rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-white/5">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Order</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">User</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Rating</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Message</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($testimonials as $testimonial)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-500/20 text-purple-400 font-semibold text-sm">
                                        {{ $testimonial->order }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($testimonial->user_photo)
                                        <img src="{{ $testimonial->user_photo }}"
                                             alt="{{ $testimonial->user_name }}"
                                             class="w-10 h-10 rounded-full object-cover">
                                        @else
                                        <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center">
                                            <span class="material-icons-outlined text-blue-400 text-sm">person</span>
                                        </div>
                                        @endif
                                        <span class="font-medium text-white">{{ $testimonial->user_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="star-rating">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span class="material-icons-outlined star" style="font-size: 16px; {{ $i <= $testimonial->rating ? '' : 'opacity: 0.3;' }}">star</span>
                                        @endfor
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-md">
                                        <p class="text-gray-400 text-sm truncate">{{ Str::limit($testimonial->message, 80) }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($testimonial->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-500/10 text-green-400 rounded-full text-xs font-medium">
                                        <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                                        Active
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-500/10 text-gray-400 rounded-full text-xs font-medium">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                        Inactive
                                    </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick="editTestimonial({{ $testimonial->id }}, '{{ addslashes($testimonial->user_name) }}', {{ $testimonial->rating }}, '{{ addslashes($testimonial->message) }}', {{ $testimonial->order }}, {{ $testimonial->is_active ? 'true' : 'false' }}, '{{ $testimonial->user_photo }}')"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-lg hover:bg-blue-500/20 transition-colors text-sm font-medium">
                                            <span class="material-icons-outlined text-sm">edit</span>
                                            Edit
                                        </button>
                                        <button onclick="toggleStatus({{ $testimonial->id }})"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 rounded-lg hover:bg-yellow-500/20 transition-colors text-sm font-medium"
                                                title="Toggle status">
                                            <span class="material-icons-outlined text-sm">{{ $testimonial->is_active ? 'toggle_on' : 'toggle_off' }}</span>
                                        </button>
                                        <form action="{{ route('admin.testimonials.destroy', $testimonial) }}"
                                              method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this testimonial?');"
                                              class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-500/10 text-red-400 border border-red-500/20 rounded-lg hover:bg-red-500/20 transition-colors text-sm font-medium">
                                                <span class="material-icons-outlined text-sm">delete</span>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <span class="material-icons-outlined text-6xl text-gray-700 mb-4 block">star_border</span>
                                    <p class="text-gray-400 text-lg mb-4">No testimonials found</p>
                                    <p class="text-gray-500 text-sm">Add your first testimonial using the form above</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($testimonials->hasPages())
                <div class="px-6 py-4 border-t border-white/5">
                    {{ $testimonials->links() }}
                </div>
                @endif
            </div>

            <!-- Back to Dashboard -->
            <div class="mt-8">
                <a href="{{ route('admin.dashboard') }}"
                   class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition-colors">
                    <span class="material-icons-outlined">arrow_back</span>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        let currentEditId = null;

        // Character counter
        const messageInput = document.getElementById('message');
        const charCount = document.getElementById('charCount');

        messageInput.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });

        // Star rating
        function setRating(rating) {
            document.getElementById('rating').value = rating;
            document.getElementById('ratingText').textContent = rating + ' star' + (rating > 1 ? 's' : '');

            const stars = document.querySelectorAll('#starRating .star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.style.opacity = '1';
                } else {
                    star.style.opacity = '0.3';
                }
            });
        }

        // Image preview
        function previewImage(event) {
            const preview = document.getElementById('imagePreview');
            const file = event.target.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('hidden');
            }
        }

        // Edit testimonial
        function editTestimonial(id, userName, rating, message, order, isActive, userPhoto) {
            currentEditId = id;

            // Update form
            document.getElementById('formTitle').textContent = 'Edit Testimonial';
            document.getElementById('submitText').textContent = 'Update Testimonial';
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('testimonialForm').action = '{{ route('admin.testimonials.update', ':id') }}'.replace(':id', id);

            // Fill form fields
            document.getElementById('user_name').value = userName;
            document.getElementById('message').value = message;
            document.getElementById('order').value = order;
            document.getElementById('is_active').checked = isActive;

            // Update character count
            charCount.textContent = message.length;

            // Set rating
            setRating(rating);

            // Show existing photo
            if (userPhoto) {
                const preview = document.getElementById('imagePreview');
                preview.src = userPhoto;
                preview.classList.remove('hidden');
            }

            // Scroll to form
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Reset form
        function resetForm() {
            currentEditId = null;

            document.getElementById('formTitle').textContent = 'Add New Testimonial';
            document.getElementById('submitText').textContent = 'Save Testimonial';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('testimonialForm').action = '{{ route('admin.testimonials.store') }}';
            document.getElementById('testimonialForm').reset();
            document.getElementById('imagePreview').classList.add('hidden');

            charCount.textContent = '0';
            setRating(5);
        }

        // Toggle status
        function toggleStatus(id) {
            if (!confirm('Are you sure you want to toggle this testimonial status?')) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('admin.testimonials.toggle', ':id') }}'.replace(':id', id);

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }

        // Initialize rating display
        setRating(5);
    </script>
</body>
</html>
