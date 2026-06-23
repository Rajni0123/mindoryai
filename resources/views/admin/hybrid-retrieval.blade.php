<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hybrid Retrieval</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
    </style>
</head>
<body class="text-gray-300">
<div class="flex h-screen">
    @include('admin.partials.sidebar')

    <main class="flex-1 overflow-y-auto">
        <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
            <h1 class="text-base font-semibold text-white">Hybrid Retrieval Engine</h1>
            <p class="text-xs text-gray-500 mt-0.5">RAG + Exa orchestration, caching, and knowledge sources</p>
        </header>

        <div class="p-6 space-y-4">
            @if(!empty($pageError))
                <div class="p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-sm text-red-300">
                    {{ $pageError }}
                </div>
            @endif

            @if(!empty($migrationRequired))
                <div class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg text-sm text-amber-200">
                    Database tables missing. Run on server:
                    <code class="block mt-2 text-xs bg-black/40 p-2 rounded">php artisan migrate --force</code>
                </div>
            @endif

            @if(session('error'))
                <div class="p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-sm text-red-300">{{ session('error') }}</div>
            @endif

            @if(session('success'))
                <div class="p-3 bg-green-500/10 border border-green-500/30 rounded-lg text-sm text-green-300">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ $settingsUrl }}" class="card rounded-lg p-4 space-y-4" data-admin-csrf-form>
                @csrf
                <h3 class="text-sm font-semibold text-white">Feature Toggles</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    @foreach([
                        'hybrid_enabled' => 'Enable Hybrid Engine',
                        'existing_rag' => 'Existing RAG',
                        'exa_enabled' => 'Exa Search',
                        'hybrid_mode' => 'Hybrid Mode (RAG + Exa)',
                        'redis_cache' => 'Redis Cache',
                        'web_search' => 'Web Search',
                        'temporary_pdf' => 'Temporary PDF Retrieval',
                        'ai_quiz_fallback' => 'AI Quiz Fallback',
                    ] as $field => $label)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="{{ $field }}" value="1"
                                   {{ \App\Http\Controllers\Admin\HybridRetrievalController::retrievalFlagChecked($config, $field) ? 'checked' : '' }}
                                   class="rounded bg-gray-800 border-gray-700">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div>
                    <label class="text-xs text-gray-400">Exa API Key</label>
                    <input type="password" name="exa_api_key" value="{{ old('exa_api_key', \App\Http\Controllers\Admin\HybridRetrievalController::retrievalString($config, 'retrieval.exa_api_key')) }}"
                           class="w-full mt-1 px-3 py-2 rounded bg-gray-900 border border-gray-800 text-sm">
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="testExaBtn" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-600 text-white text-xs rounded">
                        Test Exa Connection
                    </button>
                    <span id="testExaStatus" class="text-xs text-gray-400"></span>
                </div>

                <div>
                    <label class="text-xs text-gray-400">Provider Priority (JSON)</label>
                    <textarea name="provider_priority" rows="4"
                              class="w-full mt-1 px-3 py-2 rounded bg-gray-900 border border-gray-800 text-sm font-mono">{{ old('provider_priority', \App\Http\Controllers\Admin\HybridRetrievalController::jsonForTextarea($providerPriority)) }}</textarea>
                </div>

                <div>
                    <label class="text-xs text-gray-400">Quiz Priority (JSON array)</label>
                    <textarea name="quiz_priority" rows="3"
                              class="w-full mt-1 px-3 py-2 rounded bg-gray-900 border border-gray-800 text-sm font-mono">{{ old('quiz_priority', \App\Http\Controllers\Admin\HybridRetrievalController::jsonForTextarea($quizPriority)) }}</textarea>
                </div>

                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs rounded">Save Settings</button>
            </form>

            <form method="POST" action="{{ $storeUrl }}" enctype="multipart/form-data" class="card rounded-lg p-4 space-y-3" data-admin-csrf-form>
                @csrf
                <h3 class="text-sm font-semibold text-white">Add New Knowledge Source</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <input name="name" placeholder="Source name" required class="px-3 py-2 rounded bg-gray-900 border border-gray-800 text-sm">
                    <select name="source_type" class="px-3 py-2 rounded bg-gray-900 border border-gray-800 text-sm">
                        <option value="pdf">PDF</option>
                        <option value="docx">DOCX</option>
                        <option value="txt">TXT</option>
                        <option value="markdown">Markdown</option>
                        <option value="url">Website URL</option>
                        <option value="zip">ZIP</option>
                        <option value="question_bank">Question Bank</option>
                    </select>
                    <input type="file" name="file" class="px-3 py-2 rounded bg-gray-900 border border-gray-800 text-sm">
                    <input name="url" placeholder="Website URL (if type=url)" class="px-3 py-2 rounded bg-gray-900 border border-gray-800 text-sm">
                    <input name="subject" placeholder="Subject" class="px-3 py-2 rounded bg-gray-900 border border-gray-800 text-sm">
                    <input name="chapter" placeholder="Chapter" class="px-3 py-2 rounded bg-gray-900 border border-gray-800 text-sm">
                    <input name="class" placeholder="Class" class="px-3 py-2 rounded bg-gray-900 border border-gray-800 text-sm">
                    <input name="exam" placeholder="Exam" class="px-3 py-2 rounded bg-gray-900 border border-gray-800 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs rounded">Upload & Embed</button>
            </form>

            <div class="card rounded-lg p-4">
                <h3 class="text-sm font-semibold text-white mb-3">Registered Knowledge Sources</h3>
                <div class="space-y-2 text-xs">
                    @forelse($sources as $source)
                        <div class="flex items-center justify-between border border-gray-800 rounded p-2">
                            <div>
                                <div class="text-white">{{ $source->name }}</div>
                                <div class="text-gray-500">{{ $source->provider_key }} · {{ $source->type }} · {{ $source->chunk_count }} chunks</div>
                            </div>
                            <form method="POST" action="{{ \App\Support\AdminUrl::route('admin.hybrid-retrieval.sources.toggle', $source) }}" data-admin-csrf-form>
                                @csrf
                                <button class="px-2 py-1 rounded {{ $source->is_active ? 'bg-green-500/20 text-green-300' : 'bg-gray-700 text-gray-300' }}">
                                    {{ $source->is_active ? 'Enabled' : 'Disabled' }}
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-gray-500">No custom knowledge sources yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </main>
</div>
<script>
(function () {
    function xsrfFromCookie() {
        const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    async function ensureAdminCsrf() {
        await fetch('/sanctum/csrf-cookie', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
    }

    async function submitAdminForm(form, retried) {
        const submitter = form.querySelector('[type="submit"]');
        if (submitter) submitter.disabled = true;

        try {
            await ensureAdminCsrf();
            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html,application/json',
            };
            const xsrf = xsrfFromCookie();
            const meta = document.querySelector('meta[name="csrf-token"]')?.content;
            if (meta) headers['X-CSRF-TOKEN'] = meta;
            if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;

            const response = await fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                body: new FormData(form),
                credentials: 'same-origin',
                headers,
                redirect: 'follow',
            });

            if (response.status === 419 && !retried) {
                await ensureAdminCsrf();
                return submitAdminForm(form, true);
            }

            if (!response.ok && response.status !== 419) {
                window.location.reload();
                return;
            }

            window.location.href = response.url || window.location.href;
        } catch (e) {
            form.submit();
        } finally {
            if (submitter) submitter.disabled = false;
        }
    }

    document.querySelectorAll('[data-admin-csrf-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitAdminForm(form, false);
        });
    });

    ensureAdminCsrf();

    const testExaBtn = document.getElementById('testExaBtn');
    const testExaStatus = document.getElementById('testExaStatus');
    if (testExaBtn && testExaStatus) {
        testExaBtn.addEventListener('click', async function () {
            testExaBtn.disabled = true;
            testExaStatus.textContent = 'Testing Exa...';
            testExaStatus.className = 'text-xs text-gray-400';

            try {
                await ensureAdminCsrf();
                const headers = {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                };
                const meta = document.querySelector('meta[name="csrf-token"]')?.content;
                const xsrf = xsrfFromCookie();
                if (meta) headers['X-CSRF-TOKEN'] = meta;
                if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;

                const response = await fetch(@json($testExaUrl), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers,
                    body: JSON.stringify({
                        query: 'UPSC previous year question paper official pdf hindi',
                        subject: 'General Studies',
                    }),
                });

                const data = await response.json().catch(function () { return {}; });
                const message = data.message || ('Request failed (' + response.status + ')');
                testExaStatus.textContent = message;
                testExaStatus.className = data.ok
                    ? 'text-xs text-green-400'
                    : 'text-xs text-red-400';
            } catch (e) {
                testExaStatus.textContent = e.message || 'Exa test failed';
                testExaStatus.className = 'text-xs text-red-400';
            } finally {
                testExaBtn.disabled = false;
            }
        });
    }
})();
</script>
</body>
</html>
