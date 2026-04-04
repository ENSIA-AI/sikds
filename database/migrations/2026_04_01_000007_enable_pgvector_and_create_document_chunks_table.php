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
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->integer('chunk_index');
            $table->text('content');
            $table->integer('token_count')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['document_id', 'chunk_index']);
        });

        DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding vector(1536) NULL');

        DB::statement('CREATE INDEX IF NOT EXISTS document_chunks_idx_document_id ON document_chunks (document_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_chunks');

        DB::statement('DROP EXTENSION IF EXISTS vector');
    }
};
