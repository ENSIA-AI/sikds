<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Document Management
            ['name' => 'document.create'],
            ['name' => 'document.view.all'],
            ['name' => 'document.view.own_institution'],
            ['name' => 'document.view.assigned'],
            ['name' => 'document.edit'],
            ['name' => 'document.delete'],
            ['name' => 'document.restore'],
            ['name' => 'document.publish'],

            // Document Distribution
            ['name' => 'distribution.manage'],

            // Document Tagging
            ['name' => 'tag.assign'],
            ['name' => 'tag.manage'],

            // User & Role Management
            ['name' => 'user.manage'],
            ['name' => 'user.deactivate'],
            ['name' => 'user.view.all'],
            ['name' => 'user.assign.permissions'],
            ['name' => 'role.create'],
            ['name' => 'role.edit'],
            ['name' => 'role.delete'],
            ['name' => 'role.view'],

            // Institution Management
            ['name' => 'institution.create'],
            ['name' => 'institution.edit'],
            ['name' => 'institution.delete'],
            ['name' => 'institution.view'],

            // RAG & Search
            ['name' => 'rag.query'],
            ['name' => 'search.basic'],

            // Audit
            ['name' => 'audit.view'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate([
                'name' => $p['name'],
                'guard_name' => 'web',
            ]);
        }
    }
}
