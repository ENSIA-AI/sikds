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
        Schema::create('document_institution_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['document_id', 'institution_id']);
        });

        Schema::create('document_role_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['document_id', 'role_id']);
        });

        Schema::create('document_user_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['document_id', 'user_id']);
        });

        DB::statement('CREATE INDEX IF NOT EXISTS doc_inst_targets_idx_document_id ON document_institution_targets (document_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS doc_inst_targets_idx_institution_id ON document_institution_targets (institution_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS doc_role_targets_idx_document_id ON document_role_targets (document_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS doc_role_targets_idx_role_id ON document_role_targets (role_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS doc_user_targets_idx_document_id ON document_user_targets (document_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS doc_user_targets_idx_user_id ON document_user_targets (user_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_user_targets');
        Schema::dropIfExists('document_role_targets');
        Schema::dropIfExists('document_institution_targets');
    }
};
