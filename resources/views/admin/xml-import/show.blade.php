<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Import Details #{{ $import->id }} - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "neon-blue": "#3ddcff",
                        "neon-violet": "#9d5bff",
                        "soft-grey": "#d1d1d1"
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"],
                        "body": ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="font-body text-white">
    <div class="flex h-screen">
        <!-- Sidebar -->
        @include('admin.partials.sidebar')

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-gray-900/30 backdrop-blur-sm border-b border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <a href="{{ route('admin.xml-import.index') }}" class="text-neon-blue hover:text-neon-blue/80">
                                <span class="material-icons-outlined">arrow_back</span>
                            </a>
                            <h2 class="text-2xl font-bold font-display text-white">Import Details #{{ $import->id }}</h2>
                        </div>
                        <p class="text-soft-grey/70 text-sm">{{ $import->filename }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        @if($import->status === 'success')
                        <span class="inline-flex items-center gap-2 bg-green-500/10 text-green-400 px-4 py-2 rounded-full">
                            <span class="material-icons-outlined">check_circle</span>
                            Success
                        </span>
                        @elseif($import->status === 'failed')
                        <span class="inline-flex items-center gap-2 bg-red-500/10 text-red-400 px-4 py-2 rounded-full">
                            <span class="material-icons-outlined">error</span>
                            Failed
                        </span>
                        @elseif($import->status === 'processing')
                        <span class="inline-flex items-center gap-2 bg-yellow-500/10 text-yellow-400 px-4 py-2 rounded-full">
                            <span class="material-icons-outlined">hourglass_empty</span>
                            Processing
                        </span>
                        @else
                        <span class="inline-flex items-center gap-2 bg-gray-500/10 text-gray-400 px-4 py-2 rounded-full">
                            <span class="material-icons-outlined">pending</span>
                            Pending
                        </span>
                        @endif
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6 space-y-6">
                <!-- Import Summary -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Records Processed -->
                    <div class="bg-gradient-to-br from-neon-blue/10 to-transparent border border-neon-blue/30 rounded-xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 bg-neon-blue/20 rounded-lg flex items-center justify-center">
                                <span class="material-icons-outlined text-neon-blue">storage</span>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-1">{{ $import->records_processed }}</h3>
                        <p class="text-sm text-soft-grey/70">Records Processed</p>
                    </div>

                    <!-- Success -->
                    <div class="bg-gradient-to-br from-green-500/10 to-transparent border border-green-500/30 rounded-xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center">
                                <span class="material-icons-outlined text-green-400">check_circle</span>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-1">{{ $import->records_success }}</h3>
                        <p class="text-sm text-soft-grey/70">Successful</p>
                        @if($import->records_processed > 0)
                        <p class="text-xs text-green-400 mt-1">{{ number_format($import->success_rate, 1) }}%</p>
                        @endif
                    </div>

                    <!-- Failed -->
                    <div class="bg-gradient-to-br from-red-500/10 to-transparent border border-red-500/30 rounded-xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center">
                                <span class="material-icons-outlined text-red-400">error</span>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-1">{{ $import->records_failed }}</h3>
                        <p class="text-sm text-soft-grey/70">Failed</p>
                    </div>

                    <!-- Duration -->
                    <div class="bg-gradient-to-br from-neon-violet/10 to-transparent border border-neon-violet/30 rounded-xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 bg-neon-violet/20 rounded-lg flex items-center justify-center">
                                <span class="material-icons-outlined text-neon-violet">schedule</span>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-1">
                            @if($import->duration !== null)
                            {{ $import->duration }}s
                            @else
                            N/A
                            @endif
                        </h3>
                        <p class="text-sm text-soft-grey/70">Duration</p>
                    </div>
                </div>

                <!-- Import Information -->
                <div class="bg-gray-900/50 backdrop-blur-sm border border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-bold font-display text-white mb-4 flex items-center gap-2">
                        <span class="material-icons-outlined text-neon-blue">info</span>
                        Import Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-soft-grey/70 text-sm">Filename</label>
                            <p class="text-white mt-1">{{ $import->filename }}</p>
                        </div>

                        <div>
                            <label class="text-soft-grey/70 text-sm">Version</label>
                            <p class="text-white mt-1">{{ $import->version ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-soft-grey/70 text-sm">Admin</label>
                            <p class="text-white mt-1">{{ $import->admin->mobile ?? $import->admin->email ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-soft-grey/70 text-sm">IP Address</label>
                            <p class="text-white mt-1">{{ $import->ip_address ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-soft-grey/70 text-sm">Started At</label>
                            <p class="text-white mt-1">{{ $import->started_at ? $import->started_at->format('Y-m-d H:i:s') : 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-soft-grey/70 text-sm">Completed At</label>
                            <p class="text-white mt-1">{{ $import->completed_at ? $import->completed_at->format('Y-m-d H:i:s') : 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-soft-grey/70 text-sm">Created At</label>
                            <p class="text-white mt-1">{{ $import->created_at->format('Y-m-d H:i:s') }}</p>
                        </div>

                        <div>
                            <label class="text-soft-grey/70 text-sm">File Hash (SHA256)</label>
                            <p class="text-white mt-1 font-mono text-xs break-all">{{ $import->file_hash ?? 'N/A' }}</p>
                        </div>
                    </div>

                    @if($import->error_message)
                    <div class="mt-6">
                        <label class="text-soft-grey/70 text-sm">Error Message</label>
                        <div class="mt-2 bg-red-500/10 border border-red-500/30 rounded-lg p-4">
                            <p class="text-red-400">{{ $import->error_message }}</p>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Import Log -->
                @if($importLog)
                <div class="bg-gray-900/50 backdrop-blur-sm border border-gray-800 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold font-display text-white flex items-center gap-2">
                            <span class="material-icons-outlined text-neon-violet">list_alt</span>
                            Import Log
                        </h3>
                        <a href="{{ route('admin.xml-import.download-log', $import->id) }}" class="bg-neon-blue/20 border border-neon-blue/50 text-neon-blue px-4 py-2 rounded-lg hover:bg-neon-blue/30 transition-all text-sm flex items-center gap-2">
                            <span class="material-icons-outlined text-sm">download</span>
                            Download JSON
                        </a>
                    </div>

                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($importLog as $log)
                        <div class="flex items-start gap-3 p-3 rounded-lg {{ $log['status'] === 'success' ? 'bg-green-500/5 border border-green-500/20' : ($log['status'] === 'error' ? 'bg-red-500/5 border border-red-500/20' : 'bg-yellow-500/5 border border-yellow-500/20') }}">
                            <span class="material-icons-outlined text-sm {{ $log['status'] === 'success' ? 'text-green-400' : ($log['status'] === 'error' ? 'text-red-400' : 'text-yellow-400') }}">
                                {{ $log['status'] === 'success' ? 'check_circle' : ($log['status'] === 'error' ? 'error' : 'warning') }}
                            </span>
                            <div class="flex-1">
                                <p class="text-white text-sm">{{ $log['message'] }}</p>
                                @if(isset($log['table']))
                                <p class="text-soft-grey/70 text-xs mt-1">Table: {{ $log['table'] }}</p>
                                @endif
                                @if(isset($log['action']))
                                <p class="text-soft-grey/70 text-xs">Action: {{ ucfirst($log['action']) }}</p>
                                @endif
                                @if(isset($log['timestamp']))
                                <p class="text-soft-grey/50 text-xs mt-1">{{ $log['timestamp'] }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.xml-import.index') }}" class="bg-gray-800 border border-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-all flex items-center gap-2">
                        <span class="material-icons-outlined">arrow_back</span>
                        Back to List
                    </a>

                    <form action="{{ route('admin.xml-import.destroy', $import->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this import record? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500/10 border border-red-500/30 text-red-400 px-6 py-3 rounded-lg hover:bg-red-500/20 transition-all flex items-center gap-2">
                            <span class="material-icons-outlined">delete</span>
                            Delete Record
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
