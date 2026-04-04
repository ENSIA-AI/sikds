<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PredefinedTagsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tags')->insertOrIgnore([
            // Document Types
            [
                'name' => 'Directive',
                'slug' => 'directive',
                'description' => 'Instruction officielle',
                'color' => '#3B82F6',
                'category' => 'type',
                'is_predefined' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Décision',
                'slug' => 'decision',
                'description' => 'Décision officielle',
                'color' => '#8B5CF6',
                'category' => 'type',
                'is_predefined' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Règlement',
                'slug' => 'reglement',
                'description' => 'Règlement officiel',
                'color' => '#06B6D4',
                'category' => 'type',
                'is_predefined' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rapport',
                'slug' => 'rapport',
                'description' => 'Rapport officiel',
                'color' => '#10B981',
                'category' => 'type',
                'is_predefined' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Priority Tags
            [
                'name' => 'Urgent',
                'slug' => 'urgent',
                'description' => 'Priorité urgente',
                'color' => '#EF4444',
                'category' => 'priority',
                'is_predefined' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Haute Priorité',
                'slug' => 'haute-priorite',
                'description' => 'Haute priorité',
                'color' => '#F97316',
                'category' => 'priority',
                'is_predefined' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Normale',
                'slug' => 'normale',
                'description' => 'Priorité normale',
                'color' => '#6B7280',
                'category' => 'priority',
                'is_predefined' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Basse',
                'slug' => 'basse',
                'description' => 'Basse priorité',
                'color' => '#D1D5DB',
                'category' => 'priority',
                'is_predefined' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
