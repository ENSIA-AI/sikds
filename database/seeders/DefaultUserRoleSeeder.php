<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DefaultUserRoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

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
