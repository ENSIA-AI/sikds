<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstitutionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('institutions')->updateOrInsert(
            ['code' => 'MESRS'],
            [
                'name' => 'Ministère de l\'Enseignement Supérieur et de la Recherche Scientifique',
                'type' => 'ministry',
                'domain' => 'mesrs.dz',
                'contact_email' => null,
                'contact_phone' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
