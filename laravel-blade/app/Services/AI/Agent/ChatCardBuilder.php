<?php

namespace App\Services\AI\Agent;

use App\Models\Comic;

/** Resolves model-chosen ids into the exact public card contract. */
final class ChatCardBuilder
{
    public function build(array $ids, array $meta): array
    {
        if ($ids === []) {
            return [];
        }

        $comics = Comic::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with(['genres:id,name', 'tags:id,name'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $cards = [];
        foreach ($ids as $id) {
            $comic = $comics->get((int) $id);
            if ($comic === null) {
                continue;
            }
            $cards[] = [
                'id' => $comic->id,
                'title' => $comic->title,
                'slug' => $comic->slug,
                'cover' => $comic->cover_url,
                'score' => $meta[$comic->id]['score'] ?? null,
                'matched_reasons' => $meta[$comic->id]['matched_reasons'] ?? [],
                'url' => route('comics.show', $comic->slug),
            ];
        }

        return $cards;
    }
}
