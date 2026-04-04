<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 100);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_email', 255)->nullable();
            $table->string('resource_type', 100)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->string('result', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->string('previous_hash', 64)->nullable();
        });

        DB::statement('CREATE INDEX IF NOT EXISTS audit_logs_idx_event_type ON audit_logs (event_type)');
        DB::statement('CREATE INDEX IF NOT EXISTS audit_logs_idx_user_id ON audit_logs (user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS audit_logs_idx_resource ON audit_logs (resource_type, resource_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS audit_logs_idx_created_at ON audit_logs (created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS audit_logs_idx_result ON audit_logs (result)');

        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION fn_audit_immutable()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Audit logs are immutable and cannot be modified or deleted';
END;
$$ LANGUAGE plpgsql;
SQL);

        DB::statement(<<<'SQL'
CREATE TRIGGER trg_audit_no_update
BEFORE UPDATE ON audit_logs
FOR EACH ROW EXECUTE FUNCTION fn_audit_immutable();
SQL);

        DB::statement(<<<'SQL'
CREATE TRIGGER trg_audit_no_delete
BEFORE DELETE ON audit_logs
FOR EACH ROW EXECUTE FUNCTION fn_audit_immutable();
SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_audit_no_update ON audit_logs');
        DB::statement('DROP TRIGGER IF EXISTS trg_audit_no_delete ON audit_logs');
        DB::statement('DROP FUNCTION IF EXISTS fn_audit_immutable()');

        Schema::dropIfExists('audit_logs');
    }
};
