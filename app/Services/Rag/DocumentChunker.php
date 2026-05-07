<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Services\Rag\Contracts\TokenEstimatorInterface;

/**
 * Cleans extracted PDF text and splits it into token-bounded overlapping chunks.
 */
class DocumentChunker
{
    public function __construct(
        protected TokenEstimatorInterface $tokenEstimator,
    ) {}
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
        $overlapText = '';
        $prevPage = null;

        foreach ($blocks as $block) {
            $page = (int) $block['page'];
            $segment = trim($block['text']);
            if ($segment === '') {
                continue;
            }

            if ($prevPage !== null && $page !== $prevPage && $overlapText !== '') {
                $halfTokens = $maxTokens / 2;
                if ($this->estimateTokenCount($overlapText) >= $halfTokens) {
                    $overlapText = '';
                }
            }
            $prevPage = $page;

            $subSegments = $this->enforceMaxTokens($segment, $maxTokens);

            foreach ($subSegments as $sub) {
                $sub = trim($sub);
                if ($sub === '') {
                    continue;
                }

                $content = $overlapText === ''
                    ? $sub
                    : trim($overlapText . ' ' . $sub);

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

                $overlapText = $this->extractOverlapTail($sub, $overlapTokens);
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

        $sentences = preg_split('/(?<=[.!?\x{061F}\x{06D4}\x{0964}\x{203C}\x{2047}\x{2048}\x{2049}])\s+/u', trim($segment)) ?: [];
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
        return $this->tokenEstimator->estimate($text);
    }

    /**
     * Extract the trailing portion of text worth approximately $targetTokens tokens.
     * This replaces the old word-array-based overlap with a token-budget-consistent approach.
     */
    protected function extractOverlapTail(string $text, int $targetTokens): string
    {
        if ($targetTokens <= 0 || $text === '') {
            return '';
        }

        // Walk backwards through words, accumulating until we reach the token budget.
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($words === []) {
            return '';
        }

        $tail = '';
        for ($i = count($words) - 1; $i >= 0; $i--) {
            $candidate = $tail === '' ? $words[$i] : ($words[$i] . ' ' . $tail);
            if ($this->estimateTokenCount($candidate) > $targetTokens && $tail !== '') {
                break;
            }
            $tail = $candidate;
        }

        return $tail;
    }
}
