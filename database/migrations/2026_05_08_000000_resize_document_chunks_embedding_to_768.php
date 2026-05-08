<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_dc_embedding_hnsw');
        DB::statement('DROP INDEX IF EXISTS document_chunks_embedding_idx');

        // Existing 1024-d embeddings are incompatible with the new 768-d model.
        DB::statement('UPDATE document_chunks SET embedding = NULL WHERE embedding IS NOT NULL');

        DB::statement('ALTER TABLE document_chunks ALTER COLUMN embedding TYPE vector(768) USING embedding::vector(768)');
        DB::statement("COMMENT ON COLUMN document_chunks.embedding IS 'Embedding vector for retrieval (dimension 768).'");

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_dc_embedding_hnsw
            ON document_chunks USING hnsw (embedding vector_cosine_ops)
            WITH (m = 16, ef_construction = 64)
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_dc_embedding_hnsw');
        DB::statement('DROP INDEX IF EXISTS document_chunks_embedding_idx');

        DB::statement('UPDATE document_chunks SET embedding = NULL WHERE embedding IS NOT NULL');

        DB::statement('ALTER TABLE document_chunks ALTER COLUMN embedding TYPE vector(1024) USING embedding::vector(1024)');
        DB::statement("COMMENT ON COLUMN document_chunks.embedding IS 'Embedding vector for retrieval (dimension 1024).'");

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_dc_embedding_hnsw
            ON document_chunks USING hnsw (embedding vector_cosine_ops)
            WITH (m = 16, ef_construction = 64)
        SQL);
    }
};

