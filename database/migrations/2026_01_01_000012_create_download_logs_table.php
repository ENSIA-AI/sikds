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
CREATE TABLE download_logs (
    id BIGSERIAL PRIMARY KEY,
    document_id BIGINT NOT NULL REFERENCES documents(id) ON DELETE RESTRICT,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    watermark_uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    ip_address INET NOT NULL,
    user_agent TEXT,
    downloaded_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_download_logs_document ON download_logs (document_id);
CREATE INDEX idx_download_logs_user ON download_logs (user_id);
CREATE INDEX idx_download_logs_downloaded_at ON download_logs (downloaded_at);
CREATE UNIQUE INDEX idx_download_logs_uuid ON download_logs (watermark_uuid);
SQL);
        } else {
            Schema::create('download_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('documents')->restrictOnDelete();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->uuid('watermark_uuid')->unique();
                $table->string('ip_address', 45);
                $table->text('user_agent')->nullable();
                $table->timestampTz('downloaded_at')->useCurrent();
            });
            Schema::table('download_logs', function (Blueprint $table) {
                $table->index('document_id');
                $table->index('user_id');
                $table->index('downloaded_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('download_logs');
    }
};
