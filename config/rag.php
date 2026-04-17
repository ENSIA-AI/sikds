<?php
return [
    'authorization' => [
        // Local/dev escape hatch: when false, RAG will search across all indexed+active documents
        // (still requires `rag.query` permission on the route).
        'enforce' => env('RAG_ENFORCE_AUTH', true),
    ],
    'chunking' => [
        'max_tokens' => 1000,
        'overlap_tokens' => 150,
        'min_tokens' => 50,
    ],
    'embedding' => [
        'model' => 'jina-embeddings-v5-text-small',
        'dimensions' => 1024,
        // Keep low to avoid Jina token rate limits during indexing bursts.
        'batch_size' => (int) env('RAG_EMBED_BATCH_SIZE', 10),
        'passage_task' => 'retrieval.passage',
        'query_task' => 'retrieval.query',
    ],
    'reranking' => [
        'model' => 'jina-reranker-v3',
        'top_n' => 6,
    ],
    'retrieval' => [
        'candidate_pool' => 20,
        'min_confidence' => 0.70,
    ],
    'jina' => [
        'base_url' => 'https://api.jina.ai/v1',
        'api_key' => env('JINA_API_KEY'),
        'timeout' => 30,
    ],
];