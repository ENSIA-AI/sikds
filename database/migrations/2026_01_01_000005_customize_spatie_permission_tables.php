<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->string('code', 100);
            $table->text('description')->nullable();
            $table->string('category', 50);
            $table->unique('code', 'idx_permissions_code');
            $table->index('category', 'idx_permissions_category');
        });

        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique('slug', 'idx_roles_slug');
            $table->index('is_system_role', 'idx_roles_is_system');
        });

        Schema::table($tableNames['role_has_permissions'], function (Blueprint $table) {
            $table->timestampTz('assigned_at')->useCurrent();
        });

        Schema::table($tableNames['role_has_permissions'], function (Blueprint $table) {
            $table->index('role_id', 'idx_role_permissions_role');
            $table->index('permission_id', 'idx_role_permissions_permission');
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) {
            $table->timestampTz('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('assigned_by', 'idx_user_roles_assigned_by');
        });
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) {
            $table->dropIndex('idx_user_roles_assigned_by');
            $table->dropForeign(['assigned_by']);
            $table->dropColumn(['assigned_at', 'assigned_by']);
        });

        Schema::table($tableNames['role_has_permissions'], function (Blueprint $table) {
            $table->dropIndex('idx_role_permissions_role');
            $table->dropIndex('idx_role_permissions_permission');
            $table->dropColumn('assigned_at');
        });

        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->dropIndex('idx_roles_slug');
            $table->dropIndex('idx_roles_is_system');
            $table->dropForeign(['created_by']);
            $table->dropColumn(['slug', 'description', 'is_system_role', 'created_by']);
        });

        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->dropIndex('idx_permissions_code');
            $table->dropIndex('idx_permissions_category');
            $table->dropColumn(['code', 'description', 'category']);
        });
    }
};
