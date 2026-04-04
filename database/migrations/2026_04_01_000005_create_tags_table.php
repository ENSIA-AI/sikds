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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable();
            $table->string('category', 50)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('tags')->nullOnDelete();
            $table->boolean('is_predefined')->default(false);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::statement('CREATE INDEX IF NOT EXISTS tags_idx_category ON tags (category)');
        DB::statement('CREATE INDEX IF NOT EXISTS tags_idx_parent_id ON tags (parent_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS tags_idx_is_predefined ON tags (is_predefined)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
