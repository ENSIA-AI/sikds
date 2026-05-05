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
        Schema::create('document_tags', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestampTz('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->primary(['document_id', 'tag_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
CREATE INDEX idx_document_tags_document ON document_tags (document_id);
CREATE INDEX idx_document_tags_tag ON document_tags (tag_id);
CREATE INDEX idx_document_tags_assigned_by ON document_tags (assigned_by);
SQL);
        } else {
            Schema::table('document_tags', function (Blueprint $table) {
                $table->index('document_id');
                $table->index('tag_id');
                $table->index('assigned_by');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_tags');
    }
};
