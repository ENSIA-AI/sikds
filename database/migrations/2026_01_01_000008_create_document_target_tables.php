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

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
CREATE INDEX idx_doc_inst_targets_doc ON document_institution_targets (document_id);
CREATE INDEX idx_doc_inst_targets_inst ON document_institution_targets (institution_id);
CREATE INDEX idx_doc_role_targets_doc ON document_role_targets (document_id);
CREATE INDEX idx_doc_role_targets_role ON document_role_targets (role_id);
CREATE INDEX idx_doc_user_targets_doc ON document_user_targets (document_id);
CREATE INDEX idx_doc_user_targets_user ON document_user_targets (user_id);
SQL);
        } else {
            Schema::table('document_institution_targets', function (Blueprint $table) {
                $table->index('document_id');
                $table->index('institution_id');
            });
            Schema::table('document_role_targets', function (Blueprint $table) {
                $table->index('document_id');
                $table->index('role_id');
            });
            Schema::table('document_user_targets', function (Blueprint $table) {
                $table->index('document_id');
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_user_targets');
        Schema::dropIfExists('document_role_targets');
        Schema::dropIfExists('document_institution_targets');
    }
};
