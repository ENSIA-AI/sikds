<?php
return [
    'chunking' => [
        'max_tokens'    => 1000,   // hard ceiling per chunk
        'overlap_tokens'=> 150,    // carry-over between chunks
        'min_tokens'    => 50,     // discard noise chunks below this
    ],
    'embedding' => [
        'model'         => 'jina-embeddings-v5',   // swap to v5 when available
        'dimensions'    => 1024,                    // must match vector(N) in migration
        'batch_size'    => 50,                      // chunks per Jina API call
        'passage_task'  => 'retrieval.passage',
        'query_task'    => 'retrieval.query',
    ],
    'reranking' => [
        'model'         => 'jina-reranker-v3',
        'top_n'         => 6,                       // reranker output → LLM context
    ],
    'retrieval' => [
        'candidate_pool'=> 20,      // pgvector returns this many before reranking
        'min_confidence'=> 0.70,    // below this → refuse to answer
    ],
    'jina' => [
        'base_url'      => 'https://api.jina.ai/v1',
        'api_key'       => env('JINA_API_KEY'),
        'timeout'       => 30,
    ],
];