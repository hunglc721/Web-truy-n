<?php

namespace App\Services;

use App\Data\RecommendationPreference;
use App\Models\Comic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

        return $query->with(['genres', 'tags', 'authors'])->get()->map(function (Comic $comic) use ($data) {
            $score = 0;
            $reasons = array_values(array_intersect($data['genres'], $comic->genres->pluck('name')->all()));
            if ($reasons !== []) {
                $score += 30;
            }
            foreach (self::TAG_WEIGHTS as $category => [$field, $weight]) {
                $matches = array_intersect($data[$field], $comic->tags->where('category', $category)->pluck('name')->all());
                if ($matches !== []) {
                    $score += $weight;
                    $reasons = array_merge($reasons, $matches);
                }
            }
            if ($data['status'] !== null && $comic->status === $data['status']) {
                $score += 5;
                $reasons[] = $comic->status;
            }

            return ['comic' => $comic, 'score' => $score, 'matched_reasons' => array_values(array_unique($reasons))];
        })->filter(fn (array $result) => $result['score'] > 0)
            ->sort(fn (array $a, array $b) => ($b['score'] <=> $a['score']) ?: ($b['comic']->id <=> $a['comic']->id))
            ->take($limit)->values();
    }
}
