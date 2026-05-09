<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Services\Rag\Contracts\EmbeddingServiceInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Calls any OpenAI-compatible embedding endpoint.
 *
 * Expected API contract (POST <base_url>/embeddings):
 *   Request:  { model, input: string[], dimensions?: int, task?: string }
 *   Response: { data: [ { embedding: float[], index: int } ] }
 */
class GenericEmbeddingService implements EmbeddingServiceInterface
{
    public function __construct() {}

    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedPassages(array $texts): array
    {
        $task = (string) config('rag.embedding.passage_task', '');

        return $this->embedMany($texts, $task);
    }

    /**
     * @return array<int, float>
     */
    public function embedQuery(string $text): array
    {
        $task = (string) config('rag.embedding.query_task', '');
        $vectors = $this->embedMany([$text], $task);

        return $vectors[0] ?? [];
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    protected function embedMany(array $texts, string $task = ''): array
    {
        $baseUrl = rtrim((string) config('rag.embedding.base_url'), '/');
        $apiKey = (string) config('rag.embedding.api_key', '');
        $timeout = (int) config('rag.embedding.timeout', 60);
        $model = (string) config('rag.embedding.model');
        $dimensions = (int) config('rag.embedding.dimensions', 0);

        $payload = [
            'model' => $model,
            'input' => array_values($texts),
        ];

        if ($dimensions > 0) {
            $payload['dimensions'] = $dimensions;
        }

        if ($task !== '') {
            $payload['task'] = $task;
        }

        $request = Http::timeout($timeout)->acceptJson();

        if ($apiKey !== '') {
            $request = $request->withToken($apiKey);
        }

        $resp = $request->post($this->endpointUrl($baseUrl, 'embeddings'), $payload);

        if (! $resp->successful()) {
            $body = $resp->body();
            $msg = 'Embedding request failed (HTTP ' . $resp->status() . ').';
            if (is_string($body) && trim($body) !== '') {
                $msg .= ' Response: ' . mb_substr(trim($body), 0, 1500);
            }

            throw new RuntimeException($msg);
        }

        $json = $resp->json();
        $data = $json['data'] ?? null;
        if (! is_array($data)) {
            throw new RuntimeException('Embedding response was missing data.');
        }

        $vectors = [];
        foreach ($data as $row) {
            $emb = $row['embedding'] ?? null;
            if (! is_array($emb)) {
                throw new RuntimeException('Embedding response contained an invalid embedding.');
            }
            $vectors[] = array_map('floatval', $emb);
        }

        return $vectors;
    }

    protected function endpointUrl(string $baseUrl, string $endpoint): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        return str_ends_with($baseUrl, '/' . $endpoint)
            ? $baseUrl
            : $baseUrl . '/' . $endpoint;
    }
}
