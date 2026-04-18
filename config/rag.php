<?php
return [
    'authorization' => [
        // Local/dev escape hatch: when false, RAG will search across all indexed+active documents
        // (still requires `rag.query` permission on the route).
        'enforce' => env('RAG_ENFORCE_AUTH', true),
    ],
    'chunking' => [
        'max_tokens' => (int) env('CHUNK_MAX_TOKENS', env('RAG_CHUNK_MAX_TOKENS', 400)),
        'overlap_tokens' => (int) env('CHUNK_OVERLAP_TOKENS', env('RAG_CHUNK_OVERLAP_TOKENS', 60)),
        'min_tokens' => (int) env('CHUNK_MIN_TOKENS', 50),
    ],
    'embedding' => [
        'model' => 'jina-embeddings-v5-text-small',
        'dimensions' => 1024,
        'batch_size' => (int) env('EMBEDDING_BATCH_SIZE', env('RAG_EMBED_BATCH_SIZE', 32)),
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
        'timeout' => (int) env('JINA_TIMEOUT', 30),
        'rpm_limit' => (int) env('JINA_RPM_LIMIT', 100),
        'tpm_limit' => (int) env('JINA_TPM_LIMIT', 100000),
        'rate_headroom' => (float) env('JINA_RATE_HEADROOM', 0.80),
        'backoff_429' => (int) env('JINA_BACKOFF_429', 75),
    ],
];
