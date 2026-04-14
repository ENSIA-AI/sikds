<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Documents\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Entry point for document indexing: validates state, sets status=processing,
 * writes an audit log, then dispatches the PDF extraction job.
 */
class IndexDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public string $queue = 'indexing';

    public function __construct(
        protected int $documentId,
    ) {}

    public function handle(): void
    {
        $document = Document::find($this->documentId);
        if (! $document || $document->deleted_at !== null) {
            return;
        }

        try {
            if ($document->indexing_status !== 'pending') {
                return;
            }

            $document->indexing_status = 'processing';
            $document->save();

            AuditLog::create([
                'event_type' => 'DOCUMENT_INDEXING_STARTED',
                'user_id' => null,
                'user_email' => null,
                'resource_type' => 'document',
                'resource_id' => $document->id,
                'metadata' => [
                    'document_id' => $document->id,
                    'reference_number' => $document->reference_number,
                ],
                'result' => 'success',
                'ip_address' => null,
                'user_agent' => null,
                'created_at' => now(),
            ]);

            ExtractPdfTextJob::dispatch($document->id)->onQueue('indexing');
        } catch (\Throwable $e) {
            $document->indexing_status = 'failed';
            $document->save();

            throw $e;
        }
    }
}

