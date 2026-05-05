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
        if (! Schema::hasColumn('notifications', 'read_at')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->timestampTz('read_at')->nullable()->after('metadata');
            });

            if (DB::getDriverName() === 'pgsql') {
                DB::unprepared('CREATE INDEX IF NOT EXISTS idx_notifications_recipient_read_at ON notifications (recipient_user_id, read_at);');
            } else {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->index(['recipient_user_id', 'read_at']);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'read_at')) {
            if (DB::getDriverName() === 'pgsql') {
                DB::unprepared('DROP INDEX IF EXISTS idx_notifications_recipient_read_at;');
            }

            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('read_at');
            });
        }
    }
};
