<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
CREATE INDEX IF NOT EXISTS idx_download_logs_user_downloaded_at
    ON download_logs (user_id, downloaded_at DESC);

CREATE INDEX IF NOT EXISTS idx_notifications_recipient_created_at
    ON notifications (recipient_user_id, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_audit_logs_event_type_created_at
    ON audit_logs (event_type, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_audit_logs_user_created_at
    ON audit_logs (user_id, created_at DESC);
SQL);

            return;
        }

        Schema::table('download_logs', function (Blueprint $table): void {
            $table->index(['user_id', 'downloaded_at'], 'idx_download_logs_user_downloaded_at');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(['recipient_user_id', 'created_at'], 'idx_notifications_recipient_created_at');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index(['event_type', 'created_at'], 'idx_audit_logs_event_type_created_at');
            $table->index(['user_id', 'created_at'], 'idx_audit_logs_user_created_at');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
DROP INDEX IF EXISTS idx_download_logs_user_downloaded_at;
DROP INDEX IF EXISTS idx_notifications_recipient_created_at;
DROP INDEX IF EXISTS idx_audit_logs_event_type_created_at;
DROP INDEX IF EXISTS idx_audit_logs_user_created_at;
SQL);

            return;
        }

        Schema::table('download_logs', function (Blueprint $table): void {
            $table->dropIndex('idx_download_logs_user_downloaded_at');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('idx_notifications_recipient_created_at');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('idx_audit_logs_event_type_created_at');
            $table->dropIndex('idx_audit_logs_user_created_at');
        });
    }
};
