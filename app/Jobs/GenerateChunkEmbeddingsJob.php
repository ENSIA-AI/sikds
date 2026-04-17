<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Documents\Models\Document;
use App\Models\DocumentChunk;
use App\Services\Rag\JinaEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Generates embeddings for all chunks missing vectors, batching requests to Jina,
 * and updates the pgvector column. Dispatches finalization when done.
 */
class GenerateChunkEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        protected int $documentId,
    ) {
        $this->onQueue('indexing');
    }

    public function handle(JinaEmbeddingService $embeddings): void
    {
        $document = Document::find($this->documentId);
        if (! $document || $document->deleted_at !== null) {
            return;
        }

        try {
            $batchSize = (int) config('rag.embedding.batch_size');

            $chunkBatch = DocumentChunk::query()
                ->forDocument($document->id)
                ->missingEmbeddings()
                ->orderBy('chunk_index')
                ->limit(max(1, $batchSize))
                ->get(['id', 'content']);

            if ($chunkBatch->isEmpty()) {
                FinalizeDocumentIndexJob::dispatch($document->id)->onQueue('indexing');
                return;
            }

            $texts = $chunkBatch->pluck('content')->map(fn ($v) => (string) $v)->all();
            $vectors = $embeddings->embedPassages($texts);

            foreach ($chunkBatch->values() as $i => $row) {
                $vec = $vectors[$i] ?? null;
                if (! is_array($vec)) {
                    continue;
                }

                $literal = $this->vectorLiteral($vec);
                DB::statement(
                    'UPDATE document_chunks SET embedding = (?::vector) WHERE id = ?',
                    [$literal, (int) $row->id]
                );
            }

            $remaining = DocumentChunk::query()
                ->forDocument($document->id)
                ->missingEmbeddings()
                ->count();

            if ($remaining > 0) {
                // Throttle embedding throughput; prevents hitting provider TPM limits.
                self::dispatch($document->id)->onQueue('indexing')->delay(now()->addSeconds(2));
                return;
            }

            FinalizeDocumentIndexJob::dispatch($document->id)->onQueue('indexing');
        } catch (\Throwable $e) {
            // Jina token rate limiting: back off without marking the whole document as failed.
            $msg = $e->getMessage();
            if (str_contains($msg, 'HTTP 429') || str_contains($msg, 'RATE_TOKEN_LIMIT_EXCEEDED')) {
                $this->release(75);

                return;
            }

            $document->indexing_status = 'failed';
            $document->save();

            throw $e;
        }
    }

    /**
     * @param  array<int, float|int|string>  $vector
     */
    protected function vectorLiteral(array $vector): string
    {
        return '[' . implode(',', array_map(
            fn ($v) => rtrim(rtrim(sprintf('%.10F', (float) $v), '0'), '.'),
            $vector
        )) . ']';
    }
}

