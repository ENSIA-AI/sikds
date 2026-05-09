<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ManagerRoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $managerRole = Role::query()->updateOrCreate(
            ['name' => 'Manager', 'guard_name' => 'web'],
            [
                'slug' => 'manager',
                'is_system_role' => true,
                'description' => 'Rôle gestionnaire pour les comptes SSO portant le rôle SKIDS_MANAGER.',
            ]
        );

        $managerRole->syncPermissions([
            'document.create',
            'document.delete',
            'document.edit',
            'document.forward',
            'document.publish',
            'document.view.assigned',
            'document.view.own_institution',
            'tag.assign',
            'tag.manage',
            'rag.query',
            'search.basic',
        ]);
    }
}
