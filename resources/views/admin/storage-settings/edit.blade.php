<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Storage | Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .card:hover { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.12); }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        @include('admin.partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.storage-settings.index') }}" class="text-gray-400 hover:text-white transition-colors">
                        <span class="material-icons-outlined">arrow_back</span>
                    </a>
                    <div>
                        <h1 class="text-base font-semibold text-white">Edit Storage: {{ $storageSetting->name }}</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Update Cloudflare R2 / S3 Storage Configuration</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                <div class="max-w-4xl mx-auto">
                    <div class="card rounded-lg p-6">
                        <form method="POST" action="{{ route('admin.storage-settings.update', $storageSetting) }}">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-2 gap-6">
                                <!-- Basic Settings -->
                                <div>
                                    <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                        <span class="material-icons-outlined text-blue-400 text-sm">settings</span>
                                        Basic Configuration
                                    </h3>

                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Storage Name <span class="text-red-400">*</span></label>
                                            <input type="text" name="name" value="{{ old('name', $storageSetting->name) }}" required placeholder="e.g., Cloudflare R2 Production"
                                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                                            @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Storage Driver <span class="text-red-400">*</span></label>
                                            <select name="driver" required
                                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-blue-500">
                                                <option value="r2" {{ old('driver', $storageSetting->driver) === 'r2' ? 'selected' : '' }}>Cloudflare R2</option>
                                                <option value="s3" {{ old('driver', $storageSetting->driver) === 's3' ? 'selected' : '' }}>AWS S3</option>
                                                <option value="local" {{ old('driver', $storageSetting->driver) === 'local' ? 'selected' : '' }}>Local Storage</option>
                                            </select>
                                            @error('driver') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Access Key ID / R2 Token ID</label>
                                            <input type="text" name="key" value="{{ old('key', $storageSetting->key) }}" placeholder="Your R2 Account ID or Access Key"
                                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                                            <p class="text-xs text-gray-500 mt-1">For R2: Use your Account ID from R2 dashboard</p>
                                            @error('key') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Secret Access Key / R2 API Token</label>
                                            <input type="password" name="secret" value="{{ old('secret', $storageSetting->secret) }}" placeholder="Leave empty to keep current"
                                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                                            <p class="text-xs text-gray-500 mt-1">Leave empty to keep current value. Keep this secure!</p>
                                            @error('secret') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Region</label>
                                            <input type="text" name="region" value="{{ old('region', $storageSetting->region) }}" placeholder="auto"
                                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                                            <p class="text-xs text-gray-500 mt-1">Default: auto (for R2)</p>
                                            @error('region') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Bucket & Endpoint -->
                                <div>
                                    <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                        <span class="material-icons-outlined text-purple-400 text-sm">dns</span>
                                        Bucket & Endpoint
                                    </h3>

                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Bucket Name <span class="text-red-400">*</span></label>
                                            <input type="text" name="bucket" value="{{ old('bucket', $storageSetting->bucket) }}" required placeholder="e.g., mindory-uploads"
                                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                                            @error('bucket') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Endpoint URL <span class="text-red-400">*</span></label>
                                            <input type="url" name="endpoint" value="{{ old('endpoint', $storageSetting->endpoint) }}" required placeholder="https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com"
                                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                                            <p class="text-xs text-gray-500 mt-1">For R2: <code class="px-1 py-0.5 bg-gray-800 rounded">https://&lt;account_id&gt;.r2.cloudflarestorage.com</code></p>
                                            @error('endpoint') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Custom Domain / CDN URL (Optional)</label>
                                            <input type="url" name="cdn_url" value="{{ old('cdn_url', $storageSetting->cdn_url) }}" placeholder="https://files.mindory.in"
                                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                                            <p class="text-xs text-gray-500 mt-1">Optional: Use a custom domain for your R2 bucket</p>
                                            @error('cdn_url') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 mb-1.5">URL Prefix (Optional)</label>
                                            <input type="text" name="url_prefix" value="{{ old('url_prefix', $storageSetting->url_prefix) }}" placeholder="uploads"
                                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                                            <p class="text-xs text-gray-500 mt-1">Optional: Add a prefix to all file URLs</p>
                                            @error('url_prefix') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- File Settings -->
                            <div class="mt-6 pt-6 border-t border-gray-800">
                                <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                                    <span class="material-icons-outlined text-green-400 text-sm">attach_file</span>
                                    File Upload Settings
                                </h3>

                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Maximum File Size (MB)</label>
                                        <input type="number" name="max_file_size" value="{{ old('max_file_size', ($storageSetting->max_file_size ?? 10485760) / 1048576) }}" min="1" max="100"
                                               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-blue-500">
                                        <p class="text-xs text-gray-500 mt-1">Default: 10MB, Maximum: 100MB</p>
                                        @error('max_file_size') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Allowed Extensions</label>
                                        <div class="p-3 bg-gray-800 border border-gray-700 rounded-lg max-h-32 overflow-y-auto">
                                            @php
                                                $currentExtensions = is_string($storageSetting->allowed_extensions)
                                                    ? json_decode($storageSetting->allowed_extensions, true)
                                                    : ($storageSetting->allowed_extensions ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']);
                                            @endphp
                                            @foreach(['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt'] as $ext)
                                            <label class="flex items-center gap-2 py-1 cursor-pointer">
                                                <input type="checkbox" name="allowed_extensions[]" value="{{ $ext }}"
                                                    {{ in_array($ext, old('allowed_extensions', $currentExtensions)) ? 'checked' : '' }}
                                                    class="rounded border-gray-600 text-blue-500 focus:ring-blue-500 focus:ring-offset-gray-900">
                                                <span class="text-sm text-gray-300">.{{ $ext }}</span>
                                            </label>
                                            @endforeach
                                        </div>
                                        @error('allowed_extensions') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="mt-6">
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Notes (Optional)</label>
                                <textarea name="notes" rows="2" placeholder="Add any notes about this storage configuration..."
                                          class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 resize-none">{{ old('notes', $storageSetting->notes) }}</textarea>
                                @error('notes') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Actions -->
                            <div class="mt-6 pt-6 border-t border-gray-800 flex items-center justify-between">
                                <a href="{{ route('admin.storage-settings.index') }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-medium rounded-lg transition-colors">
                                    <span class="material-icons-outlined text-sm">arrow_back</span>
                                    Back
                                </a>
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                                    <span class="material-icons-outlined text-sm">save</span>
                                    Update Storage
                                </button>
                            </div>
                        </form>

                        <!-- Setup Guide -->
                        <div class="mt-6 pt-6 border-t border-gray-800">
                            <div class="bg-blue-500/5 border border-blue-500/20 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
                                    <span class="material-icons-outlined text-blue-400 text-sm">book</span>
                                    How to Get Cloudflare R2 Credentials
                                </h3>
                                <ol class="text-xs text-gray-400 space-y-1.5 ml-4 list-decimal">
                                    <li>Go to <strong class="text-gray-300">Cloudflare Dashboard</strong> → <strong class="text-gray-300">R2</strong></li>
                                    <li>Create a new <strong class="text-gray-300">Bucket</strong> (e.g., "mindory-uploads")</li>
                                    <li>Click on <strong class="text-gray-300">"Manage R2 API Tokens"</strong></li>
                                    <li>Create an API Token with <strong class="text-gray-300">"Read & Write"</strong> permissions</li>
                                    <li>Copy the <strong class="text-gray-300">Token ID</strong> (your Account ID) as "Access Key"</li>
                                    <li>Copy the <strong class="text-gray-300">Token</strong> as "Secret Access Key"</li>
                                    <li>Use this endpoint: <code class="px-1 py-0.5 bg-gray-800 rounded text-xs">https://&lt;TOKEN_ID&gt;.r2.cloudflarestorage.com</code></li>
                                    <li>Optionally, add a <strong class="text-gray-300">Custom Domain</strong> for your bucket from R2 settings</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
