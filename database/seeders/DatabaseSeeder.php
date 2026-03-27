<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InstitutionsTableSeeder::class,
            PermissionsTableSeeder::class,
            RolesTableSeeder::class,
            PredefinedTagsSeeder::class,
            SuperAdminUserSeeder::class,
            RolePermissionAndUserRoleSeeder::class,
        ]);
    }
}
