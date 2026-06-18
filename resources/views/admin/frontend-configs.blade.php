<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Frontend Configurations</title>

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
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-semibold text-white">Frontend Configurations</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Control frontend UI and behavior dynamically</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button onclick="clearCache()" class="flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-lg transition-colors">
                            <span class="material-icons-outlined" style="font-size: 16px;">refresh</span>
                            Clear Cache
                        </button>
                        <button onclick="showAddModal()" class="flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                            <span class="material-icons-outlined" style="font-size: 16px;">add</span>
                            Add Config
                        </button>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-green-400" style="font-size: 16px;">check_circle</span>
                    <p class="text-green-300">{{ session('success') }}</p>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-red-400" style="font-size: 16px;">error</span>
                    <p class="text-red-300">{{ session('error') }}</p>
                </div>
                @endif

                <!-- Info Box -->
                <div class="mb-4 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                    <div class="flex items-start gap-3">
                        <span class="material-icons-outlined text-blue-400" style="font-size: 20px;">info</span>
                        <div class="text-xs text-blue-300">
                            <p class="font-semibold mb-1">About Frontend Configurations:</p>
                            <p class="text-blue-300/90">Configure UI elements and behavior for both website and mobile app without redeploying code. Changes take effect immediately after cache clear.</p>
                            <p class="text-blue-300/90 mt-2"><strong>API Endpoint:</strong> <code class="bg-blue-900/30 px-2 py-0.5 rounded">/api/frontend-config</code></p>
                        </div>
                    </div>
                </div>

                <!-- Configs Table -->
                <div class="card rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-white/5 border-b border-gray-800/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Config Key</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Value</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Description</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800/50">
                                @forelse($configs as $config)
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3">
                                        <code class="text-xs font-mono text-blue-400 bg-blue-500/10 px-2 py-1 rounded">{{ $config->config_key }}</code>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-xs text-gray-300 max-w-xs">
                                            @if($config->value_type === 'json')
                                                <pre class="text-[10px] overflow-x-auto bg-gray-900 p-2 rounded">{{ json_encode(json_decode($config->config_value), JSON_PRETTY_PRINT) }}</pre>
                                            @else
                                                <span class="truncate block">{{ $config->config_value }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 bg-purple-500/10 text-purple-400 rounded text-[10px] font-medium">{{ $config->value_type }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-[10px] text-gray-500 max-w-xs truncate">{{ $config->description ?? '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox"
                                                   class="sr-only peer config-toggle"
                                                   data-config-id="{{ $config->id }}"
                                                   {{ $config->is_active ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-gray-700 peer-focus:outline-none peer-focus:ring-1 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-600"></div>
                                        </label>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <button onclick="editConfig({{ $config->id }}, '{{ $config->config_key }}', `{{ $config->config_value }}`, '{{ $config->value_type }}', '{{ $config->description }}')"
                                                    class="p-1.5 bg-blue-500/10 text-blue-400 rounded hover:bg-blue-500/20 transition-colors"
                                                    title="Edit">
                                                <span class="material-icons-outlined" style="font-size: 14px;">edit</span>
                                            </button>

                                            <button onclick="deleteConfig({{ $config->id }}, '{{ $config->config_key }}')"
                                                    class="p-1.5 bg-red-500/10 text-red-400 rounded hover:bg-red-500/20 transition-colors"
                                                    title="Delete">
                                                <span class="material-icons-outlined" style="font-size: 14px;">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <span class="material-icons-outlined text-gray-700 mb-2 block" style="font-size: 48px;">settings</span>
                                        <p class="text-gray-500 text-sm">No configurations found</p>
                                        <button onclick="showAddModal()" class="mt-3 text-blue-400 hover:text-blue-300 text-xs">Add your first config</button>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($configs->hasPages())
                    <div class="px-4 py-3 border-t border-gray-800/50">
                        {{ $configs->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Modal -->
    <div id="configModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
        <div class="bg-[#1a1a1a] rounded-xl shadow-2xl border border-gray-800 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <form id="configForm" method="POST" action="{{ route('admin.frontend-configs.store') }}">
                @csrf
                <input type="hidden" id="configId" name="config_id">
                <input type="hidden" id="formMethod" name="_method" value="">

                <div class="p-6 border-b border-gray-800">
                    <h2 id="modalTitle" class="text-lg font-semibold text-white">Add Configuration</h2>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Config Key -->
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-2">Config Key *</label>
                        <input type="text"
                               id="configKey"
                               name="config_key"
                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                               placeholder="e.g., show_banner, header_text"
                               required>
                        <p class="mt-1 text-[10px] text-gray-500">Lowercase letters, numbers, and underscores only</p>
                    </div>

                    <!-- Value Type -->
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-2">Value Type *</label>
                        <select id="valueType"
                                name="value_type"
                                class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                required>
                            <option value="string">String</option>
                            <option value="boolean">Boolean</option>
                            <option value="number">Number</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>

                    <!-- Config Value -->
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-2">Config Value *</label>
                        <textarea id="configValue"
                                  name="config_value"
                                  rows="4"
                                  class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors font-mono"
                                  placeholder="Enter value..."
                                  required></textarea>
                        <p id="valueHint" class="mt-1 text-[10px] text-gray-500"></p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-2">Description</label>
                        <input type="text"
                               id="configDescription"
                               name="description"
                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm focus:outline-none focus:border-blue-500 transition-colors"
                               placeholder="Human-readable description">
                    </div>

                    <!-- Active Status -->
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   id="isActive"
                                   name="is_active"
                                   checked
                                   class="w-4 h-4 text-blue-600 bg-gray-700 border-gray-600 rounded focus:ring-blue-500">
                            <span class="text-sm text-gray-300">Enable this configuration</span>
                        </label>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-800 flex items-center justify-end gap-3">
                    <button type="button"
                            onclick="closeModal()"
                            class="px-4 py-2 text-gray-400 hover:text-white text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                        Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Config Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const configToggles = document.querySelectorAll('.config-toggle');

            configToggles.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const configId = this.dataset.configId;
                    const isActive = this.checked;

                    fetch(`/admin/frontend-configs/${configId}/toggle`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ is_active: isActive })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(`Config ${isActive ? 'enabled' : 'disabled'}`, 'success');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.checked = !isActive;
                        showToast('Failed to update status', 'error');
                    });
                });
            });

            // Value type change hint
            const valueType = document.getElementById('valueType');
            const valueHint = document.getElementById('valueHint');

            valueType.addEventListener('change', function() {
                const hints = {
                    'string': 'Enter text value',
                    'boolean': 'Enter true or false',
                    'number': 'Enter numeric value (e.g., 100 or 99.99)',
                    'json': 'Enter valid JSON (e.g., {"key": "value"} or [1,2,3])'
                };
                valueHint.textContent = hints[this.value] || '';
            });
        });

        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Configuration';
            document.getElementById('configForm').action = '{{ route('admin.frontend-configs.store') }}';
            document.getElementById('formMethod').value = '';
            document.getElementById('configForm').reset();
            document.getElementById('configKey').readOnly = false;
            document.getElementById('configModal').classList.remove('hidden');
        }

        function editConfig(id, key, value, type, description) {
            document.getElementById('modalTitle').textContent = 'Edit Configuration';
            document.getElementById('configForm').action = `/admin/frontend-configs/${id}`;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('configKey').value = key;
            document.getElementById('configKey').readOnly = true;
            document.getElementById('valueType').value = type;
            document.getElementById('configValue').value = value;
            document.getElementById('configDescription').value = description || '';
            document.getElementById('configModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('configModal').classList.add('hidden');
        }

        function deleteConfig(id, key) {
            if (!confirm(`Are you sure you want to delete "${key}"?`)) return;

            fetch(`/admin/frontend-configs/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Config deleted successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to delete config', 'error');
            });
        }

        function clearCache() {
            fetch('/admin/frontend-configs/clear-cache', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Cache cleared successfully', 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to clear cache', 'error');
            });
        }

        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 text-sm text-white ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>
</html>
