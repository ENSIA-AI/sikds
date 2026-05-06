<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Documents\Models\Document;
use App\Models\DocumentChunk;
use App\Services\Rag\Contracts\EmbeddingServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Generates embeddings for all chunks missing vectors, batching provider requests,
 * and updates the pgvector column. Dispatches finalization when done.
 */
class GenerateChunkEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 180, 420];

    public function __construct(
        protected int $documentId,
    ) {
        $this->onQueue('indexing');
    }

    public function handle(EmbeddingServiceInterface $embeddings): void
    {
        $document = Document::find($this->documentId);
        if (! $document || $document->deleted_at !== null) {
            return;
        }

        try {
            $configuredBatch = max(1, (int) config('rag.embedding.batch_size'));
            $tokensPerCallBudget = $this->tokensPerCallBudget();

            $candidates = DocumentChunk::query()
                ->forDocument($document->id)
                ->missingEmbeddings()
                ->orderBy('chunk_index')
                ->limit($configuredBatch)
                ->get(['id', 'content', 'token_count']);

            $chunkBatch = $this->selectChunksWithinTokenBudget($candidates, $tokensPerCallBudget, $configuredBatch);

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
                $delaySeconds = $this->embeddingDispatchDelaySeconds();
                self::dispatch($document->id)->onQueue('indexing')->delay(now()->addSeconds($delaySeconds));
                return;
            }

            FinalizeDocumentIndexJob::dispatch($document->id)->onQueue('indexing');
        } catch (\Throwable $e) {
            // Rate limiting: back off without marking the whole document as failed.
            $msg = $e->getMessage();
            if (str_contains($msg, 'HTTP 429') || str_contains($msg, 'RATE_TOKEN_LIMIT_EXCEEDED')) {
                $this->release((int) config('rag.embedding.backoff_429', 75));

                return;
            }

            $document->indexing_status = 'failed';
            $document->save();

            throw $e;
        }
    }

    /**
     * Max tokens we can send in one embedding call when RPM slots are fully used (TPM/RPM envelope).
     */
    protected function tokensPerCallBudget(): float
    {
        $tpm = (float) config('rag.embedding.tpm_limit');
        $headroom = (float) config('rag.embedding.rate_headroom');
        $rpm = (float) max(1, (int) config('rag.embedding.rpm_limit'));

        return max(1.0, ($tpm * $headroom) / $rpm);
    }

    /**
     * delay_seconds = ceil(60 / (rpm_limit × rate_headroom)), clamped between 1 and 60.
     */
    protected function embeddingDispatchDelaySeconds(): int
    {
        $rpm = (float) config('rag.embedding.rpm_limit');
        $headroom = (float) config('rag.embedding.rate_headroom');
        $effectiveRpm = max(0.001, $rpm * $headroom);

        return max(1, min(60, (int) ceil(60 / $effectiveRpm)));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, DocumentChunk>  $candidates
     * @return \Illuminate\Support\Collection<int, DocumentChunk>
     */
    protected function selectChunksWithinTokenBudget(Collection $candidates, float $budget, int $configuredBatch): Collection
    {
        $maxTokensHint = max(1, (int) config('rag.chunking.max_tokens'));
        $selected = collect();
        $sum = 0;

        foreach ($candidates as $chunk) {
            $tc = (int) ($chunk->token_count ?? 0);
            if ($tc < 1) {
                $tc = $maxTokensHint;
            }

            if ($selected->isNotEmpty() && $sum + $tc > $budget) {
                break;
            }

            $selected->push($chunk);
            $sum += $tc;

            if ($selected->count() >= $configuredBatch) {
                break;
            }
        }

        return $selected;
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
