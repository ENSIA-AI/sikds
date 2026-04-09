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
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->text('name');
            $table->string('type', 20)->default('ministry');
            $table->string('domain', 100)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
ALTER TABLE institutions ADD CONSTRAINT chk_institution_type CHECK (type IN ('ministry', 'university'));
CREATE UNIQUE INDEX institutions_code_unique ON institutions (code);
CREATE UNIQUE INDEX institutions_domain_unique ON institutions (domain);
CREATE INDEX idx_institutions_type ON institutions (type);
CREATE INDEX idx_institutions_domain ON institutions (domain);
CREATE INDEX idx_institutions_active ON institutions (is_active);
SQL);
        } else {
            Schema::table('institutions', function (Blueprint $table) {
                $table->unique('code');
                $table->unique('domain');
                $table->index('type');
                $table->index('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
