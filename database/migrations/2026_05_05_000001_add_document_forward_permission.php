<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Inserts the `document.forward` permission idempotently so the feature works
 * on environments that don't re-run database seeders. Mirrors `PermissionsSeeder`.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => 'document.forward', 'guard_name' => 'web'],
            [
                'code' => 'document.forward',
                'description' => 'Transférer un document à un autre utilisateur.',
                'category' => 'documents',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'document.forward')
            ->where('guard_name', 'web')
            ->delete();
    }
};
