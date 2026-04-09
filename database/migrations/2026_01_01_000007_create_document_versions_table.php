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
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('file_path', 500);
            $table->string('file_hash', 64);
            $table->string('status', 20)->default('archived');
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['document_id', 'version_number']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
CREATE INDEX idx_document_versions_document ON document_versions (document_id);
CREATE INDEX idx_document_versions_created_at ON document_versions (created_at);
SQL);
        } else {
            Schema::table('document_versions', function (Blueprint $table) {
                $table->index('document_id');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
