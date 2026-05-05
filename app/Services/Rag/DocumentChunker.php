<?php

declare(strict_types=1);

namespace App\Services\Rag;

/**
 * Cleans extracted PDF text and splits it into token-bounded overlapping chunks.
 */
class DocumentChunker
{
    /**
     * @param  array<int, array{page:int, text:string}>  $pages
     * @return array<int, array{content:string, token_count:int, metadata:array{page:int, section_heading:?string, document_title:string, chunk_index:int}}>
     */
    public function chunk(array $pages, string $documentTitle): array
    {
        $maxTokens = (int) config('rag.chunking.max_tokens');
        $overlapTokens = (int) config('rag.chunking.overlap_tokens');
        $minTokens = (int) config('rag.chunking.min_tokens');

        $blocks = $this->buildParagraphBlocks($pages);

        $chunks = [];
        $chunkIndex = 0;
        $prevTokens = [];
        $prevPage = null;

        foreach ($blocks as $block) {
            $page = (int) $block['page'];
            $segment = trim($block['text']);
            if ($segment === '') {
                continue;
            }

            if ($prevPage !== null && $page !== $prevPage && $prevTokens !== []) {
                $halfTokens = $maxTokens / 2;
                if ($this->estimateTokenCount(implode(' ', $prevTokens)) >= $halfTokens) {
                    $prevTokens = [];
                }
            }
            $prevPage = $page;

            $subSegments = $this->enforceMaxTokens($segment, $maxTokens);

            foreach ($subSegments as $sub) {
                $sub = trim($sub);
                if ($sub === '') {
                    continue;
                }

                $subTokens = $this->tokenizeApprox($sub);
                $windowTokens = $prevTokens === [] ? $subTokens : array_merge($prevTokens, $subTokens);

                $content = trim(implode(' ', $windowTokens));
                $tokenCount = $this->estimateTokenCount($content);

                if ($tokenCount >= $minTokens) {
                    $chunks[] = [
                        'content' => $content,
                        'token_count' => $tokenCount,
                        'metadata' => [
                            'page' => $page,
                            'section_heading' => null,
                            'document_title' => $documentTitle,
                            'chunk_index' => $chunkIndex,
                        ],
                    ];

                    $chunkIndex++;
                }

                $prevTokens = array_slice($subTokens, max(0, count($subTokens) - $overlapTokens));
            }
        }

        return $chunks;
    }

    /**
     * One paragraph per block, tagged with its source page (PDF page signal preserved).
     *
     * @param  array<int, array{page:int, text:string}>  $pages
     * @return array<int, array{page:int, text:string}>
     */
    protected function buildParagraphBlocks(array $pages): array
    {
        $blocks = [];

        foreach ($pages as $p) {
            $pageNum = (int) ($p['page'] ?? 1);
            $raw = (string) ($p['text'] ?? '');
            $cleaned = $this->cleanText($raw);
            if ($cleaned === '') {
                continue;
            }

            $parts = preg_split('/\n\s*\n/', $cleaned) ?: [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $blocks[] = [
                        'page' => $pageNum,
                        'text' => $part,
                    ];
                }
            }
        }

        return $blocks;
    }

    protected function cleanText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Remove common hyphenation artifacts from PDF extraction.
        $text = preg_replace("/([A-Za-zÀ-ÿ])-\n([A-Za-zÀ-ÿ])/", '$1$2', $text) ?? $text;

        // Normalize whitespace.
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;

        // Strip repeated blank lines (max 1 consecutive blank line).
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @return array<int, string>
     */
    protected function enforceMaxTokens(string $segment, int $maxTokens): array
    {
        if ($this->estimateTokenCount($segment) <= $maxTokens) {
            return [$segment];
        }

        $sentences = preg_split("/(?<=[\\.!\\?])\\s+/", trim($segment)) ?: [];
        $out = [];
        $buf = '';

        foreach ($sentences as $s) {
            $candidate = trim($buf === '' ? $s : ($buf . ' ' . $s));
            if ($candidate === '') {
                continue;
            }

            if ($this->estimateTokenCount($candidate) > $maxTokens && $buf !== '') {
                $out[] = $buf;
                $buf = $s;
                continue;
            }

            $buf = $candidate;
        }

        if (trim($buf) !== '') {
            $out[] = $buf;
        }

        return $out;
    }

    protected function estimateTokenCount(string $text): int
    {
        // TODO: swap for a proper tokenizer (tiktoken-like) later.
        return (int) ceil(mb_strlen($text, 'UTF-8') / 4);
    }

    /**
     * @return array<int, string>
     */
    protected function tokenizeApprox(string $text): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        if ($text === '') {
            return [];
        }

        return explode(' ', $text);
    }
}
