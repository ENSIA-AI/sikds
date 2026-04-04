<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SuperAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $mesrsId = DB::table('institutions')->where('code', 'MESRS')->value('id');

        $user = User::firstOrCreate(
            ['email' => 'admin@mesrs.dz'],
            [
                'sso_user_id' => 'local-super-admin',
                'username' => 'superadmin',
                'full_name' => 'Super Administrateur',
                'institution_id' => $mesrsId,
                'auth_type' => 'local',
                'auth_domain' => 'mesrs.dz',
                'password' => bcrypt('Admin@SIKDS2026!'),
                'is_active' => true,
            ]
        );

        $superAdminRole = Role::findByName('Super Administrateur', 'web');
        $user->assignRole($superAdminRole);
    }
}
