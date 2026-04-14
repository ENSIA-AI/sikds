<?php

declare(strict_types=1);

namespace App\Services\Rag;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Calls Jina embeddings API to generate query/passage vectors.
 */
class JinaEmbeddingService
{
    public function __construct() {}

    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedPassages(array $texts): array
    {
        return $this->embedMany($texts, (string) config('rag.embedding.passage_task'));
    }

    /**
     * @return array<int, float>
     */
    public function embedQuery(string $text): array
    {
        $vectors = $this->embedMany([$text], (string) config('rag.embedding.query_task'));

        return $vectors[0] ?? [];
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    protected function embedMany(array $texts, string $task): array
    {
        $baseUrl = rtrim((string) config('rag.jina.base_url'), '/');
        $apiKey = (string) config('rag.jina.api_key');
        $timeout = (int) config('rag.jina.timeout', 30);

        $resp = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post($baseUrl . '/embeddings', [
                'model' => (string) config('rag.embedding.model'),
                'dimensions' => (int) config('rag.embedding.dimensions'),
                'task' => $task,
                'input' => array_values($texts),
            ]);

        if (! $resp->successful()) {
            throw new RuntimeException('Jina embeddings request failed (HTTP ' . $resp->status() . ').');
        }

        $json = $resp->json();
        $data = $json['data'] ?? null;
        if (! is_array($data)) {
            throw new RuntimeException('Jina embeddings response was missing data.');
        }

        $vectors = [];
        foreach ($data as $row) {
            $emb = $row['embedding'] ?? null;
            if (! is_array($emb)) {
                throw new RuntimeException('Jina embeddings response contained an invalid embedding.');
            }
            $vectors[] = array_map('floatval', $emb);
        }

        return $vectors;
    }
}

