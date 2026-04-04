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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20)->unique();
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
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
        });

        DB::statement('ALTER TABLE documents ADD CONSTRAINT chk_document_status CHECK (status IN (\'draft\', \'active\', \'archived\', \'soft_deleted\'))');
        DB::statement('ALTER TABLE documents ADD CONSTRAINT chk_indexing_status CHECK (indexing_status IN (\'pending\', \'processing\', \'indexed\', \'failed\'))');
        DB::statement('ALTER TABLE documents ADD CONSTRAINT chk_target_audience CHECK (target_audience IN (\'all\', \'specific_institutions\', \'specific_roles\'))');
        DB::statement('ALTER TABLE documents ADD CONSTRAINT chk_document_dates CHECK ((effective_date IS NULL OR effective_date >= issue_date) AND (expiration_date IS NULL OR expiration_date > issue_date))');

        DB::statement('CREATE INDEX IF NOT EXISTS documents_idx_status_partial ON documents (status) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS documents_idx_uploaded_by ON documents (uploaded_by)');
        DB::statement('CREATE INDEX IF NOT EXISTS documents_idx_issue_date ON documents (issue_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS documents_idx_indexing_status ON documents (indexing_status)');
        DB::statement('CREATE INDEX IF NOT EXISTS documents_idx_target_audience ON documents (target_audience)');
        DB::statement('CREATE INDEX IF NOT EXISTS documents_idx_deleted_at ON documents (deleted_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS documents_idx_status_issue_date_partial ON documents (status, issue_date DESC) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
