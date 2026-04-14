<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Documents\Models\Document;
use App\Services\Rag\PdfTextExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Downloads the document PDF from SeaweedFS (S3), extracts per-page text,
 * stores it in cache for the chunking job, then dispatches chunking.
 */
class ExtractPdfTextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public string $queue = 'indexing';

    public function __construct(
        protected int $documentId,
    ) {}

    public function handle(PdfTextExtractor $extractor): void
    {
        $document = Document::find($this->documentId);
        if (! $document || $document->deleted_at !== null) {
            return;
        }

        $cacheKey = "doc_raw_text_{$document->id}";
        $tempFile = null;

        try {
            $pdfBytes = Storage::disk('s3')->get($document->file_path);

            $tempFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
                . 'sikds_doc_' . $document->id . '_' . uniqid('', true) . '.pdf';

            file_put_contents($tempFile, $pdfBytes);

            $pages = $extractor->extract($tempFile);

            Cache::put($cacheKey, $pages, now()->addHour());

            ChunkDocumentJob::dispatch($document->id)->onQueue('indexing');
        } catch (\Throwable $e) {
            $document->indexing_status = 'failed';
            $document->save();

            throw $e;
        } finally {
            if ($tempFile && is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }
}

