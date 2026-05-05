<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE documents DROP CONSTRAINT IF EXISTS chk_target_audience;
ALTER TABLE documents ADD CONSTRAINT chk_target_audience
CHECK (target_audience IN ('all', 'specific_institutions', 'specific_roles', 'specific_users'));
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE documents DROP CONSTRAINT IF EXISTS chk_target_audience;
ALTER TABLE documents ADD CONSTRAINT chk_target_audience
CHECK (target_audience IN ('all', 'specific_institutions', 'specific_roles'));
SQL);
    }
};
