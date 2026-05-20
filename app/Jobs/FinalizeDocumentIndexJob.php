<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Documents\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Finalizes indexing by checking for missing embeddings and updating the
 * document's indexing_status accordingly, writing an audit log entry.
 */
class FinalizeDocumentIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        protected int $documentId,
    ) {
        $this->onQueue('indexing');
    }

    public function handle(): void
    {
        $document = Document::find($this->documentId);
        if (! $document || $document->deleted_at !== null) {
            return;
        }

        try {
            $missing = (int) DB::table('document_chunks')
                ->where('document_id', $document->id)
                ->whereNull('embedding')
                ->count();

            if ($missing > 0) {
                $document->indexing_status = 'failed';
                $document->save();

                app(AuditService::class)->record(
                    eventType: 'document.indexing.failed',
                    result: 'failed',
                    resourceType: 'document',
                    resourceId: $document->id,
                    metadata: [
                        'document_id' => $document->id,
                        'reference_number' => $document->reference_number,
                        'document_title' => $document->title,
                        'missing_embeddings' => $missing,
                        'reason' => 'missing_embeddings',
                        'message' => 'Document indexing failed because embeddings are missing.',
                    ],
                );

                return;
            }

            $document->indexing_status = 'indexed';
            $document->save();

            app(AuditService::class)->record(
                eventType: 'document.indexing.completed',
                resourceType: 'document',
                resourceId: $document->id,
                metadata: [
                    'document_id' => $document->id,
                    'reference_number' => $document->reference_number,
                    'document_title' => $document->title,
                ],
            );
        } catch (\Throwable $e) {
            $document->indexing_status = 'failed';
            $document->save();

            throw $e;
        }
    }
}

