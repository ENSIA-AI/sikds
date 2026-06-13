<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()->updateOrCreate(
            ['name' => 'Super Administrateur', 'guard_name' => 'web'],
            [
                'slug' => 'super-admin',
                'is_system_role' => true,
                'description' => 'Rôle système avec accès complet. Ne peut pas être modifié ou supprimé.',
            ]
        );

        $role->syncPermissions(Permission::query()->pluck('name')->all());

    }
}
