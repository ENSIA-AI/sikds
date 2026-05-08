<?php
declare(strict_types=1);

namespace App\Services\Rag;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Smalot\PdfParser\Parser;

class PdfTextExtractor
{
    private const PDFTOTEXT_CANDIDATES = [
        '/usr/bin/pdftotext',
        '/usr/local/bin/pdftotext',
    ];

    private const PDFINFO_CANDIDATES = [
        '/usr/bin/pdfinfo',
        '/usr/local/bin/pdfinfo',
    ];

    public function __construct(
        protected Parser $parser,
    ) {}

    /** @return array<int, array{page:int, text:string}> */
    public function extract(string $filePath): array
    {
        $pages = $this->extractNative($filePath);

        if ($this->isSparse($pages)) {
            Log::info('PdfTextExtractor: sparse text detected, falling back to OCR', [
                'file' => basename($filePath),
            ]);
            $pages = $this->extractWithOcr($filePath);
        }

        return $pages;
    }

    // ─── Native extraction ────────────────────────────────────────────────────

    /** @return array<int, array{page:int, text:string}> */
    private function extractNative(string $filePath): array
    {
        $fallback = $this->extractWithPdftotext($filePath);
        if ($fallback !== null) {
            return $fallback;
        }

        try {
            $pdf = $this->parser->parseFile($filePath);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Unable to parse PDF file for text extraction.',
                previous: $e
            );
        }

        $pages = [];
        foreach ($pdf->getPages() as $i => $page) {
            $pages[] = ['page' => $i + 1, 'text' => (string) $page->getText()];
        }

        if ($pages === []) {
            throw new RuntimeException('PDF parsing succeeded but no pages were extracted.');
        }

        return $pages;
    }

    /** @return array<int, array{page:int, text:string}>|null */
    private function extractWithPdftotext(string $filePath): ?array
    {
        $bin = $this->findBinary(self::PDFTOTEXT_CANDIDATES);
        if ($bin === null) {
            return null;
        }

        $pageCount = $this->detectPageCount($filePath);

        if ($pageCount === null) {
            $single = $this->run([$bin, '-layout', '-enc', 'UTF-8', $filePath, '-']);
            if ($single === null) {
                return null;
            }
            return [['page' => 1, 'text' => $single]];
        }

        $pages = [];
        for ($page = 1; $page <= $pageCount; $page++) {
            $text = $this->run([
                $bin, '-f', (string) $page, '-l', (string) $page,
                '-layout', '-enc', 'UTF-8', $filePath, '-',
            ]);
            if ($text === null) {
                return null;
            }
            $pages[] = ['page' => $page, 'text' => $text];
        }

        return $pages === [] ? null : $pages;
    }

    // ─── OCR fallback ─────────────────────────────────────────────────────────

    /** @return array<int, array{page:int, text:string}> */
    private function extractWithOcr(string $filePath): array
    {
        if (! config('rag.ocr.enabled', true)) {
            Log::warning('PdfTextExtractor: OCR is disabled, returning sparse native result.');
            return [];
        }

        $url     = config('rag.ocr.url');
        $timeout = config('rag.ocr.timeout', 300);

        $b64 = base64_encode((string) file_get_contents($filePath));

        $response = Http::timeout($timeout)
            ->post($url . '/ocr', ['pdf_base64' => $b64]);

        if ($response->failed()) {
            throw new RuntimeException(
                'OCR service error: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $response->json('pages') ?? [];
    }

    // ─── Sparseness check ─────────────────────────────────────────────────────

    /** @param array<int, array{page:int, text:string}> $pages */
    private function isSparse(array $pages): bool
    {
        if ($pages === []) {
            return true;
        }

        $totalChars    = array_sum(array_map(fn(array $p) => strlen($p['text'] ?? ''), $pages));
        $avgPerPage    = $totalChars / count($pages);
        $minChars      = (int) config('rag.ocr.min_chars_per_page', 50);

        return $avgPerPage < $minChars;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** @param string[] $candidates */
    private function findBinary(array $candidates): ?string
    {
        foreach ($candidates as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }
        return null;
    }

    private function detectPageCount(string $filePath): ?int
    {
        $bin = $this->findBinary(self::PDFINFO_CANDIDATES);
        if ($bin === null) {
            return null;
        }

        $out = $this->run([$bin, $filePath]);
        if ($out === null) {
            return null;
        }

        if (preg_match('/^Pages:\s*(\d+)\s*$/mi', $out, $m) !== 1) {
            return null;
        }

        $count = (int) $m[1];
        return $count > 0 ? $count : null;
    }

    /** @param array<int, string> $cmd */
    private function run(array $cmd): ?string
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = @proc_open($cmd, $descriptors, $pipes);
        if (! is_resource($proc)) {
            return null;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        if ($exit !== 0) {
            return null;
        }

        return is_string($stdout) ? $stdout : null;
    }
}