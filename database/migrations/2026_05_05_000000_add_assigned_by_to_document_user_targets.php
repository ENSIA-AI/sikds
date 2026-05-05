<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds traceability column to `document_user_targets` so we can record WHO
 * assigned/forwarded a document to a given user. Nullable so existing rows
 * (and bulk distribution targets created during upload) remain valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('document_user_targets', 'assigned_by')) {
            Schema::table('document_user_targets', function (Blueprint $table): void {
                $table->foreignId('assigned_by')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });

            if (DB::getDriverName() === 'pgsql') {
                DB::unprepared('CREATE INDEX IF NOT EXISTS idx_doc_user_targets_assigned_by ON document_user_targets (assigned_by);');
            } else {
                Schema::table('document_user_targets', function (Blueprint $table): void {
                    $table->index('assigned_by');
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('document_user_targets', 'assigned_by')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP INDEX IF EXISTS idx_doc_user_targets_assigned_by;');
        }

        Schema::table('document_user_targets', function (Blueprint $table): void {
            $table->dropForeign(['assigned_by']);
            $table->dropColumn('assigned_by');
        });
    }
};
