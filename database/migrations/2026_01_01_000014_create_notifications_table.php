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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->timestampTz('email_sent_at')->nullable();
            $table->string('email_status', 20)->nullable();
            $table->text('email_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
CREATE INDEX idx_notifications_recipient ON notifications (recipient_user_id);
CREATE INDEX idx_notifications_document ON notifications (document_id);
CREATE INDEX idx_notifications_status ON notifications (email_status);
CREATE INDEX idx_notifications_type ON notifications (type);
CREATE INDEX idx_notifications_created_at ON notifications (created_at);
SQL);
        } else {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index('recipient_user_id');
                $table->index('document_id');
                $table->index('email_status');
                $table->index('type');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
