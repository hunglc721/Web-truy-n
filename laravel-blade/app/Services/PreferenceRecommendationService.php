<?php

namespace App\Services;

use App\Data\RecommendationPreference;
use App\Models\Comic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PreferenceRecommendationService
{
    private const TAG_WEIGHTS = [
        'theme' => ['themes', 25], 'character' => ['character_traits', 20],
        'setting' => ['settings', 15], 'tone' => ['tones', 10],
    ];

    /**
     * @return Collection<int, array{comic: Comic, score: int, matched_reasons: array}>
     *                                                                                  Relationships have no positive weight in MVP; relationship exclusions still apply.
     */
    public function recommend(RecommendationPreference $preference, int $limit = 5): Collection
    {
        $data = $preference->toArray();
        $hasSignal = $data['genres'] !== [] || $data['status'] !== null;
        foreach (self::TAG_WEIGHTS as [$field]) {
            $hasSignal = $hasSignal || $data[$field] !== [];
        }
        if (! $hasSignal || $limit <= 0) {
            return collect();
        }

        // Comic has no public scope. Null/future publication dates are not public candidates.
        $query = Comic::query()->whereNotNull('published_at')->where('published_at', '<=', now())
            ->where(function (Builder $query) use ($data) {
                $query->whereRaw('1 = 0');
                if ($data['genres'] !== []) {
                    $query->orWhereHas('genres', fn (Builder $genres) => $genres->whereIn('name', $data['genres']));
                }
                foreach (self::TAG_WEIGHTS as $category => [$field]) {
                    if ($data[$field] !== []) {
                        $query->orWhereHas('tags', fn (Builder $tags) => $tags->where('category', $category)->whereIn('name', $data[$field]));
                    }
                }
                if ($data['status'] !== null) {
                    $query->orWhere('status', $data['status']);
                }
            });

        if ($data['exclude'] !== []) {
            $query->whereDoesntHave('genres', fn (Builder $genres) => $genres->whereIn('name', $data['exclude']))
                ->whereDoesntHave('tags', fn (Builder $tags) => $tags
                    ->whereIn('category', array_keys(RecommendationTaxonomyService::CATEGORIES))
                    ->whereIn('name', $data['exclude']))
                ->whereNotIn('status', $data['exclude']);
        }

        // Rank IDs in SQL before hydrating models/relations. Each dimension counts once.
        $matches = [];
        $score = '0';
        $bindings = [];
        if ($data['genres'] !== []) {
            $matches['genres as genre_match'] = fn (Builder $genres) => $genres->whereIn('name', $data['genres']);
            $score .= ' + CASE WHEN genre_match THEN 30 ELSE 0 END';
        }
        foreach (self::TAG_WEIGHTS as $category => [$field, $weight]) {
            if ($data[$field] !== []) {
                $matches['tags as '.$category.'_match'] = fn (Builder $tags) => $tags
                    ->where('category', $category)->whereIn('name', $data[$field]);
                $score .= ' + CASE WHEN '.$category.'_match THEN '.$weight.' ELSE 0 END';
            }
        }
        if ($data['status'] !== null) {
            $score .= ' + CASE WHEN status = ? THEN 5 ELSE 0 END';
            $bindings[] = $data['status'];
        }
        $candidates = $query->select(['comics.id', 'comics.status'])->withExists($matches);
        $ranked = DB::query()->fromSub($candidates, 'candidates')->select('id')
            ->selectRaw($score.' as score', $bindings)
            ->orderByDesc('score')->orderByDesc('id')->limit($limit)->get();
        $comics = Comic::with(['genres', 'tags'])->whereIn('id', $ranked->pluck('id'))->get()->keyBy('id');

        return $ranked->filter(fn ($row) => $comics->has($row->id))->map(function ($row) use ($data, $comics) {
            $comic = $comics->get($row->id);
            $reasons = array_values(array_intersect($data['genres'], $comic->genres->pluck('name')->all()));
            foreach (self::TAG_WEIGHTS as $category => [$field]) {
                $matches = array_intersect($data[$field], $comic->tags->where('category', $category)->pluck('name')->all());
                if ($matches !== []) {
                    $reasons = array_merge($reasons, $matches);
                }
            }
            if ($data['status'] !== null && $comic->status === $data['status']) {
                $reasons[] = $comic->status;
            }

            return ['comic' => $comic, 'score' => (int) $row->score, 'matched_reasons' => array_values(array_unique($reasons))];
        })->values();
    }
}
