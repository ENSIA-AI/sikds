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

        $payload = [
            'model' => (string) config('rag.embedding.model'),
            'task' => $task,
            'input' => array_values($texts),
        ];

        // Some Jina models reject an explicit "dimensions" field with HTTP 422.
        // Let the API decide the default dimensions for the chosen model.
        $resp = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post($baseUrl . '/embeddings', [
                ...$payload,
            ]);

        if (! $resp->successful()) {
            $body = $resp->body();
            $msg = 'Jina embeddings request failed (HTTP ' . $resp->status() . ').';
            if (is_string($body) && trim($body) !== '') {
                $msg .= ' Response: ' . mb_substr(trim($body), 0, 1500);
            }

            throw new RuntimeException($msg);
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

