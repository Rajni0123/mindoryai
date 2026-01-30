<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>XML Import System</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .card:hover { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.12); }
        .upload-area { transition: all 0.3s ease; }
        .upload-area.drag-over { background: rgba(59, 130, 246, 0.1); border-color: #3b82f6; }
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
                        <h1 class="text-base font-semibold text-white">XML Import System</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Upload XML files to automatically apply database updates</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6 space-y-4">
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

                @if($errors->any())
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg">
                    <div class="flex items-start gap-2">
                        <span class="material-icons-outlined text-red-400" style="font-size: 16px;">error</span>
                        <div class="flex-1">
                            <h4 class="text-red-400 font-medium text-xs">Validation Errors</h4>
                            <ul class="text-red-300 text-[10px] mt-1 space-y-0.5">
                                @foreach($errors->all() as $error)
                                <li>• {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Statistics Grid -->
                <div class="grid grid-cols-5 gap-3">
                    <!-- Total Imports -->
                    <div class="card rounded-lg p-3 transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[10px] text-gray-500 font-medium">Total Imports</p>
                                <p class="text-xl font-semibold text-white mt-1">{{ $stats['total_imports'] }}</p>
                            </div>
                            <div class="w-7 h-7 rounded-lg bg-blue-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-blue-400" style="font-size: 14px;">upload_file</span>
                            </div>
                        </div>
                    </div>

                    <!-- Successful -->
                    <div class="card rounded-lg p-3 transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[10px] text-gray-500 font-medium">Successful</p>
                                <p class="text-xl font-semibold text-white mt-1">{{ $stats['successful_imports'] }}</p>
                            </div>
                            <div class="w-7 h-7 rounded-lg bg-green-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-green-400" style="font-size: 14px;">check_circle</span>
                            </div>
                        </div>
                    </div>

                    <!-- Failed -->
                    <div class="card rounded-lg p-3 transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[10px] text-gray-500 font-medium">Failed</p>
                                <p class="text-xl font-semibold text-white mt-1">{{ $stats['failed_imports'] }}</p>
                            </div>
                            <div class="w-7 h-7 rounded-lg bg-red-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-red-400" style="font-size: 14px;">error</span>
                            </div>
                        </div>
                    </div>

                    <!-- Processing -->
                    <div class="card rounded-lg p-3 transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[10px] text-gray-500 font-medium">Processing</p>
                                <p class="text-xl font-semibold text-white mt-1">{{ $stats['processing_imports'] }}</p>
                            </div>
                            <div class="w-7 h-7 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-amber-400" style="font-size: 14px;">hourglass_empty</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Records -->
                    <div class="card rounded-lg p-3 transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[10px] text-gray-500 font-medium">Records</p>
                                <p class="text-xl font-semibold text-white mt-1">{{ number_format($stats['total_records_processed']) }}</p>
                            </div>
                            <div class="w-7 h-7 rounded-lg bg-purple-500/10 flex items-center justify-center">
                                <span class="material-icons-outlined text-purple-400" style="font-size: 14px;">storage</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload Section -->
                <div class="card rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-white mb-3">Upload XML File</h3>

                    <form action="{{ route('admin.xml-import.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf

                        <div class="upload-area border-2 border-dashed border-gray-700 rounded-lg p-6 text-center cursor-pointer" id="uploadArea">
                            <input type="file" name="xml_file" id="xmlFile" accept=".xml" class="hidden" required>

                            <div id="uploadPlaceholder">
                                <span class="material-icons-outlined text-blue-400 mb-3 block" style="font-size: 40px;">cloud_upload</span>
                                <h4 class="text-white font-medium text-sm mb-1">Click or drag XML file here</h4>
                                <p class="text-gray-500 text-[10px] mb-3">Maximum file size: 5MB</p>
                                <button type="button" onclick="document.getElementById('xmlFile').click()" class="px-4 py-2 bg-blue-500/10 border border-blue-500/30 text-blue-400 rounded-lg hover:bg-blue-500/20 transition-all text-xs font-medium">
                                    Browse Files
                                </button>
                            </div>

                            <div id="fileSelected" class="hidden">
                                <span class="material-icons-outlined text-green-400 mb-3 block" style="font-size: 40px;">check_circle</span>
                                <h4 class="text-white font-medium text-sm mb-1">File Selected</h4>
                                <p class="text-gray-400 text-xs mb-3" id="fileName"></p>
                                <button type="button" onclick="resetUpload()" class="text-red-400 hover:text-red-300 text-xs">
                                    Remove
                                </button>
                            </div>
                        </div>

                        <!-- Preview Results -->
                        <div id="previewResults" class="hidden mt-3 bg-blue-500/5 border border-blue-500/30 rounded-lg p-3">
                            <h4 class="text-white font-medium text-xs mb-2">Preview Results</h4>
                            <div id="previewContent" class="text-gray-400 text-[10px]"></div>
                        </div>

                        <div class="flex items-center gap-3 mt-4">
                            <button type="button" onclick="previewImport()" class="px-4 py-2 bg-purple-500/10 border border-purple-500/30 text-purple-400 rounded-lg hover:bg-purple-500/20 transition-all text-xs font-medium flex items-center gap-1.5">
                                <span class="material-icons-outlined" style="font-size: 14px;">visibility</span>
                                Preview
                            </button>
                            <button type="submit" class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-all text-xs font-medium flex items-center gap-1.5" id="importBtn">
                                <span class="material-icons-outlined" style="font-size: 14px;">cloud_upload</span>
                                Import Now
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Import History -->
                <div class="card rounded-lg overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-800/50">
                        <h2 class="text-sm font-semibold text-white">Import History</h2>
                    </div>

                    @if($imports->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-white/5 border-b border-gray-800/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Filename</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Admin</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Records</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400">Date</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800/50">
                                @foreach($imports as $import)
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3 text-xs text-white">#{{ $import->id }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $import->filename }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $import->admin->mobile ?? $import->admin->email ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        @if($import->status === 'success')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-500/10 text-green-400 rounded text-[10px] font-medium">
                                            <span class="w-1 h-1 bg-green-400 rounded-full"></span>
                                            Success
                                        </span>
                                        @elseif($import->status === 'failed')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-500/10 text-red-400 rounded text-[10px] font-medium">
                                            <span class="w-1 h-1 bg-red-400 rounded-full"></span>
                                            Failed
                                        </span>
                                        @elseif($import->status === 'processing')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-500/10 text-amber-400 rounded text-[10px] font-medium">
                                            <span class="w-1 h-1 bg-amber-400 rounded-full"></span>
                                            Processing
                                        </span>
                                        @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-500/10 text-gray-400 rounded text-[10px] font-medium">
                                            <span class="w-1 h-1 bg-gray-400 rounded-full"></span>
                                            Pending
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1 text-[10px]">
                                            <span class="text-green-400">{{ $import->records_success }}</span>
                                            <span class="text-gray-600">/</span>
                                            <span class="text-red-400">{{ $import->records_failed }}</span>
                                            <span class="text-gray-600">/</span>
                                            <span class="text-gray-400">{{ $import->records_processed }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-[10px] text-gray-500">{{ $import->created_at->format('M d, Y H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.xml-import.show', $import->id) }}"
                                               class="p-1.5 bg-blue-500/10 text-blue-400 rounded hover:bg-blue-500/20 transition-colors"
                                               title="View Details">
                                                <span class="material-icons-outlined" style="font-size: 14px;">visibility</span>
                                            </a>
                                            <form action="{{ route('admin.xml-import.destroy', $import->id) }}"
                                                  method="POST"
                                                  class="inline-block"
                                                  onsubmit="return confirm('Are you sure you want to delete this import record?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="p-1.5 bg-gray-500/10 text-gray-400 rounded hover:bg-gray-500/20 transition-colors"
                                                        title="Delete">
                                                    <span class="material-icons-outlined" style="font-size: 14px;">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 py-3 border-t border-gray-800/50">
                        {{ $imports->links() }}
                    </div>
                    @else
                    <div class="text-center py-12">
                        <span class="material-icons-outlined text-gray-700 mb-2 block" style="font-size: 48px;">inbox</span>
                        <p class="text-gray-500 text-sm">No import history yet</p>
                    </div>
                    @endif
                </div>
            </div>
        </main>
    </div>

    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('xmlFile');
        const uploadPlaceholder = document.getElementById('uploadPlaceholder');
        const fileSelected = document.getElementById('fileSelected');
        const fileName = document.getElementById('fileName');

        // Drag and drop
        uploadArea.addEventListener('click', (e) => {
            if (!e.target.closest('button[type="button"]')) {
                fileInput.click();
            }
        });

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');

            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect();
            }
        });

        fileInput.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            if (fileInput.files.length) {
                const file = fileInput.files[0];
                fileName.textContent = file.name;
                uploadPlaceholder.classList.add('hidden');
                fileSelected.classList.remove('hidden');
            }
        }

        function resetUpload() {
            fileInput.value = '';
            uploadPlaceholder.classList.remove('hidden');
            fileSelected.classList.add('hidden');
            document.getElementById('previewResults').classList.add('hidden');
        }

        async function previewImport() {
            if (!fileInput.files.length) {
                alert('Please select an XML file first');
                return;
            }

            const formData = new FormData();
            formData.append('xml_file', fileInput.files[0]);
            formData.append('preview_mode', '1');
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            const importBtn = document.getElementById('importBtn');
            importBtn.disabled = true;
            importBtn.innerHTML = '<span class="material-icons-outlined" style="font-size: 14px;">hourglass_empty</span><span>Processing...</span>';

            try {
                const response = await fetch('{{ route("admin.xml-import.upload") }}', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    const preview = data.preview;
                    let html = `
                        <div class="space-y-1">
                            <p><strong>Filename:</strong> ${preview.filename}</p>
                            ${preview.version ? `<p><strong>Version:</strong> ${preview.version}</p>` : ''}
                            <p><strong>Total Records:</strong> ${preview.total_records}</p>
                            <div class="mt-2">
                                <strong>Tables:</strong>
                                <ul class="mt-1 space-y-0.5">
                    `;

                    preview.tables.forEach(table => {
                        html += `<li>• ${table.name}: ${table.records} record(s)</li>`;
                    });

                    html += `
                                </ul>
                            </div>
                        </div>
                    `;

                    document.getElementById('previewContent').innerHTML = html;
                    document.getElementById('previewResults').classList.remove('hidden');
                } else {
                    alert('Preview failed: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                importBtn.disabled = false;
                importBtn.innerHTML = '<span class="material-icons-outlined" style="font-size: 14px;">cloud_upload</span><span>Import Now</span>';
            }
        }
    </script>
</body>
</html>
