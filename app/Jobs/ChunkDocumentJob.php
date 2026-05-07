<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Documents\Models\Document;
use App\Models\DocumentChunk;
use App\Services\Rag\DocumentChunker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Reads extracted per-page text from cache, chunks it, replaces existing
 * chunks in the database for clean re-indexing, and dispatches embedding.
 */
class ChunkDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        protected int $documentId,
    ) {
        $this->onQueue('indexing');
    }

    public function handle(DocumentChunker $chunker): void
    {
        $document = Document::find($this->documentId);
        if (! $document || $document->deleted_at !== null) {
            return;
        }

        $cacheKey = "doc_raw_text_{$document->id}";

        try {
            $pages = Cache::get($cacheKey);
            if (! is_array($pages)) {
                throw new \RuntimeException('Missing raw extracted text in cache for chunking.');
            }

            $chunks = $chunker->chunk($pages, (string) $document->title);

            DB::table('document_chunks')->where('document_id', $document->id)->delete();

            $batch = [];
            foreach ($chunks as $c) {
                $batch[] = [
                    'document_id' => $document->id,
                    'chunk_index' => (int) ($c['metadata']['chunk_index'] ?? 0),
                    'content' => (string) ($c['content'] ?? ''),
                    'token_count' => (int) ($c['token_count'] ?? 0),
                    'metadata' => json_encode($c['metadata'] ?? [], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                ];

                if (count($batch) >= 200) {
                    DocumentChunk::insert($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                DocumentChunk::insert($batch);
            }

            // Populate tsvector for BM25 hybrid search (French + English + simple for Arabic).
            if (DB::getDriverName() === 'pgsql') {
                DB::statement(<<<'SQL'
                    UPDATE document_chunks
                    SET search_vector =
                        setweight(to_tsvector('french', coalesce(content, '')), 'A') ||
                        setweight(to_tsvector('english', coalesce(content, '')), 'B') ||
                        setweight(to_tsvector('simple', coalesce(content, '')), 'C')
                    WHERE document_id = ? AND search_vector IS NULL
                SQL, [$document->id]);
            }

            Cache::forget($cacheKey);

            GenerateChunkEmbeddingsJob::dispatch($document->id)->onQueue('indexing');
        } catch (\Throwable $e) {
            $document->indexing_status = 'failed';
            $document->save();

            throw $e;
        }
    }
}

