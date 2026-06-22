<?php

namespace Tests\Unit\Retrieval;

use App\Services\Retrieval\DTO\RetrievalQuery;
use App\Services\Retrieval\HybridContextMerger;
use App\Services\Retrieval\DTO\RetrievalResult;
use PHPUnit\Framework\TestCase;

class HybridContextMergerTest extends TestCase
{
    public function test_merges_successful_results(): void
    {
        $merger = new HybridContextMerger();
        $merged = $merger->merge([
            new RetrievalResult(true, 'NCERT context', ['Biology Ch1'], 'ncert'),
            new RetrievalResult(true, 'Exa context', ['gov.in'], 'exa'),
        ]);

        $this->assertTrue($merged->success);
        $this->assertStringContainsString('NCERT context', $merged->context);
        $this->assertStringContainsString('Exa context', $merged->context);
        $this->assertSame('ncert+exa', $merged->provider);
    }

    public function test_returns_empty_when_no_results(): void
    {
        $merger = new HybridContextMerger();
        $merged = $merger->merge([RetrievalResult::empty('ncert')]);

        $this->assertFalse($merged->success);
    }
}
