<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear predefined tags and re-seed with correct Figma colours
        DB::table('tags')->where('is_predefined', true)->delete();

        $now = now();

        $tags = [
            ['name' => 'Directive',  'slug' => 'directive',  'category' => 'type_document', 'color' => '#dbeafe'],
            ['name' => 'Décision',   'slug' => 'decision',   'category' => 'type_document', 'color' => '#f3e8ff'],
            ['name' => 'Régulation', 'slug' => 'regulation', 'category' => 'type_document', 'color' => '#e0e7ff'],
            ['name' => 'Rapport',    'slug' => 'rapport',    'category' => 'type_document', 'color' => '#dcfce7'],
            ['name' => 'Urgente', 'slug' => 'urgente', 'category' => 'priority', 'color' => '#fce8e8'],
            ['name' => 'Haute',   'slug' => 'haute',   'category' => 'priority', 'color' => '#fef2de'],
            ['name' => 'Normale', 'slug' => 'normale', 'category' => 'priority', 'color' => '#e5effd'],
            ['name' => 'Basse',   'slug' => 'basse',   'category' => 'priority', 'color' => '#eaebec'],
        ];

        foreach ($tags as $tag) {
            DB::table('tags')->insert([
                'name'          => $tag['name'],
                'slug'          => $tag['slug'],
                'description'   => null,
                'color'         => $tag['color'],
                'category'      => $tag['category'],
                'parent_id'     => null,
                'is_predefined' => true,
                'created_at'    => $now,
                'updated_at'    => $now,
                'created_by'    => null,
            ]);
        }
    }
}
