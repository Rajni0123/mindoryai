<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Storage Settings | Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-semibold text-white">Storage Settings</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Manage Cloudflare R2 / S3 Storage</p>
                    </div>
                    <a href="{{ route('admin.storage-settings.create') }}" class="flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                        <span class="material-icons-outlined text-sm">add</span>
                        Add Storage
                    </a>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6 space-y-6">
                <!-- Active Storage Status -->
                @if($activeStorage)
                <div class="card rounded-lg p-4 border-l-4 border-green-500">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-green-500/10 flex items-center justify-center flex-shrink-0">
                            <span class="material-icons-outlined text-green-400">check_circle</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-white">Active Storage: {{ $activeStorage->name }}</h3>
                            <p class="text-xs text-gray-400 mt-1">
                                <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $activeStorage->driver === 'r2' ? 'bg-blue-500/20 text-blue-400' : ($activeStorage->driver === 's3' ? 'bg-amber-500/20 text-amber-400' : 'bg-gray-500/20 text-gray-400') }}">
                                    {{ strtoupper($activeStorage->driver) }}
                                </span>
                                <span class="mx-2">|</span>
                                <span>Bucket: <code class="px-1.5 py-0.5 bg-gray-800 rounded text-xs">{{ $activeStorage->bucket }}</code></span>
                                <span class="mx-2">|</span>
                                <span>Max: {{ number_format($activeStorage->max_file_size / 1048576, 1) }} MB</span>
                            </p>
                        </div>
                    </div>
                </div>
                @else
                <div class="card rounded-lg p-4 border-l-4 border-yellow-500">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-yellow-500/10 flex items-center justify-center flex-shrink-0">
                            <span class="material-icons-outlined text-yellow-400">warning</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-white">No Active Storage</h3>
                            <p class="text-xs text-gray-400 mt-1">Please configure and activate a storage to enable file uploads.</p>
                        </div>
                        <a href="{{ route('admin.storage-settings.create') }}" class="flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                            <span class="material-icons-outlined text-sm">add</span>
                            Add Storage
                        </a>
                    </div>
                </div>
                @endif

                <!-- Storage List -->
                <div class="card rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Driver</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Bucket</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Endpoint</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Max Size</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-400 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800/50">
                                @forelse($storages as $storage)
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-white">{{ $storage->name }}</p>
                                        @if($storage->notes)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ \Illuminate\Support\Str::limit($storage->notes, 40) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $storage->driver === 'r2' ? 'bg-blue-500/20 text-blue-400' : ($storage->driver === 's3' ? 'bg-amber-500/20 text-amber-400' : 'bg-gray-500/20 text-gray-400') }}">
                                            {{ strtoupper($storage->driver) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <code class="text-xs text-gray-300">{{ $storage->bucket ?? 'N/A' }}</code>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-xs text-gray-400 max-w-xs truncate">{{ $storage->endpoint ?? 'N/A' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-300">{{ number_format($storage->max_file_size / 1048576, 1) }} MB</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($storage->is_active)
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
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            @if($storage->is_active)
                                            <a href="{{ route('admin.storage-settings.deactivate', $storage) }}"
                                               onclick="return confirm('Deactivate this storage?')"
                                               class="p-1.5 text-yellow-400 hover:bg-yellow-500/10 rounded transition-colors"
                                               title="Deactivate">
                                                <span class="material-icons-outlined text-sm">pause_circle</span>
                                            </a>
                                            @else
                                            <a href="{{ route('admin.storage-settings.activate', $storage) }}"
                                               onclick="return confirm('Activate this storage?')"
                                               class="p-1.5 text-green-400 hover:bg-green-500/10 rounded transition-colors"
                                               title="Activate">
                                                <span class="material-icons-outlined text-sm">play_circle</span>
                                            </a>
                                            @endif
                                            <button onclick="testConnection({{ $storage->id }})"
                                                    class="p-1.5 text-blue-400 hover:bg-blue-500/10 rounded transition-colors"
                                                    title="Test Connection">
                                                <span class="material-icons-outlined text-sm">power</span>
                                            </button>
                                            <a href="{{ route('admin.storage-settings.edit', $storage) }}"
                                               class="p-1.5 text-gray-400 hover:bg-gray-800 rounded transition-colors"
                                               title="Edit">
                                                <span class="material-icons-outlined text-sm">edit</span>
                                            </a>
                                            @if(!$storage->is_active)
                                            <form action="{{ route('admin.storage-settings.destroy', $storage) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Delete this storage?')"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="p-1.5 text-red-400 hover:bg-red-500/10 rounded transition-colors"
                                                        title="Delete">
                                                    <span class="material-icons-outlined text-sm">delete</span>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <span class="material-icons-outlined text-5xl text-gray-700 block mb-3">storage</span>
                                        <p class="text-gray-400 text-sm mb-4">No storage configurations found</p>
                                        <a href="{{ route('admin.storage-settings.create') }}"
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                            <span class="material-icons-outlined text-sm">add</span>
                                            Add First Storage
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Setup Guide -->
                <div class="card rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
                        <span class="material-icons-outlined text-blue-400 text-sm">info</span>
                        Cloudflare R2 Setup Guide
                    </h3>
                    <ol class="text-xs text-gray-400 space-y-1.5 ml-5 list-decimal">
                        <li>Go to <strong class="text-gray-300">Cloudflare Dashboard</strong> → <strong class="text-gray-300">R2</strong></li>
                        <li>Create a new <strong class="text-gray-300">Bucket</strong> (e.g., "mindory-uploads")</li>
                        <li>Click on <strong class="text-gray-300">"Manage R2 API Tokens"</strong></li>
                        <li>Create an API Token with <strong class="text-gray-300">"Read & Write"</strong> permissions</li>
                        <li>Copy the <strong class="text-gray-300">Token ID</strong> as "Access Key"</li>
                        <li>Copy the <strong class="text-gray-300">Token</strong> as "Secret Access Key"</li>
                        <li>Endpoint: <code class="px-1.5 py-0.5 bg-gray-800 rounded text-xs">https://&lt;TOKEN_ID&gt;.r2.cloudflarestorage.com</code></li>
                        <li>Optionally, add a <strong class="text-gray-300">Custom Domain</strong> for public access</li>
                    </ol>
                </div>
            </div>
        </main>
    </div>

    <script>
    function testConnection(storageId) {
        if (!confirm('Test connection to this storage?')) {
            return;
        }

        const btn = event.currentTarget;
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<span class="material-icons-outlined text-sm animate-spin">sync</span>';
        btn.disabled = true;

        fetch(`{{ route('admin.storage-settings.test', ['storageSetting' => '__ID__']) }}`.replace('__ID__', storageId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Success: ' + data.message);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error: Failed to test connection');
            console.error(error);
        })
        .finally(() => {
            btn.innerHTML = originalContent;
            btn.disabled = false;
        });
    }
    </script>
</body>
</html>
