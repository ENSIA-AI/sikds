<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
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

        $defaultUserRole = Role::query()->updateOrCreate(
            ['name' => 'User', 'guard_name' => 'web'],
            [
                'slug' => 'user',
                'is_system_role' => true,
                'description' => 'Rôle utilisateur par défaut pour les comptes SSO.',
            ]
        );

        $defaultUserRole->syncPermissions([
            'document.view.assigned',
            'document.view.own_institution',
            'rag.query',
            'search.basic',
        ]);
    }
}
