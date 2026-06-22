<?php

namespace Tests\Unit\Retrieval;

use App\Services\Retrieval\DTO\IntentResult;
use App\Services\Retrieval\RetrievalRouter;
use App\Services\Retrieval\RetrievalSettingsService;
use PHPUnit\Framework\TestCase;

class RetrievalRouterTest extends TestCase
{
    public function test_hybrid_disabled_falls_back_to_rag_only(): void
    {
        $settings = $this->createMock(RetrievalSettingsService::class);
        $settings->method('isHybridEnabled')->willReturn(false);
        $settings->method('isExistingRagEnabled')->willReturn(true);

        $router = new RetrievalRouter($settings);
        $route = $router->resolveRoute(new IntentResult('current_affairs', 'exa_only'));

        $this->assertSame('rag_only', $route);
    }

    public function test_exa_only_when_enabled(): void
    {
        $settings = $this->createMock(RetrievalSettingsService::class);
        $settings->method('isHybridEnabled')->willReturn(true);
        $settings->method('isExaEnabled')->willReturn(true);

        $router = new RetrievalRouter($settings);
        $route = $router->resolveRoute(new IntentResult('current_affairs', 'exa_only'));

        $this->assertSame('exa_only', $route);
    }
}
