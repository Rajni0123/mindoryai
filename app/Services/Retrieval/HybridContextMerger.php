<?php

namespace App\Services\Retrieval;

use App\Services\Retrieval\DTO\RetrievalResult;

class HybridContextMerger
{
    /**
     * @param  list<RetrievalResult>  $results
     */
    public function merge(array $results): RetrievalResult
    {
        $successful = array_values(array_filter($results, fn (RetrievalResult $r) => $r->success && $r->context !== ''));

        if ($successful === []) {
            return RetrievalResult::empty('hybrid', 'No retrieval context found.');
        }

        $parts = [];
        $sources = [];
        $providers = [];

        foreach ($successful as $index => $result) {
            $providers[] = $result->provider;
            $sources = array_merge($sources, $result->sources);
            $parts[] = '[Source: ' . ($result->provider ?: 'unknown') . "]\n" . trim($result->context);
        }

        return new RetrievalResult(
            success: true,
            context: implode("\n\n---\n\n", $parts),
            sources: array_values(array_unique($sources)),
            provider: implode('+', array_unique($providers)),
        );
    }
}
