<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('permission.table_names.roles', 'roles');

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (! Schema::hasColumn($tableName, 'slug')) {
                $table->string('slug')->nullable();
            }
            if (! Schema::hasColumn($tableName, 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn($tableName, 'is_system_role')) {
                $table->boolean('is_system_role')->default(false);
            }
            if (! Schema::hasColumn($tableName, 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        $tableName = config('permission.table_names.roles', 'roles');

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasColumn($tableName, 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            $drops = [];
            foreach (['is_system_role', 'description', 'slug'] as $col) {
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
