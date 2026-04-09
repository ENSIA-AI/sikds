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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable();
            $table->string('category', 50)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('tags')->nullOnDelete();
            $table->boolean('is_predefined')->default(false);
            $table->timestampsTz();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
CREATE UNIQUE INDEX idx_tags_slug ON tags (slug);
CREATE INDEX idx_tags_category ON tags (category);
CREATE INDEX idx_tags_parent ON tags (parent_id);
CREATE INDEX idx_tags_is_predefined ON tags (is_predefined);
SQL);
        } else {
            Schema::table('tags', function (Blueprint $table) {
                $table->unique('slug');
                $table->index('category');
                $table->index('parent_id');
                $table->index('is_predefined');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
