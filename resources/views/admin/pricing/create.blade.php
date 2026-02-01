<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Plan | Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        select, select option { background-color: #1a1a2e; color: #e5e7eb; }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        @include('admin.partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.pricing.index') }}" class="text-gray-500 hover:text-white text-xs mb-1 inline-flex items-center gap-1">
                            <span class="material-icons-outlined text-sm">arrow_back</span>
                            Back to Pricing
                        </a>
                        <h1 class="text-base font-semibold text-white">Create New Plan</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Add a new pricing plan with usage limits</p>
                    </div>
                </div>
            </header>

            <div class="p-6">
                @if($errors->any())
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg">
                    <ul class="list-disc list-inside text-red-300 text-sm">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('admin.pricing.store') }}" method="POST" class="max-w-4xl space-y-5">
                    @csrf

                    {{-- Section 1: Basic Info --}}
                    <div class="card rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-blue-400 mb-3 flex items-center gap-1.5">
                            <span class="material-icons-outlined text-base">info</span>
                            Basic Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Plan Name *</label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition"
                                       placeholder="e.g., Basic, Pro">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Display Order</label>
                                <input type="number" name="order" value="{{ old('order', 0) }}" min="0" required
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Price (Rs) *</label>
                                <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0" required
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition"
                                       placeholder="0">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Billing Period *</label>
                                <select name="billing_period" required
                                        class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                                    <option value="month" {{ old('billing_period') === 'month' ? 'selected' : '' }}>Monthly</option>
                                    <option value="year" {{ old('billing_period') === 'year' ? 'selected' : '' }}>Yearly</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Validity (Days)</label>
                                <input type="number" name="validity_days" value="{{ old('validity_days', 30) }}" min="0"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                                <p class="text-[10px] text-gray-500 mt-0.5">0 = lifetime</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Billing Description</label>
                                <input type="text" name="billing_description" value="{{ old('billing_description') }}"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition"
                                       placeholder="Billed monthly">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="block text-xs font-medium text-gray-400 mb-1">Description</label>
                            <textarea name="description" rows="2"
                                      class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition"
                                      placeholder="Plan description">{{ old('description') }}</textarea>
                        </div>
                    </div>

                    {{-- Section 2: Display Features (public pages) --}}
                    <div class="card rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-blue-400 mb-1 flex items-center gap-1.5">
                            <span class="material-icons-outlined text-base">checklist</span>
                            Display Features
                        </h3>
                        <p class="text-[10px] text-gray-500 mb-3">Text features shown on website/mobile pricing page</p>

                        <div id="features-container" class="space-y-2">
                            <div class="flex gap-2 feature-row">
                                <input type="text" name="display_features[]" value="{{ old('display_features.0') }}"
                                       class="flex-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition"
                                       placeholder="e.g., Unlimited quizzes per day">
                                <button type="button" onclick="removeRow(this, 'features-container', 'feature-row')"
                                        class="px-2 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition-colors">
                                    <span class="material-icons-outlined text-sm">close</span>
                                </button>
                            </div>
                        </div>
                        <button type="button" onclick="addFeatureRow()"
                                class="mt-2 inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500/10 text-blue-400 rounded-lg hover:bg-blue-500/20 transition-colors text-xs">
                            <span class="material-icons-outlined text-sm">add</span>
                            Add Feature
                        </button>
                    </div>

                    {{-- Section 3: Usage Limits (dynamic) --}}
                    <div class="card rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-blue-400 mb-1 flex items-center gap-1.5">
                            <span class="material-icons-outlined text-base">tune</span>
                            Usage Limits
                        </h3>
                        <p class="text-[10px] text-gray-500 mb-3">-1 = unlimited, 0 = disabled, N = limit per day/month</p>

                        <div id="limits-container" class="space-y-2">
                            {{-- Default known limits --}}
                            @php
                                $defaultLimits = [
                                    ['key' => 'quiz_per_day', 'label' => 'Quiz / Day', 'value' => 0],
                                    ['key' => 'quiz_per_month', 'label' => 'Quiz / Month', 'value' => 0],
                                    ['key' => 'topic_quiz_per_day', 'label' => 'Topic Quiz / Day', 'value' => 0],
                                    ['key' => 'topic_quiz_per_month', 'label' => 'Topic Quiz / Month', 'value' => 0],
                                    ['key' => 'whiteboard_videos_per_day', 'label' => 'Whiteboard Videos / Day', 'value' => 0],
                                    ['key' => 'whiteboard_videos_per_month', 'label' => 'Whiteboard Videos / Month', 'value' => 0],
                                    ['key' => 'exam_prep_per_day', 'label' => 'Exam Prep / Day', 'value' => 0],
                                    ['key' => 'exam_prep_per_month', 'label' => 'Exam Prep / Month', 'value' => 0],
                                    ['key' => 'scan_solve_per_day', 'label' => 'Scan & Solve / Day', 'value' => 0],
                                    ['key' => 'scan_solve_per_month', 'label' => 'Scan & Solve / Month', 'value' => 0],
                                    ['key' => 'pdf_uploads_per_day', 'label' => 'PDF Uploads / Day', 'value' => 0],
                                    ['key' => 'pdf_uploads_per_month', 'label' => 'PDF Uploads / Month', 'value' => 0],
                                ];
                            @endphp
                            @foreach($defaultLimits as $i => $limit)
                            <div class="flex gap-2 items-center limit-row">
                                <select name="limit_keys[]"
                                        class="w-[240px] px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition limit-key-select"
                                        onchange="handleLimitKeyChange(this)">
                                    @foreach($defaultLimits as $opt)
                                    <option value="{{ $opt['key'] }}" {{ $limit['key'] === $opt['key'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                    @endforeach
                                    <option value="__custom__">-- Custom Key --</option>
                                </select>
                                <input type="text" class="hidden custom-key-input w-[200px] px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition"
                                       placeholder="custom_key_per_day">
                                <input type="number" name="limit_values[]" value="{{ old("limit_values.{$i}", $limit['value']) }}" min="-1"
                                       class="w-[100px] px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition text-center"
                                       placeholder="0">
                                <span class="text-[10px] text-gray-500 w-[60px]">
                                    @if(str_contains($limit['key'], 'month'))
                                        /month
                                    @else
                                        /day
                                    @endif
                                </span>
                                <button type="button" onclick="removeRow(this, 'limits-container', 'limit-row')"
                                        class="px-2 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition-colors">
                                    <span class="material-icons-outlined text-sm">close</span>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" onclick="addLimitRow()"
                                class="mt-2 inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500/10 text-blue-400 rounded-lg hover:bg-blue-500/20 transition-colors text-xs">
                            <span class="material-icons-outlined text-sm">add</span>
                            Add Limit
                        </button>
                    </div>

                    {{-- Section 4: Plan Config --}}
                    <div class="card rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-blue-400 mb-3 flex items-center gap-1.5">
                            <span class="material-icons-outlined text-base">settings</span>
                            Plan Config
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">Max Video (sec)</label>
                                <input type="number" name="max_video_length_seconds" value="{{ old('max_video_length_seconds', 30) }}" min="0"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">Frames/Video</label>
                                <input type="number" name="frames_per_video" value="{{ old('frames_per_video', 5) }}" min="1"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">Pages/PDF</label>
                                <input type="number" name="pages_per_pdf" value="{{ old('pages_per_pdf', 5) }}" min="1"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1">History (days)</label>
                                <input type="number" name="history_days" value="{{ old('history_days', 3) }}" min="0"
                                       class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition">
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="watermark" value="1" {{ old('watermark', true) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Watermark</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="ads" value="1" {{ old('ads', true) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Show Ads</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="priority_queue" value="1" {{ old('priority_queue') ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Priority Queue</span>
                            </label>
                        </div>
                    </div>

                    {{-- Section 5: Status --}}
                    <div class="card rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-blue-400 mb-3 flex items-center gap-1.5">
                            <span class="material-icons-outlined text-base">toggle_on</span>
                            Status & Flags
                        </h3>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Active</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="popular" value="1" {{ old('popular') ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Popular Badge</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="recommended" value="1" {{ old('recommended') ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Recommended</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="unlimited_credits" value="1" {{ old('unlimited_credits') ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-600 bg-white/10 text-blue-500 focus:ring-blue-500">
                                <span class="text-xs text-gray-300">Unlimited Credits</span>
                            </label>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                                class="px-5 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                            <span class="material-icons-outlined text-sm">save</span>
                            Create Plan
                        </button>
                        <a href="{{ route('admin.pricing.index') }}"
                           class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-gray-300 text-sm rounded-lg transition-colors">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        const knownLimits = [
            { key: 'quiz_per_day', label: 'Quiz / Day' },
            { key: 'quiz_per_month', label: 'Quiz / Month' },
            { key: 'topic_quiz_per_day', label: 'Topic Quiz / Day' },
            { key: 'topic_quiz_per_month', label: 'Topic Quiz / Month' },
            { key: 'whiteboard_videos_per_day', label: 'Whiteboard Videos / Day' },
            { key: 'whiteboard_videos_per_month', label: 'Whiteboard Videos / Month' },
            { key: 'exam_prep_per_day', label: 'Exam Prep / Day' },
            { key: 'exam_prep_per_month', label: 'Exam Prep / Month' },
            { key: 'scan_solve_per_day', label: 'Scan & Solve / Day' },
            { key: 'scan_solve_per_month', label: 'Scan & Solve / Month' },
            { key: 'pdf_uploads_per_day', label: 'PDF Uploads / Day' },
            { key: 'pdf_uploads_per_month', label: 'PDF Uploads / Month' },
        ];

        function addFeatureRow() {
            const c = document.getElementById('features-container');
            const div = document.createElement('div');
            div.className = 'flex gap-2 feature-row';
            div.innerHTML = `
                <input type="text" name="display_features[]"
                       class="flex-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition"
                       placeholder="e.g., 10 quizzes per day">
                <button type="button" onclick="removeRow(this, 'features-container', 'feature-row')"
                        class="px-2 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition-colors">
                    <span class="material-icons-outlined text-sm">close</span>
                </button>`;
            c.appendChild(div);
        }

        function addLimitRow() {
            const c = document.getElementById('limits-container');
            const div = document.createElement('div');
            div.className = 'flex gap-2 items-center limit-row';
            let options = knownLimits.map(l => `<option value="${l.key}">${l.label}</option>`).join('');
            options += '<option value="__custom__">-- Custom Key --</option>';
            div.innerHTML = `
                <select name="limit_keys[]" class="w-[240px] px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition limit-key-select" onchange="handleLimitKeyChange(this)">
                    ${options}
                </select>
                <input type="text" class="hidden custom-key-input w-[200px] px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition" placeholder="custom_key_per_day">
                <input type="number" name="limit_values[]" value="0" min="-1"
                       class="w-[100px] px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm focus:border-blue-500 outline-none transition text-center" placeholder="0">
                <span class="text-[10px] text-gray-500 w-[60px]">/day</span>
                <button type="button" onclick="removeRow(this, 'limits-container', 'limit-row')"
                        class="px-2 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition-colors">
                    <span class="material-icons-outlined text-sm">close</span>
                </button>`;
            c.appendChild(div);
        }

        function handleLimitKeyChange(select) {
            const row = select.closest('.limit-row');
            const customInput = row.querySelector('.custom-key-input');
            const periodLabel = row.querySelector('span');
            if (select.value === '__custom__') {
                customInput.classList.remove('hidden');
                customInput.name = 'limit_keys[]';
                select.name = '';
            } else {
                customInput.classList.add('hidden');
                customInput.name = '';
                select.name = 'limit_keys[]';
                periodLabel.textContent = select.value.includes('month') ? '/month' : '/day';
            }
        }

        function removeRow(btn, containerId, rowClass) {
            const c = document.getElementById(containerId);
            const rows = c.querySelectorAll('.' + rowClass);
            if (rows.length > 1) {
                btn.closest('.' + rowClass).remove();
            }
        }
    </script>
</body>
</html>
