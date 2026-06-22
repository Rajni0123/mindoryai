<?php

namespace App\Services;

use App\Models\DocumentChunk;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RAGService
{
    protected string $apiKey;
    protected string $embeddingModel = 'text-embedding-3-small';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    /**
     * Get relevant context from documents based on question
     *
     * @param string $question User's question
     * @param string|null $classLevel Student's class level (e.g., "Class 5", "Class 10")
     * @param string|null $subject Subject filter (e.g., "Mathematics", "Science")
     * @param int $topK Number of top chunks to retrieve
     * @param float $similarityThreshold Minimum similarity score (0-1)
     * @return array
     */
    public function getRelevantContext(
        string $question,
        ?string $classLevel = null,
        ?string $subject = null,
        int $topK = 5,
        float $similarityThreshold = 0.6
    ): array
    {
        return $this->getRelevantContextForProvider(
            $question,
            null,
            $classLevel,
            $subject,
            $topK,
            $similarityThreshold
        );
    }

    /**
     * Provider-scoped retrieval for modular RAG sources.
     */
    public function getRelevantContextForProvider(
        string $question,
        ?string $providerKey = null,
        ?string $classLevel = null,
        ?string $subject = null,
        int $topK = 5,
        float $similarityThreshold = 0.6
    ): array
    {
        try {
            // Generate embedding for the question
            $questionEmbedding = $this->generateEmbedding($question);

            if (!$questionEmbedding) {
                return [
                    'success' => false,
                    'context' => '',
                    'sources' => [],
                    'message' => 'Failed to generate question embedding'
                ];
            }

            // Search for similar chunks with class and subject filtering
            $relevantChunks = $this->findSimilarChunks(
                $questionEmbedding,
                $classLevel,
                $subject,
                $topK,
                $similarityThreshold,
                $providerKey
            );

            if (empty($relevantChunks)) {
                // No relevant context found - return specific message
                $filters = [];
                if ($classLevel) $filters[] = $classLevel;
                if ($subject) $filters[] = $subject;

                $filterText = !empty($filters) ? implode(' ', $filters) : 'selected';

                return [
                    'success' => false,
                    'context' => '',
                    'sources' => [],
                    'message' => "This question is outside the {$filterText} class syllabus."
                ];
            }

            // Format context from chunks
            $context = $this->formatContext($relevantChunks);
            $sources = $this->extractSources($relevantChunks);

            return [
                'success' => true,
                'context' => $context,
                'sources' => $sources,
                'chunks_count' => count($relevantChunks),
                'class_level' => $classLevel,
                'subject' => $subject
            ];

        } catch (\Exception $e) {
            Log::error('RAG Service Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'context' => '',
                'sources' => [],
                'message' => 'Error retrieving context: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate embedding for text using OpenAI
     *
     * @param string $text
     * @return array|null
     */
    protected function generateEmbedding(string $text): ?array
    {
        try {
            // Cache key based on text hash
            $cacheKey = 'embedding_' . md5($text);

            // Check cache first (embeddings don't change)
            return Cache::remember($cacheKey, 86400 * 7, function () use ($text) {
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(30)
                    ->post('https://api.openai.com/v1/embeddings', [
                        'model' => $this->embeddingModel,
                        'input' => $text,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['data'][0]['embedding'] ?? null;
                }

                Log::error('OpenAI Embedding API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return null;
            });

        } catch (\Exception $e) {
            Log::error('Embedding generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find similar chunks using cosine similarity with class and subject filtering
     *
     * @param array $queryEmbedding
     * @param string|null $classLevel
     * @param string|null $subject
     * @param int $topK
     * @param float $similarityThreshold
     * @return array
     */
    protected function findSimilarChunks(
        array $queryEmbedding,
        ?string $classLevel,
        ?string $subject,
        int $topK,
        float $similarityThreshold,
        ?string $providerKey = null
    ): array
    {
        // Start with chunks that have embeddings
        $query = DocumentChunk::whereNotNull('embedding');

        if ($providerKey) {
            $query->where('provider_key', $providerKey);
        }

        // STRICT FILTERING: Only return chunks matching class AND subject
        if ($classLevel || $subject) {
            $query->where(function ($q) use ($classLevel, $subject) {
                // Filter by class level
                if ($classLevel) {
                    $q->where(function ($classQuery) use ($classLevel) {
                        $classQuery->whereJsonContains('metadata->class_level', $classLevel)
                            ->orWhereJsonContains('metadata->suitable_for_classes', $classLevel)
                            ->orWhereJsonContains('metadata->class', $classLevel);
                    });
                }

                // Filter by subject
                if ($subject) {
                    $q->where(function ($subjectQuery) use ($subject) {
                        $subjectQuery->whereJsonContains('metadata->subject', $subject)
                            ->orWhereJsonContains('metadata->subjects', $subject)
                            ->orWhereJsonContains('metadata->topic', $subject);
                    });
                }
            });
        }

        $chunks = $query->get();

        // NO FALLBACK - If no chunks found with filters, return empty
        // This ensures we only answer from the selected class/subject
        if ($chunks->isEmpty()) {
            Log::info('No chunks found for filters', [
                'class' => $classLevel,
                'subject' => $subject
            ]);
            return [];
        }

        // Calculate similarity scores
        $scoredChunks = [];
        foreach ($chunks as $chunk) {
            if (!$chunk->embedding) {
                continue;
            }

            $similarity = $this->cosineSimilarity($queryEmbedding, $chunk->embedding);

            // Only include chunks above similarity threshold
            if ($similarity >= $similarityThreshold) {
                $scoredChunks[] = [
                    'chunk' => $chunk,
                    'similarity' => $similarity
                ];
            }
        }

        // If no chunks meet similarity threshold, return empty
        if (empty($scoredChunks)) {
            Log::info('No chunks above similarity threshold', [
                'threshold' => $similarityThreshold,
                'class' => $classLevel,
                'subject' => $subject
            ]);
            return [];
        }

        // Sort by similarity (descending) and get top K
        usort($scoredChunks, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        $topChunks = array_slice($scoredChunks, 0, $topK);

        // Log similarity scores for debugging
        Log::info('RAG found relevant chunks', [
            'count' => count($topChunks),
            'top_similarity' => $topChunks[0]['similarity'] ?? 0,
            'class' => $classLevel,
            'subject' => $subject
        ]);

        return array_map(function ($item) {
            return $item['chunk'];
        }, $topChunks);
    }

    /**
     * Calculate cosine similarity between two vectors
     *
     * @param array $vec1
     * @param array $vec2
     * @return float
     */
    protected function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;

        $length = min(count($vec1), count($vec2));

        for ($i = 0; $i < $length; $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] * $vec1[$i];
            $magnitude2 += $vec2[$i] * $vec2[$i];
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Format chunks into context string
     *
     * @param array $chunks
     * @return string
     */
    protected function formatContext(array $chunks): string
    {
        $contextParts = [];

        foreach ($chunks as $index => $chunk) {
            $metadata = $chunk->metadata ?? [];
            $documentTitle = $metadata['document_title'] ?? 'NCERT Material';

            $contextParts[] = "[Context " . ($index + 1) . " from {$documentTitle}]\n" . trim($chunk->content);
        }

        return implode("\n\n---\n\n", $contextParts);
    }

    /**
     * Extract source information from chunks
     *
     * @param array $chunks
     * @return array
     */
    protected function extractSources(array $chunks): array
    {
        $sources = [];

        foreach ($chunks as $chunk) {
            $metadata = $chunk->metadata ?? [];
            $documentTitle = $metadata['document_title'] ?? 'Unknown';

            if (!in_array($documentTitle, $sources)) {
                $sources[] = $documentTitle;
            }
        }

        return $sources;
    }

    /**
     * Generate and store embedding for a document chunk
     *
     * @param DocumentChunk $chunk
     * @return bool
     */
    public function generateChunkEmbedding(DocumentChunk $chunk): bool
    {
        try {
            $embedding = $this->generateEmbedding($chunk->content);

            if ($embedding) {
                $chunk->embedding = $embedding;
                $chunk->save();
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Chunk embedding generation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Batch generate embeddings for all chunks without embeddings
     *
     * @param int $batchSize
     * @return array
     */
    public function generateMissingEmbeddings(int $batchSize = 100): array
    {
        $chunks = DocumentChunk::whereNull('embedding')
            ->limit($batchSize)
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($chunks as $chunk) {
            if ($this->generateChunkEmbedding($chunk)) {
                $processed++;
            } else {
                $failed++;
            }

            // Small delay to avoid rate limits
            usleep(100000); // 100ms delay
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => $chunks->count()
        ];
    }
}
