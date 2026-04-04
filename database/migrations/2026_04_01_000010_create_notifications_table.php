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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->timestampTz('email_sent_at')->nullable();
            $table->string('email_status', 20)->nullable();
            $table->text('email_error')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement('ALTER TABLE notifications ADD CONSTRAINT chk_notifications_email_status CHECK (email_status IS NULL OR email_status IN (\'pending\', \'sent\', \'failed\'))');

        DB::statement('CREATE INDEX IF NOT EXISTS notifications_idx_recipient_user_id ON notifications (recipient_user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS notifications_idx_document_id ON notifications (document_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS notifications_idx_email_status ON notifications (email_status)');
        DB::statement('CREATE INDEX IF NOT EXISTS notifications_idx_type ON notifications (type)');
        DB::statement('CREATE INDEX IF NOT EXISTS notifications_idx_created_at ON notifications (created_at)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE notifications DROP CONSTRAINT IF EXISTS chk_notifications_email_status');

        Schema::dropIfExists('notifications');
    }
};
