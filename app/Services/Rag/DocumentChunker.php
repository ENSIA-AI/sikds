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

        [$fullText, $pageMap] = $this->joinPagesWithCharMap($pages);
        $cleanText = $this->cleanText($fullText);

        $segments = $this->splitIntoSegments($cleanText);

        $chunks = [];
        $chunkIndex = 0;
        $prevTokens = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $subSegments = $this->enforceMaxTokens($segment, $maxTokens);

            foreach ($subSegments as $sub) {
                $sub = trim($sub);
                if ($sub === '') {
                    continue;
                }

                $tokens = $this->tokenizeApprox($sub);

                if ($prevTokens !== []) {
                    $tokens = array_merge($prevTokens, $tokens);
                }

                $content = trim(implode(' ', $tokens));
                $tokenCount = $this->estimateTokenCount($content);

                if ($tokenCount >= $minTokens) {
                    $charPos = $this->approxCharPositionInFullText($content, $cleanText);
                    $page = $this->pageForCharPosition($charPos, $pageMap);
                    $heading = $this->detectHeading($sub);

                    $chunks[] = [
                        'content' => $content,
                        'token_count' => $tokenCount,
                        'metadata' => [
                            'page' => $page,
                            'section_heading' => $heading,
                            'document_title' => $documentTitle,
                            'chunk_index' => $chunkIndex,
                        ],
                    ];

                    $chunkIndex++;
                }

                $prevTokens = array_slice($tokens, max(0, count($tokens) - $overlapTokens));
            }
        }

        return $chunks;
    }

    /**
     * @param  array<int, array{page:int, text:string}>  $pages
     * @return array{0:string, 1:array<int, array{start:int, end:int, page:int}>}
     */
    protected function joinPagesWithCharMap(array $pages): array
    {
        $text = '';
        $map = [];
        $offset = 0;

        foreach ($pages as $p) {
            $pageText = (string) ($p['text'] ?? '');
            $pageText .= "\n\n";

            $start = $offset;
            $text .= $pageText;
            $offset = strlen($text);
            $end = $offset;

            $map[] = [
                'start' => $start,
                'end' => $end,
                'page' => (int) ($p['page'] ?? 1),
            ];
        }

        return [$text, $map];
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
    protected function splitIntoSegments(string $text): array
    {
        $headingPattern = "/^(#{1,3}\\s|Article\\s+\\d+|CHAPITRE|TITRE|Section\\s+\\d+)/im";

        $lines = preg_split("/\n/", $text) ?: [];
        $segments = [];
        $current = '';

        foreach ($lines as $line) {
            if (preg_match($headingPattern, $line) === 1) {
                if (trim($current) !== '') {
                    $segments[] = $current;
                }
                $current = $line . "\n";
                continue;
            }

            $current .= $line . "\n";
        }

        if (trim($current) !== '') {
            $segments[] = $current;
        }

        $final = [];
        foreach ($segments as $seg) {
            $parts = preg_split("/\n{2,}/", trim($seg)) ?: [];
            foreach ($parts as $p) {
                if (trim($p) !== '') {
                    $final[] = $p;
                }
            }
        }

        return $final;
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
        return (int) ceil(strlen($text) / 4);
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

    protected function detectHeading(string $text): ?string
    {
        $lines = preg_split("/\n/", trim($text)) ?: [];
        $first = trim((string) ($lines[0] ?? ''));
        if ($first === '') {
            return null;
        }

        if (preg_match("/^(#{1,3}\\s|Article\\s+\\d+|CHAPITRE|TITRE|Section\\s+\\d+)/i", $first) === 1) {
            return mb_substr($first, 0, 200);
        }

        return null;
    }

    protected function approxCharPositionInFullText(string $needle, string $haystack): int
    {
        $pos = mb_stripos($haystack, mb_substr($needle, 0, 120));

        return $pos === false ? 0 : (int) $pos;
    }

    /**
     * @param  array<int, array{start:int, end:int, page:int}>  $pageMap
     */
    protected function pageForCharPosition(int $charPos, array $pageMap): int
    {
        foreach ($pageMap as $range) {
            if ($charPos >= $range['start'] && $charPos < $range['end']) {
                return (int) $range['page'];
            }
        }

        return (int) ($pageMap[0]['page'] ?? 1);
    }
}

