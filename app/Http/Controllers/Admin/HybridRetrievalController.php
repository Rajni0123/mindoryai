<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrontendConfig;
use App\Models\KnowledgeSource;
use App\Services\Retrieval\KnowledgeSourceIngestionService;
use App\Services\Retrieval\RetrievalSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HybridRetrievalController extends Controller
{
    public function index(RetrievalSettingsService $settings): View
    {
        $config = FrontendConfig::getAllConfigs();
        $migrationRequired = ! Schema::hasTable('knowledge_sources');
        $sources = $migrationRequired
            ? collect()
            : KnowledgeSource::orderByDesc('created_at')->get();
        $providerPriority = $settings->providerPriority();
        $quizPriority = $settings->quizPriority();

        return view('admin.hybrid-retrieval', compact(
            'config',
            'sources',
            'providerPriority',
            'quizPriority',
            'migrationRequired'
        ));
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
}
