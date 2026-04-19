<?php
declare(strict_types=1);
namespace App\Services\Rag;

use RuntimeException;
use Smalot\PdfParser\Parser;

class PdfTextExtractor
{
    // ✅ ADD: known install locations for Poppler on Debian/Ubuntu
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
        // ✅ CHANGED: probe absolute paths instead of relying on PATH via which()
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

    private function detectPageCount(string $filePath): ?int
    {
        // ✅ CHANGED: same — probe absolute paths
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

    // ✅ NEW: replaces which() — no PATH needed, pure filesystem check
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
        fclose($pipes[2]); // ✅ drain stderr but don't use it for text output
        $exit = proc_close($proc);

        if ($exit !== 0) {
            return null;
        }

        // ✅ CHANGED: empty string is valid output (blank page), but null/false is failure
        return is_string($stdout) ? $stdout : null;
    }
}