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
        Schema::create('document_tags', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestampTz('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->primary(['document_id', 'tag_id']);
        });

        DB::statement('CREATE INDEX IF NOT EXISTS document_tags_idx_document_id ON document_tags (document_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS document_tags_idx_tag_id ON document_tags (tag_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS document_tags_idx_assigned_by ON document_tags (assigned_by)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_tags');
    }
};
