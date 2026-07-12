<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Audit\Services\AuditService;
use Illuminate\Console\Command;

/**
 * Walk the audit_logs hash chain and report the first break.
 *
 * Each chained row stores:
 *   previous_hash — the row_hash of the preceding audit row (GENESIS_HASH for
 *                   the first chained row),
 *   row_hash      — sha256(previous_hash | canonical core payload), computed
 *                   by AuditService::hashRow().
 *
 * A "break" is any of:
 *   - a row whose recomputed hash differs from its stored row_hash (its core
 *     payload was altered), or
 *   - a row whose previous_hash does not match the row_hash of the row before
 *     it (a row was deleted, inserted out-of-band, or re-ordered).
 *
 * Rows written before the chain existed have row_hash = NULL; they are counted
 * as "legacy" and skipped — the chain is verified from the first hashed row.
 */
class AuditVerifyChainCommand extends Command
{
    protected $signature = 'audit:verify-chain
                            {--from-id=0 : Start verification at this audit log id}';

    protected $description = 'Verify the tamper-evident hash chain of the audit log and report the first break';

    public function handle(): int
    {
        $fromId = max(0, (int) $this->option('from-id'));

        $verified = 0;
        $legacy = 0;
        $expectedPreviousHash = null; // null until the first chained row is seen

        // When starting mid-chain, anchor on the last chained row at or before
        // the requested id so the first verified row links correctly.
        if ($fromId > 0) {
            $expectedPreviousHash = AuditLog::query()
                ->where('id', '<=', $fromId)
                ->whereNotNull('row_hash')
                ->orderByDesc('id')
                ->value('row_hash');
        }
        $brokenAt = null;
        $reason = '';

        AuditLog::query()
            ->where('id', '>', $fromId)
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$verified, &$legacy, &$expectedPreviousHash, &$brokenAt, &$reason): bool {
                foreach ($rows as $row) {
                    if ($row->row_hash === null) {
                        // Pre-chain legacy row: nothing to verify. A hashed row
                        // appearing later anchors the chain at GENESIS_HASH.
                        $legacy++;

                        continue;
                    }

                    $expected = $expectedPreviousHash ?? AuditService::GENESIS_HASH;

                    if (! hash_equals($expected, (string) $row->previous_hash)) {
                        $brokenAt = $row->id;
                        $reason = "previous_hash mismatch (expected {$expected}, stored {$row->previous_hash}) — a row was deleted, inserted out-of-band, or re-ordered before this one";

                        return false;
                    }

                    $recomputed = AuditService::hashRow((string) $row->previous_hash, [
                        'event_type'    => $row->event_type,
                        'user_id'       => $row->user_id,
                        'user_email'    => $row->user_email,
                        'resource_type' => $row->resource_type,
                        'resource_id'   => $row->resource_id,
                        'metadata'      => $row->metadata,
                        'result'        => $row->result,
                    ]);

                    if (! hash_equals($recomputed, (string) $row->row_hash)) {
                        $brokenAt = $row->id;
                        $reason = 'row_hash mismatch — the core payload of this row was altered after it was written';

                        return false;
                    }

                    $expectedPreviousHash = $row->row_hash;
                    $verified++;
                }

                return true;
            });

        if ($brokenAt !== null) {
            $this->error("Audit chain BROKEN at audit_logs.id = {$brokenAt}: {$reason}");
            $this->line("Rows verified before the break: {$verified} (legacy unchained rows skipped: {$legacy})");

            return self::FAILURE;
        }

        $this->info("Audit chain OK — {$verified} chained row(s) verified, {$legacy} legacy unchained row(s) skipped.");

        return self::SUCCESS;
    }
}
