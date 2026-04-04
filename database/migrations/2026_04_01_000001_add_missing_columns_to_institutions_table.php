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
        Schema::table('institutions', function (Blueprint $table) {
            if (! Schema::hasColumn('institutions', 'code')) {
                $table->string('code', 50)->unique();
            }
            if (! Schema::hasColumn('institutions', 'name')) {
                $table->text('name');
            }
            if (! Schema::hasColumn('institutions', 'type')) {
                $table->string('type', 20)->default('ministry');
            }
            if (! Schema::hasColumn('institutions', 'domain')) {
                $table->string('domain', 100)->unique()->nullable();
            }
            if (! Schema::hasColumn('institutions', 'contact_email')) {
                $table->string('contact_email', 255)->nullable();
            }
            if (! Schema::hasColumn('institutions', 'contact_phone')) {
                $table->string('contact_phone', 50)->nullable();
            }
            if (! Schema::hasColumn('institutions', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::hasColumn('institutions', 'created_at')) {
                $table->timestampTz('created_at')->nullable();
            }
            if (! Schema::hasColumn('institutions', 'updated_at')) {
                $table->timestampTz('updated_at')->nullable();
            }
        });

        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'chk_institution_type'
                ) THEN
                    ALTER TABLE institutions
                    ADD CONSTRAINT chk_institution_type
                    CHECK (type IN ('ministry', 'university'));
                END IF;
            END \$\$;
        ");

        // Ensure indexes exist (base migration already adds type, is_active, unique domain).
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = 'institutions' AND indexname = 'institutions_type_index'
                ) THEN
                    CREATE INDEX institutions_type_index ON institutions (type);
                END IF;
                IF NOT EXISTS (
                    SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = 'institutions' AND indexname = 'institutions_domain_unique'
                ) THEN
                    CREATE UNIQUE INDEX institutions_domain_unique ON institutions (domain);
                END IF;
                IF NOT EXISTS (
                    SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = 'institutions' AND indexname = 'institutions_is_active_index'
                ) THEN
                    CREATE INDEX institutions_is_active_index ON institutions (is_active);
                END IF;
            END \$\$;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE institutions DROP CONSTRAINT IF EXISTS chk_institution_type');
    }
};
