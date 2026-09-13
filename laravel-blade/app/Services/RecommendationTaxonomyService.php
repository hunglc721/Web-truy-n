<?php

namespace App\Services;

use App\Models\Genre;
use App\Models\Tag;

class RecommendationTaxonomyService
{
    // Matches the existing comics.status enum without changing its business logic.
    public const STATUSES = ['ongoing', 'completed', 'hiatus', 'cancelled'];

    public const CATEGORIES = [
        'theme' => 'themes', 'setting' => 'settings', 'character' => 'character_traits',
        'tone' => 'tones', 'relationship' => 'relationships',
    ];

    public function all(): array
    {
        $taxonomy = ['genres' => Genre::query()->orderBy('name')->pluck('name')->unique()->values()->all()];
        $tags = Tag::query()->whereIn('category', array_keys(self::CATEGORIES))->orderBy('name')->get(['name', 'category']);
        foreach (self::CATEGORIES as $category => $field) {
            $taxonomy[$field] = $tags->where('category', $category)->pluck('name')->unique()->values()->all();
        }
        $taxonomy['statuses'] = self::STATUSES;

        return $taxonomy;
    }
}
