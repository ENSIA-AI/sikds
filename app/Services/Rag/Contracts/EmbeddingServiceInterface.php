<?php

declare(strict_types=1);

namespace App\Services\Rag\Contracts;

/**
 * Generates vector embeddings for text passages and queries.
 */
interface EmbeddingServiceInterface
{
    /**
     * Embed one or more passages (documents/chunks) for indexing.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedPassages(array $texts): array;

    /**
     * Embed a single query string for retrieval.
     *
     * @return array<int, float>
     */
    public function embedQuery(string $text): array;
}
