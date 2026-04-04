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
        Schema::create('download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->uuid('watermark_uuid')->unique();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestampTz('downloaded_at')->useCurrent();
        });

        DB::statement('ALTER TABLE download_logs ALTER COLUMN watermark_uuid SET DEFAULT gen_random_uuid()');

        DB::statement('CREATE INDEX IF NOT EXISTS download_logs_idx_document_id ON download_logs (document_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS download_logs_idx_user_id ON download_logs (user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS download_logs_idx_downloaded_at ON download_logs (downloaded_at)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_logs');
    }
};
