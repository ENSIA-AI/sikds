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

        DB::statement('ALTER TABLE document_chunks ALTER COLUMN embedding TYPE vector(1024)');
        DB::statement("COMMENT ON COLUMN document_chunks.embedding IS 'Embedding vector for retrieval (dimension 1024).'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Previous placeholder dimension used in early migrations.
        DB::statement('ALTER TABLE document_chunks ALTER COLUMN embedding TYPE vector(1536)');
        DB::statement("COMMENT ON COLUMN document_chunks.embedding IS '1536 is a placeholder dimension — update to match the chosen embedding model.'");
    }
};

