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
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->integer('chunk_index');
            $table->text('content');
            $table->integer('token_count')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['document_id', 'chunk_index']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
ALTER TABLE document_chunks ADD COLUMN embedding vector(1536) NULL;
COMMENT ON COLUMN document_chunks.embedding IS '1536 is a placeholder dimension — update to match the chosen embedding model.';
CREATE INDEX idx_document_chunks_document ON document_chunks (document_id);
SQL);
        } else {
            Schema::table('document_chunks', function (Blueprint $table) {
                $table->text('embedding')->nullable();
                $table->index('document_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
