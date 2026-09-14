<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RecommendationTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'theme' => ['System', 'Dungeon', 'Revenge', 'Survival', 'Isekai', 'Regression', 'Cultivation'],
            'setting' => ['Modern', 'School', 'Medieval', 'Post Apocalypse'],
            'character' => ['Overpowered MC', 'Weak to Strong', 'Smart MC', 'Anti Hero', 'Cold MC'],
            'tone' => ['Dark', 'Comedy', 'Emotional', 'Serious'],
            'relationship' => ['No Romance', 'Low Romance', 'Romance', 'Harem'],
        ];
        foreach ($categories as $category => $names) {
            foreach ($names as $name) {
                Tag::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'category' => $category]);
            }
        }
    }
}
