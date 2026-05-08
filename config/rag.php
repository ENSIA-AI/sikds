<?php

return [
    'api_key' => env('RAG_API_KEY', ''),

    'authorization' => [
        'enforce' => env('RAG_ENFORCE_AUTH', true),
    ],
    /*
    |--------------------------------------------------------------------------
    | OCR (scanned PDF fallback)
    |--------------------------------------------------------------------------
    |
    | Called when native text extraction yields too little text per page.
    | Points to the Surya FastAPI microservice inside the Docker network.
    |
    */

    'ocr' => [
        'enabled'           => filter_var(env('RAG_OCR_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'url'               => env('OCR_SERVICE_URL', 'http://surya:8100'),
        'timeout'           => (int) env('OCR_TIMEOUT', 300),
        'min_chars_per_page'=> (int) env('OCR_MIN_CHARS_PER_PAGE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chunking
    |--------------------------------------------------------------------------
    */
    'chunking' => [
        'max_tokens' => (int) env('CHUNK_MAX_TOKENS', 400),
        'overlap_tokens' => (int) env('CHUNK_OVERLAP_TOKENS', 60),
        'min_tokens' => (int) env('CHUNK_MIN_TOKENS', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding
    |--------------------------------------------------------------------------
    |
    | Any OpenAI-compatible embeddings endpoint.
    | Set the URL, model, API key, and vector dimensions for your provider.
    |
    */
    'embedding' => [
        'base_url' => env('RAG_EMBEDDING_URL', ''),
        'api_key' => env('RAG_EMBEDDING_API_KEY'),
        'model' => env('RAG_EMBEDDING_MODEL', ''),
        'dimensions' => (int) env('RAG_EMBEDDING_DIMS', 768),
        'batch_size' => (int) env('RAG_EMBEDDING_BATCH_SIZE', 32),
        'timeout' => (int) env('RAG_EMBEDDING_TIMEOUT', 60),
        // Optional task hints for providers that support them.
        'passage_task' => env('RAG_EMBEDDING_PASSAGE_TASK', ''),
        'query_task' => env('RAG_EMBEDDING_QUERY_TASK', ''),
        'rpm_limit' => (int) env('RAG_EMBEDDING_RPM', 100),
        'tpm_limit' => (int) env('RAG_EMBEDDING_TPM', 100000),
        'rate_headroom' => (float) env('RAG_EMBEDDING_RATE_HEADROOM', 0.80),
        'backoff_429' => (int) env('RAG_EMBEDDING_BACKOFF_429', 75),
        'task_param' => env('RAG_EMBEDDING_TASK_PARAM', 'task_type'),
        'task_mode' => env('RAG_EMBEDDING_TASK_MODE', 'param'), // 'param' or 'prefix'
    ],

    /*
    |--------------------------------------------------------------------------
    | Reranking
    |--------------------------------------------------------------------------
    |
    | Any OpenAI-compatible rerank endpoint.
    |
    */
    'reranking' => [
        'enabled' => filter_var(env('RAG_RERANKER_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'base_url' => env('RAG_RERANKER_URL', ''),
        'api_key' => env('RAG_RERANKER_API_KEY'),
        'model' => env('RAG_RERANKER_MODEL', ''),
        'top_n' => (int) env('RAG_RERANKER_TOP_N', 6),
        'timeout' => (int) env('RAG_RERANKER_TIMEOUT', 60),
    ],

    'retrieval' => [
        'candidate_pool' => (int) env('RAG_CANDIDATE_POOL', 20),
        'min_confidence' => (float) env('RAG_MIN_CONFIDENCE', 0.10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hybrid Search (Dense + BM25 via RRF)
    |--------------------------------------------------------------------------
    |
    | When enabled, both semantic (pgvector) and lexical (tsvector) searches
    | are run and merged using Reciprocal Rank Fusion before reranking.
    |
    */
    'hybrid' => [
        'enabled' => (bool) env('RAG_HYBRID_SEARCH', true),
        'rrf_k' => (int) env('RAG_RRF_K', 60),
        'bm25_candidate_pool' => (int) env('RAG_BM25_POOL', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM (generation)
    |--------------------------------------------------------------------------
    */
    'llm' => [
        'provider' => env('RAG_LLM_PROVIDER', 'groq'),
        'model' => env('RAG_LLM_MODEL', 'llama-3.3-70b-versatile'),
        'api_key' => env('LLM_API_KEY', ''),
        'url' => env('RAG_LLM_URL', ''),
        'timeout' => (int) env('RAG_LLM_TIMEOUT',120),
    ],
];
