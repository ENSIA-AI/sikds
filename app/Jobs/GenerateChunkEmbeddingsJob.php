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

    public string $queue = 'indexing';

    public function __construct(
        protected int $documentId,
    ) {}

    public function handle(JinaEmbeddingService $embeddings): void
    {
        $document = Document::find($this->documentId);
        if (! $document || $document->deleted_at !== null) {
            return;
        }

        try {
            $batchSize = (int) config('rag.embedding.batch_size');

            $chunks = DocumentChunk::query()
                ->forDocument($document->id)
                ->missingEmbeddings()
                ->orderBy('chunk_index')
                ->get(['id', 'content']);

            if ($chunks->isEmpty()) {
                FinalizeDocumentIndexJob::dispatch($document->id)->onQueue('indexing');
                return;
            }

            $chunks->chunk($batchSize)->each(function ($chunkBatch) use ($embeddings) {
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
            });

            FinalizeDocumentIndexJob::dispatch($document->id)->onQueue('indexing');
        } catch (\Throwable $e) {
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

