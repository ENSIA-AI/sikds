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
CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    user_email VARCHAR(255),
    resource_type VARCHAR(100),
    resource_id BIGINT,
    metadata JSONB,
    result VARCHAR(20),
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    previous_hash VARCHAR(64)
);
CREATE INDEX idx_audit_logs_event_type ON audit_logs (event_type);
CREATE INDEX idx_audit_logs_user ON audit_logs (user_id);
CREATE INDEX idx_audit_logs_resource ON audit_logs (resource_type, resource_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs (created_at);
CREATE INDEX idx_audit_logs_result ON audit_logs (result);
COMMENT ON TABLE audit_logs IS 'Immutable audit trail - all security events logged here';
SQL);

            DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION fn_audit_immutable()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Audit logs are immutable and cannot be modified or deleted';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_audit_no_update
BEFORE UPDATE ON audit_logs
FOR EACH ROW EXECUTE FUNCTION fn_audit_immutable();

CREATE TRIGGER trg_audit_no_delete
BEFORE DELETE ON audit_logs
FOR EACH ROW EXECUTE FUNCTION fn_audit_immutable();
SQL);
        } else {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 100);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_email', 255)->nullable();
                $table->string('resource_type', 100)->nullable();
                $table->unsignedBigInteger('resource_id')->nullable();
                $table->json('metadata')->nullable();
                $table->string('result', 20)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestampTz('created_at')->useCurrent();
                $table->string('previous_hash', 64)->nullable();
            });
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('event_type');
                $table->index('user_id');
                $table->index(['resource_type', 'resource_id']);
                $table->index('created_at');
                $table->index('result');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_no_update ON audit_logs;');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_no_delete ON audit_logs;');
            DB::unprepared('DROP FUNCTION IF EXISTS fn_audit_immutable();');
        }
        Schema::dropIfExists('audit_logs');
    }
};
