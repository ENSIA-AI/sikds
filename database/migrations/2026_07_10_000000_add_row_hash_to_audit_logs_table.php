<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tamper-evident audit chaining (SRS audit-trail integrity).
 *
 * `previous_hash` existed since the original audit_logs migration but was never
 * populated. AuditService now writes, for every new row:
 *   - previous_hash: the row_hash of the previous audit row (or the genesis
 *     sentinel of 64 zeros for the first chained row),
 *   - row_hash:      sha256(previous_hash | canonical core payload).
 *
 * This column stores the row's own hash so the next row can link to it and so
 * `php artisan audit:verify-chain` can recompute and verify the whole chain.
 * Rows created before this migration keep row_hash = NULL (legacy, unchained).
 *
 * Adding a column is DDL and is not affected by the row-level immutability
 * triggers (they only block UPDATE/DELETE of rows).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('row_hash', 64)->nullable()->after('previous_hash');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('row_hash');
        });
    }
};
