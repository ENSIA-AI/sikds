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
        $tableName = config('permission.table_names.permissions', 'permissions');

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (! Schema::hasColumn($tableName, 'code')) {
                $table->string('code')->nullable();
            }
            if (! Schema::hasColumn($tableName, 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn($tableName, 'category')) {
                $table->string('category', 100)->nullable();
            }
        });

        // Mirror `name` into `code` for existing Spatie rows (avoids null codes in UI / queries).
        DB::table($tableName)->whereNull('code')->update([
            'code' => DB::raw('name'),
        ]);
    }

    public function down(): void
    {
        $tableName = config('permission.table_names.permissions', 'permissions');

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            $drops = [];
            foreach (['category', 'description', 'code'] as $col) {
                if (Schema::hasColumn($tableName, $col)) {
                    $drops[] = $col;
                }
            }
            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
