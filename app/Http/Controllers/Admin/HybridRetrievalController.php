<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrontendConfig;
use App\Models\KnowledgeSource;
use App\Services\Retrieval\KnowledgeSourceIngestionService;
use App\Services\Retrieval\RetrievalSettingsService;
use App\Support\AdminUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HybridRetrievalController extends Controller
{
    public function index(RetrievalSettingsService $settings): View
    {
        $pageError = null;
        $config = FrontendConfig::getAllConfigs();
        $migrationRequired = ! $this->hybridTablesReady();

        $sources = collect();
        if (! $migrationRequired) {
            try {
                $sources = KnowledgeSource::orderByDesc('created_at')->get();
            } catch (\Throwable $e) {
                Log::warning('Hybrid retrieval: could not list knowledge sources', [
                    'message' => $e->getMessage(),
                ]);
                $pageError = 'Could not load knowledge sources: ' . $e->getMessage();
            }
        }

        $providerPriority = $this->normalizeJsonArray($settings->providerPriority());
        $quizPriority = $this->normalizeJsonArray($settings->quizPriority());

        return view('admin.hybrid-retrieval', [
            'config' => $config,
            'sources' => $sources,
            'providerPriority' => $providerPriority,
            'quizPriority' => $quizPriority,
            'migrationRequired' => $migrationRequired,
            'pageError' => $pageError ?? null,
            'settingsUrl' => AdminUrl::route('admin.hybrid-retrieval.settings'),
            'storeUrl' => AdminUrl::route('admin.hybrid-retrieval.sources.store'),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $flags = [
            'retrieval.hybrid_enabled' => $request->boolean('hybrid_enabled'),
            'retrieval.existing_rag' => $request->boolean('existing_rag'),
            'retrieval.exa_enabled' => $request->boolean('exa_enabled'),
            'retrieval.hybrid_mode' => $request->boolean('hybrid_mode'),
            'retrieval.redis_cache' => $request->boolean('redis_cache'),
            'retrieval.web_search' => $request->boolean('web_search'),
            'retrieval.temporary_pdf' => $request->boolean('temporary_pdf'),
            'retrieval.ai_quiz_fallback' => $request->boolean('ai_quiz_fallback'),
        ];

        foreach ($flags as $key => $value) {
            FrontendConfig::setValue($key, $value ? '1' : '0', 'boolean');
        }

        if ($request->filled('provider_priority')) {
            FrontendConfig::setValue('retrieval.provider_priority', $request->input('provider_priority'), 'json');
        }

        if ($request->filled('quiz_priority')) {
            FrontendConfig::setValue('retrieval.quiz_priority', $request->input('quiz_priority'), 'json');
        }

        if ($request->filled('exa_api_key')) {
            FrontendConfig::setValue('retrieval.exa_api_key', $request->input('exa_api_key'), 'string');
        }

        return back()->with('success', 'Hybrid retrieval settings saved.');
    }

    public function storeKnowledgeSource(Request $request, KnowledgeSourceIngestionService $ingestion): RedirectResponse
    {
        if (! Schema::hasTable('knowledge_sources')) {
            return back()->with('error', 'Run php artisan migrate --force first.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'source_type' => 'required|in:pdf,docx,txt,markdown,url,zip,question_bank',
            'file' => 'nullable|file|max:51200',
            'url' => 'nullable|url|max:2048',
            'subject' => 'nullable|string|max:100',
            'chapter' => 'nullable|string|max:100',
            'class' => 'nullable|string|max:50',
            'exam' => 'nullable|string|max:100',
            'board' => 'nullable|string|max:100',
            'topic' => 'nullable|string|max:150',
            'difficulty' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:50',
        ]);

        $metadata = $request->only(['subject', 'chapter', 'class', 'exam', 'board', 'topic', 'difficulty', 'language']);
        $metadata['type'] = $request->input('source_type');

        if ($request->input('source_type') === 'url') {
            $ingestion->ingestUrl(
                $request->input('name'),
                $request->input('url'),
                $metadata,
                $request->user()?->id,
            );
        } else {
            $request->validate(['file' => 'required|file']);
            $ingestion->ingestUploadedFile(
                $request->file('file'),
                $request->input('name'),
                $request->input('source_type'),
                $metadata,
                $request->user()?->id,
            );
        }

        return back()->with('success', 'Knowledge source ingested and embeddings queued.');
    }

    public function toggleKnowledgeSource(KnowledgeSource $knowledgeSource): RedirectResponse
    {
        $knowledgeSource->update(['is_active' => ! $knowledgeSource->is_active]);

        return back()->with('success', 'Knowledge source updated.');
    }

    /**
     * @param  mixed  $value
     * @return array<mixed>
     */
    private function normalizeJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function hybridTablesReady(): bool
    {
        if (Schema::hasTable('knowledge_sources')) {
            return true;
        }

        try {
            DB::select('SELECT 1 FROM knowledge_sources LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function retrievalFlag(array $config, string $field): bool
    {
        $value = $config['retrieval.' . $field] ?? false;

        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return ! empty($value);
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function retrievalFlagChecked(array $config, string $field): bool
    {
        $old = old($field);

        if ($old !== null) {
            return (bool) $old;
        }

        return self::retrievalFlag($config, $field);
    }

    public static function retrievalString(array $config, string $key, string $default = ''): string
    {
        $value = $config[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    public static function jsonForTextarea(mixed $data): string
    {
        if (! is_array($data)) {
            $data = [];
        }

        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        return $encoded !== false ? $encoded : '[]';
    }
}
