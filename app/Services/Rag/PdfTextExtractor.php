<?php

declare(strict_types=1);

namespace App\Services\Rag;

use RuntimeException;
use Smalot\PdfParser\Parser;

/**
 * Extracts text per page from a local PDF file path.
 */
class PdfTextExtractor
{
    public function __construct(
        protected Parser $parser,
    ) {}

    /**
     * @return array<int, array{page:int, text:string}>
     */
    public function extract(string $filePath): array
    {
        try {
            $pdf = $this->parser->parseFile($filePath);
        } catch (\Throwable $e) {
            throw new RuntimeException('Unable to parse PDF file for text extraction.', previous: $e);
        }

        $pages = [];
        foreach ($pdf->getPages() as $i => $page) {
            $text = (string) $page->getText();
            $pages[] = [
                'page' => $i + 1,
                'text' => $text,
            ];
        }

        if ($pages === []) {
            throw new RuntimeException('PDF parsing succeeded but no pages were extracted.');
        }

        return $pages;
    }
}

