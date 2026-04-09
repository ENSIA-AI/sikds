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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('file_path', 500);
            $table->string('file_hash', 64);
            $table->unsignedBigInteger('file_size');
            $table->date('issue_date');
            $table->date('effective_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('indexing_status', 20)->default('pending');
            $table->string('target_audience', 30)->default('all');
            $table->integer('version_number')->default(1);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('deleted_at')->nullable();
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
ALTER TABLE documents ADD CONSTRAINT chk_document_status CHECK (status IN ('draft', 'active', 'archived', 'soft_deleted'));
ALTER TABLE documents ADD CONSTRAINT chk_indexing_status CHECK (indexing_status IN ('pending', 'processing', 'indexed', 'failed'));
ALTER TABLE documents ADD CONSTRAINT chk_target_audience CHECK (target_audience IN ('all', 'specific_institutions', 'specific_roles'));
ALTER TABLE documents ADD CONSTRAINT chk_document_dates CHECK (
    (effective_date IS NULL OR effective_date >= issue_date) AND
    (expiration_date IS NULL OR expiration_date > issue_date)
);
CREATE UNIQUE INDEX idx_documents_reference ON documents (reference_number);
CREATE INDEX idx_documents_status ON documents (status) WHERE deleted_at IS NULL;
CREATE INDEX idx_documents_uploaded_by ON documents (uploaded_by);
CREATE INDEX idx_documents_issue_date ON documents (issue_date);
CREATE INDEX idx_documents_indexing_status ON documents (indexing_status);
CREATE INDEX idx_documents_target_audience ON documents (target_audience);
CREATE INDEX idx_documents_deleted_at ON documents (deleted_at);
CREATE INDEX idx_documents_status_date ON documents (status, issue_date DESC) WHERE deleted_at IS NULL;
SQL);
        } else {
            Schema::table('documents', function (Blueprint $table) {
                $table->unique('reference_number');
                $table->index('uploaded_by');
                $table->index('issue_date');
                $table->index('indexing_status');
                $table->index('target_audience');
                $table->index('deleted_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
