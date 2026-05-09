<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Services\Rag\Contracts\RerankerServiceInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Calls any OpenAI-compatible rerank endpoint.
 *
 * Expected API contract (POST /v1/rerank or /rerank):
 *   Request:  { model, query, documents: string[], top_n }
 *   Response: { results: [ { index, relevance_score } ] }
 */
class GenericRerankerService implements RerankerServiceInterface
{
    public function __construct() {}

    /**
     * @param  array<int, array<string, mixed>>  $chunks
     * @return array<int, array<string, mixed>>
     */
    public function rerank(string $query, array $chunks, int $topN): array
    {
        $baseUrl = rtrim((string) config('rag.reranking.base_url'), '/');
        $apiKey = (string) config('rag.reranking.api_key', '');
        $timeout = (int) config('rag.reranking.timeout', 60);
        $model = (string) config('rag.reranking.model');

        $documents = array_map(
            fn ($c) => (string) ($c['content'] ?? ''),
            $chunks
        );

        $request = Http::timeout($timeout)->acceptJson();

        if ($apiKey !== '') {
            $request = $request->withToken($apiKey);
        }

        $resp = $request->post($this->endpointUrl($baseUrl, 'rerank'), [
            'model' => $model,
            'query' => $query,
            'documents' => $documents,
            'top_n' => $topN,
        ]);

        if (! $resp->successful()) {
            throw new RuntimeException('Reranker request failed (HTTP ' . $resp->status() . ').');
        }

        $json = $resp->json();
        $results = $json['results'] ?? null;
        if (! is_array($results)) {
            throw new RuntimeException('Reranker response was missing results.');
        }

        $ranked = [];
        foreach ($results as $r) {
            $idx = $r['index'] ?? null;
            $score = $r['relevance_score'] ?? null;
            if (! is_int($idx) || ! isset($chunks[$idx])) {
                continue;
            }

            $item = $chunks[$idx];
            $item['relevance_score'] = (float) $score;
            $ranked[] = $item;
        }

        usort($ranked, fn ($a, $b) => ($b['relevance_score'] <=> $a['relevance_score']));

        return array_slice($ranked, 0, $topN);
    }

    protected function endpointUrl(string $baseUrl, string $endpoint): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        return str_ends_with($baseUrl, '/' . $endpoint)
            ? $baseUrl
            : $baseUrl . '/' . $endpoint;
    }
}
