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
 * Entry point for document indexing: validates state, sets status=processing,
 * writes an audit log, then dispatches the PDF extraction job.
 */
class IndexDocumentJob implements ShouldQueue
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
            if ($document->status !== 'active') {
                DB::table('document_chunks')->where('document_id', $document->id)->delete();

                return;
            }

            if ($document->indexing_status !== 'pending') {
                return;
            }

            $document->indexing_status = 'processing';
            $document->save();

            app(AuditService::class)->record(
                eventType: 'document.indexing.started',
                resourceType: 'document',
                resourceId: $document->id,
                metadata: [
                    'document_id' => $document->id,
                    'reference_number' => $document->reference_number,
                    'document_title' => $document->title,
                ],
            );

            ExtractPdfTextJob::dispatch($document->id)->onQueue('indexing');
        } catch (\Throwable $e) {
            $document->indexing_status = 'failed';
            $document->save();

            throw $e;
        }
    }
}

