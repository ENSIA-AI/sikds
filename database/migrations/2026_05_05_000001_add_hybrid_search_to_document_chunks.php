<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds full-text search (tsvector + GIN) and HNSW vector index to document_chunks.
 *
 * - search_vector: multi-language tsvector for BM25/sparse retrieval
 * - GIN index on search_vector for fast @@ queries
 * - HNSW index on embedding for approximate nearest-neighbor search
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Add tsvector column for BM25 full-text search.
        DB::statement('ALTER TABLE document_chunks ADD COLUMN IF NOT EXISTS search_vector tsvector');

        // GIN index for fast full-text match.
        DB::statement('CREATE INDEX IF NOT EXISTS idx_dc_search_vector ON document_chunks USING GIN(search_vector)');

        // HNSW index for approximate nearest-neighbor vector search (cosine distance).
        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_dc_embedding_hnsw
            ON document_chunks USING hnsw (embedding vector_cosine_ops)
            WITH (m = 16, ef_construction = 64)
        SQL);

        // Backfill search_vector for any existing chunks.
        DB::statement(<<<'SQL'
            UPDATE document_chunks
            SET search_vector =
                setweight(to_tsvector('french', coalesce(content, '')), 'A') ||
                setweight(to_tsvector('english', coalesce(content, '')), 'B') ||
                setweight(to_tsvector('simple', coalesce(content, '')), 'C')
            WHERE search_vector IS NULL
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_dc_embedding_hnsw');
        DB::statement('DROP INDEX IF EXISTS idx_dc_search_vector');
        DB::statement('ALTER TABLE document_chunks DROP COLUMN IF EXISTS search_vector');
    }
};
