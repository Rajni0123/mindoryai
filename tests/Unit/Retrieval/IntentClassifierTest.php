<?php

namespace Tests\Unit\Retrieval;

use App\Services\Retrieval\DTO\RetrievalQuery;
use App\Services\Retrieval\IntentClassifier;
use Tests\TestCase;

class IntentClassifierTest extends TestCase
{
    protected function classifier(): IntentClassifier
    {
        return new IntentClassifier();
    }

    public function test_detects_current_affairs_as_exa_only(): void
    {
        $result = $this->classifier()->classify(new RetrievalQuery(
            question: 'What are today current affairs for UPSC?'
        ));

        $this->assertSame('current_affairs', $result->intent);
        $this->assertSame('exa_only', $result->strategy);
    }

    public function test_detects_hybrid_for_explain_and_latest_pyq(): void
    {
        $result = $this->classifier()->classify(new RetrievalQuery(
            question: 'Explain photosynthesis and show latest NEET PYQs'
        ));

        $this->assertSame('hybrid', $result->strategy);
    }

    public function test_detects_tutor_as_rag_only(): void
    {
        $result = $this->classifier()->classify(new RetrievalQuery(
            question: 'Explain the formula for kinetic energy'
        ));

        $this->assertSame('tutor', $result->intent);
        $this->assertSame('rag_only', $result->strategy);
    }
}
