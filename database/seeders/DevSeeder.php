<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Users\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command->warn('DevSeeder only runs in local environment. Skipping.');

            return;
        }

        $mesrsId = DB::table('institutions')->where('code', 'MESRS')->value('id');
        if (! $mesrsId) {
            $this->command->error('MESRS institution missing. Run DatabaseSeeder first.');

            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => 'admin@mesrs.dz'],
            [
                'sso_user_id' => 'local-dev-admin',
                'username' => 'admin',
                'full_name' => 'Admin MESRS',
                'institution_id' => $mesrsId,
                'auth_type' => 'local',
                'auth_domain' => 'mesrs.dz',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );

        $role = Role::findByName('Super Administrateur', 'web');
        $user->syncRoles([$role]);
    }
}
