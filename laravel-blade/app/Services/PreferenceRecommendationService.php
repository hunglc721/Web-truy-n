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
     * Recommend public comics for a parsed preference.
     *
     * Matching policy:
     *  - status, publication, and exclusions are hard constraints. They are
     *    applied in SQL before ranking, so a result can never violate them.
     *  - genres and the theme/setting/character/tone tags are positive
     *    requirements. Every requested value must be present for a "full match".
     *    Relationships are excluded from positive matching because the current
     *    MVP gives them no positive weight (exclusion still applies).
     *  - full matches always rank above partial matches. When at least one full
     *    match exists, only full matches are returned: the service does not pad
     *    the result set with weaker partial matches.
     *  - if no full match exists, the documented partial fallback returns the
     *    candidates matching the most requested values, then the weighted score,
     *    then the newest id. Every fallback result still satisfies publication,
     *    status, and exclusions. This raises precision on multi-condition
     *    requests and intentionally trades away padded recall.
     *
     * @return Collection<int, array{comic: Comic, score: int, matched_reasons: array}>
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

        // Publication and status are hard constraints, not ranking signals.
        $query = Comic::query()->whereNotNull('published_at')->where('published_at', '<=', now());
        if ($data['status'] !== null) {
            $query->where('status', $data['status']);
        }

        if ($data['exclude'] !== []) {
            $query->whereDoesntHave('genres', fn (Builder $genres) => $genres->whereIn('name', $data['exclude']))
                ->whereDoesntHave('tags', fn (Builder $tags) => $tags
                    ->whereIn('category', array_keys(RecommendationTaxonomyService::CATEGORIES))
                    ->whereIn('name', $data['exclude']))
                ->whereNotIn('status', $data['exclude']);
        }

        // Candidates must match at least one positive requirement. Status-only
        // requests have no positive requirement and keep every status match.
        $hasPositive = $data['genres'] !== [];
        foreach (self::TAG_WEIGHTS as [$field]) {
            $hasPositive = $hasPositive || $data[$field] !== [];
        }
        if ($hasPositive) {
            $query->where(function (Builder $query) use ($data) {
                $query->whereRaw('1 = 0');
                if ($data['genres'] !== []) {
                    $query->orWhereHas('genres', fn (Builder $genres) => $genres->whereIn('name', $data['genres']));
                }
                foreach (self::TAG_WEIGHTS as $category => [$field]) {
                    if ($data[$field] !== []) {
                        $query->orWhereHas('tags', fn (Builder $tags) => $tags->where('category', $category)->whereIn('name', $data[$field]));
                    }
                }
            });
        }

        // One exists flag per requested value. The flags drive both the
        // dimension score and the number of matched requirements.
        $matches = [];
        $genreAliases = [];
        foreach ($data['genres'] as $index => $genre) {
            $alias = 'genre_'.$index.'_match';
            $genreAliases[] = $alias;
            $matches['genres as '.$alias] = fn (Builder $genres) => $genres->where('name', $genre);
        }
        $categoryAliases = [];
        foreach (self::TAG_WEIGHTS as $category => [$field]) {
            $categoryAliases[$category] = [];
            foreach ($data[$field] as $index => $value) {
                $alias = $category.'_'.$index.'_match';
                $categoryAliases[$category][] = $alias;
                $matches['tags as '.$alias] = fn (Builder $tags) => $tags->where('category', $category)->where('name', $value);
            }
        }

        // Each dimension keeps its documented weight and counts once, even
        // when several values in that dimension match.
        $score = ['0'];
        $bindings = [];
        if ($genreAliases !== []) {
            $score[] = 'CASE WHEN ('.implode(' OR ', $genreAliases).') THEN 30 ELSE 0 END';
        }
        foreach (self::TAG_WEIGHTS as $category => [$field, $weight]) {
            if ($categoryAliases[$category] !== []) {
                $score[] = 'CASE WHEN ('.implode(' OR ', $categoryAliases[$category]).') THEN '.$weight.' ELSE 0 END';
            }
        }
        if ($data['status'] !== null) {
            $score[] = 'CASE WHEN status = ? THEN 5 ELSE 0 END';
            $bindings[] = $data['status'];
        }

        $requiredAliases = array_merge($genreAliases, ...array_values($categoryAliases));
        $requiredCount = count($requiredAliases);
        $matchedValues = $requiredAliases === []
            ? '0'
            : implode(' + ', array_map(
                static fn (string $alias): string => 'CASE WHEN '.$alias.' THEN 1 ELSE 0 END',
                $requiredAliases,
            ));

        // Rank ids in SQL before hydrating models/relations. Full matches have
        // the highest matched_values value, so they always sort first.
        $candidates = $query->select(['comics.id', 'comics.status'])->withExists($matches);
        $ranked = DB::query()->fromSub($candidates, 'candidates')->select('id')
            ->selectRaw(implode(' + ', $score).' as score', $bindings)
            ->selectRaw($matchedValues.' as matched_values')
            ->orderByDesc('matched_values')->orderByDesc('score')->orderByDesc('id')
            ->limit($limit)->get();

        // A full match exists when the top candidate matched every requested
        // value. In that case drop the partial rows that only filled the limit.
        $hasFullMatch = $ranked->isNotEmpty()
            && ($requiredCount === 0 || (int) $ranked->first()->matched_values === $requiredCount);
        if ($hasFullMatch && $requiredCount > 0) {
            $ranked = $ranked->filter(
                fn ($row): bool => (int) $row->matched_values === $requiredCount,
            )->values();
        }

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
