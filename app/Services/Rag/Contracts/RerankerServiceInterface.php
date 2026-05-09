<?php

declare(strict_types=1);

namespace App\Services\Rag\Contracts;

/**
 * Reranks candidate chunks by relevance to a query.
 */
interface RerankerServiceInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $chunks
     * @return array<int, array<string, mixed>>
     */
    public function rerank(string $query, array $chunks, int $topN): array;
}
