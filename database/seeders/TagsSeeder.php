<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $tags = [
            ['name' => 'Directive', 'slug' => 'directive', 'category' => 'type', 'color' => '#3B82F6'],
            ['name' => 'Décision', 'slug' => 'decision', 'category' => 'type', 'color' => '#8B5CF6'],
            ['name' => 'Règlement', 'slug' => 'reglement', 'category' => 'type', 'color' => '#06B6D4'],
            ['name' => 'Rapport', 'slug' => 'rapport', 'category' => 'type', 'color' => '#10B981'],
            ['name' => 'Urgent', 'slug' => 'urgent', 'category' => 'priority', 'color' => '#EF4444'],
            ['name' => 'High', 'slug' => 'high', 'category' => 'priority', 'color' => '#F97316'],
            ['name' => 'Normal', 'slug' => 'normal', 'category' => 'priority', 'color' => '#6B7280'],
            ['name' => 'Low', 'slug' => 'low', 'category' => 'priority', 'color' => '#D1D5DB'],
        ];

        foreach ($tags as $t) {
            DB::table('tags')->updateOrInsert(
                ['slug' => $t['slug']],
                [
                    'name' => $t['name'],
                    'description' => null,
                    'color' => $t['color'],
                    'category' => $t['category'],
                    'parent_id' => null,
                    'is_predefined' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by' => null,
                ]
            );
        }
    }
}
