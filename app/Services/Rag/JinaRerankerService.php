<?php

declare(strict_types=1);

namespace App\Services\Rag;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Calls Jina reranker API to reorder candidate chunks by relevance.
 */
class JinaRerankerService
{
    public function __construct() {}

    /**
     * @param  array<int, array<string, mixed>>  $chunks
     * @return array<int, array<string, mixed>>
     */
    public function rerank(string $query, array $chunks, int $topN): array
    {
        $baseUrl = rtrim((string) config('rag.jina.base_url'), '/');
        $apiKey = (string) config('rag.jina.api_key');
        $timeout = (int) config('rag.jina.timeout', 30);

        $documents = array_map(
            fn ($c) => (string) ($c['content'] ?? ''),
            $chunks
        );

        $resp = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post($baseUrl . '/rerank', [
                'model' => (string) config('rag.reranking.model'),
                'query' => $query,
                'documents' => $documents,
                'top_n' => $topN,
            ]);

        if (! $resp->successful()) {
            throw new RuntimeException('Jina reranker request failed (HTTP ' . $resp->status() . ').');
        }

        $json = $resp->json();
        $results = $json['results'] ?? null;
        if (! is_array($results)) {
            throw new RuntimeException('Jina reranker response was missing results.');
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
}

