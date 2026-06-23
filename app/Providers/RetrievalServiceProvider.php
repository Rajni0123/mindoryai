<?php

namespace App\Providers;

use App\Contracts\Retrieval\IntentClassifierInterface;
use App\Models\KnowledgeSource;
use App\Services\Retrieval\BroadPyqPdfSearcher;
use App\Services\Retrieval\ExaSearchService;
use App\Services\Retrieval\HybridContextMerger;
use App\Services\Retrieval\IntentClassifier;
use App\Services\Retrieval\KnowledgeSourceIngestionService;
use App\Services\Retrieval\ProviderRegistry;
use App\Services\Retrieval\Providers\ChunkRagProvider;
use App\Services\Retrieval\Providers\ExaSearchProvider;
use App\Services\Retrieval\Providers\NcertRagProvider;
use App\Services\Retrieval\Providers\PyqRagProvider;
use App\Services\Retrieval\Questions\DuplicateRemover;
use App\Services\Retrieval\Questions\MCQDetector;
use App\Services\Retrieval\Questions\PDFQuestionExtractor;
use App\Services\Retrieval\Questions\QuestionExtractionEngine;
use App\Services\Retrieval\Questions\QuestionNormalizer;
use App\Services\Retrieval\Questions\QuestionParser;
use App\Services\Retrieval\Questions\QuestionRanker;
use App\Services\Retrieval\Questions\QuestionRepository;
use App\Services\Retrieval\Questions\QuizService;
use App\Services\Retrieval\Questions\RetrievalEngine;
use App\Services\Retrieval\Questions\Search\Providers\BingSearchProvider;
use App\Services\Retrieval\Questions\Search\Providers\BraveSearchProvider;
use App\Services\Retrieval\Questions\Search\Providers\ExaQuizDocumentProvider;
use App\Services\Retrieval\Questions\Search\Providers\GoogleCustomSearchProvider;
use App\Services\Retrieval\Questions\Search\Providers\OfficialWebsiteSearchProvider;
use App\Services\Retrieval\Questions\Search\QuizSearchRouter;
use App\Services\Retrieval\QuizRetrievalEngine;
use App\Services\Retrieval\RetrievalCacheService;
use App\Services\Retrieval\RetrievalOrchestrator;
use App\Services\Retrieval\RetrievalRouter;
use App\Services\Retrieval\RetrievalSettingsService;
use App\Services\Retrieval\TemporaryPdfRetriever;
use Illuminate\Support\ServiceProvider;

class RetrievalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RetrievalSettingsService::class);
        $this->app->singleton(RetrievalCacheService::class);
        $this->app->singleton(IntentClassifierInterface::class, IntentClassifier::class);
        $this->app->singleton(RetrievalRouter::class);
        $this->app->singleton(HybridContextMerger::class);
        $this->app->singleton(TemporaryPdfRetriever::class);
        $this->app->singleton(ExaSearchService::class);
        $this->app->singleton(KnowledgeSourceIngestionService::class);
        $this->app->singleton(QuizSearchRouter::class, function ($app) {
            return new QuizSearchRouter([
                $app->make(OfficialWebsiteSearchProvider::class),
                $app->make(ExaQuizDocumentProvider::class),
                $app->make(GoogleCustomSearchProvider::class),
                $app->make(BraveSearchProvider::class),
                $app->make(BingSearchProvider::class),
            ], $app->make(RetrievalCacheService::class));
        });
        $this->app->singleton(OfficialWebsiteSearchProvider::class);
        $this->app->singleton(ExaQuizDocumentProvider::class);
        $this->app->singleton(GoogleCustomSearchProvider::class);
        $this->app->singleton(BroadPyqPdfSearcher::class);
        $this->app->singleton(BraveSearchProvider::class);
        $this->app->singleton(BingSearchProvider::class);
        $this->app->singleton(RetrievalEngine::class);
        $this->app->singleton(PDFQuestionExtractor::class);
        $this->app->singleton(MCQDetector::class);
        $this->app->singleton(QuestionParser::class);
        $this->app->singleton(QuestionNormalizer::class);
        $this->app->singleton(DuplicateRemover::class);
        $this->app->singleton(QuestionRanker::class);
        $this->app->singleton(QuestionRepository::class);
        $this->app->singleton(QuestionExtractionEngine::class);
        $this->app->singleton(QuizService::class);
        $this->app->singleton(QuizRetrievalEngine::class);

        $this->app->singleton(ProviderRegistry::class, function ($app) {
            $registry = new ProviderRegistry();
            $settings = $app->make(RetrievalSettingsService::class);

            $registry->register($app->make(NcertRagProvider::class));
            $registry->register($app->make(PyqRagProvider::class));
            $registry->register(new ChunkRagProvider($settings, $app->make(\App\Services\RAGService::class), 'teacher_notes', 'Teacher Notes'));
            $registry->register(new ChunkRagProvider($settings, $app->make(\App\Services\RAGService::class), 'formula', 'Formula Sheets'));
            $registry->register($app->make(ExaSearchProvider::class));

            try {
                foreach (KnowledgeSource::where('is_active', true)->get() as $source) {
                    $registry->register(new ChunkRagProvider(
                        $settings,
                        $app->make(\App\Services\RAGService::class),
                        $source->provider_key,
                        $source->name,
                    ));
                }
            } catch (\Throwable) {
                // DB may be unavailable during install/migrate.
            }

            return $registry;
        });

        $this->app->singleton(RetrievalOrchestrator::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\PurgeExpiredTemporaryPdfs::class,
            ]);
        }
    }
}
