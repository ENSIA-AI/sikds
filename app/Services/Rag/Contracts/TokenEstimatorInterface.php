<?php

declare(strict_types=1);

namespace App\Services\Rag\Contracts;

/**
 * Estimates the token count for a given text string.
 *
 * Tier 1 implementations use the real model tokenizer (via HTTP or FFI).
 * Tier 2 implementations use calibrated per-script character heuristics.
 */
interface TokenEstimatorInterface
{
    /**
     * Estimate the number of tokens the embedding model will produce for this text.
     */
    public function estimate(string $text): int;
}
