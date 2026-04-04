<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstitutionsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('institutions')->insertOrIgnore([
            [
                'code' => 'MESRS',
                'name' => 'Ministère de l\'Enseignement Supérieur et de la Recherche Scientifique',
                'type' => 'ministry',
                'domain' => 'mesrs.dz',
                'contact_email' => 'contact@mesrs.dz',
                'contact_phone' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'USTHB',
                'name' => 'Université des Sciences et de la Technologie Houari Boumediene',
                'type' => 'university',
                'domain' => 'usthb.dz',
                'contact_email' => 'contact@usthb.dz',
                'contact_phone' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ENSIA',
                'name' => 'École Nationale Supérieure d\'Informatique',
                'type' => 'university',
                'domain' => 'ensia.edu.dz',
                'contact_email' => 'contact@ensia.edu.dz',
                'contact_phone' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'UNIV-ALGER',
                'name' => 'Université d\'Alger 1 Benyoucef Benkhedda',
                'type' => 'university',
                'domain' => 'univ-alger.dz',
                'contact_email' => 'contact@univ-alger.dz',
                'contact_phone' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'UNIV-ORAN',
                'name' => 'Université d\'Oran 1 Ahmed Ben Bella',
                'type' => 'university',
                'domain' => 'univ-oran.dz',
                'contact_email' => 'contact@univ-oran.dz',
                'contact_phone' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
