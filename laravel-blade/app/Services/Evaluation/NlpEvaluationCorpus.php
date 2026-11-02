<?php

declare(strict_types=1);

namespace App\Services\Evaluation;

use App\Models\Author;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Tag;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Seeds the synthetic evaluation catalog into the current database.
 *
 * The command always calls this inside a transaction that is rolled back, so
 * the catalog is never persisted in a normal run. Fixture slugs are namespaced
 * with "nlp-eval-" for defensive cleanup after an interrupted process.
 */
final class NlpEvaluationCorpus
{
    /** @var array<string, int> */
    private array $ids = [];

    /** @var array<string, array<string, mixed>> */
    private array $meta = [];

    public function seed(NlpEvaluationDataset $dataset): void
    {
        $this->cleanup();
        $this->ids = [];
        $this->meta = [];

        $genres = [];
        foreach ($dataset->genres() as $name) {
            $genres[$name] = Genre::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }

        (new RecommendationTaxonomySeeder)->run();
        $tags = Tag::query()->whereIn('name', $dataset->tagNames())->get()->keyBy('name');
        $missingTags = array_values(array_diff($dataset->tagNames(), $tags->keys()->all()));
        if ($missingTags !== []) {
            throw new RuntimeException('Evaluation taxonomy is missing tags: '.implode(', ', $missingTags));
        }

        foreach ($dataset->comics() as $comic) {
            $publishedAt = $comic['published_offset_days'] === null
                ? null
                : now()->addDays($comic['published_offset_days'])->startOfDay();

            $model = Comic::factory()->create([
                'title' => $comic['title'],
                'slug' => 'nlp-eval-'.$comic['key'],
                'alt_titles' => $comic['alt_titles'],
                'cover_image' => 'covers/nlp-eval-'.$comic['key'].'.jpg',
                'description' => $comic['description'],
                'status' => $comic['status'],
                'published_at' => $publishedAt,
                'views' => $comic['views'],
                'avg_rating' => 4.0,
            ]);

            $genreIds = [];
            foreach ($comic['genres'] as $index => $name) {
                $genreIds[$genres[$name]->id] = ['is_primary' => $index === 0];
            }
            $model->genres()->attach($genreIds);

            $tagIds = [];
            foreach ($comic['tags'] as $values) {
                foreach ($values as $name) {
                    $tagIds[] = $tags->get($name)->id;
                }
            }
            $model->tags()->attach(array_values(array_unique($tagIds)));

            $author = Author::query()->firstOrCreate(
                ['slug' => Str::slug($comic['author'])],
                ['name' => $comic['author']],
            );
            $model->authors()->attach($author->id, ['role' => 'story']);

            $flatTags = [];
            foreach ($comic['tags'] as $values) {
                $flatTags = [...$flatTags, ...$values];
            }
            $this->ids[$comic['key']] = $model->id;
            $this->meta[$comic['key']] = [
                'title' => $comic['title'],
                'published' => $comic['published_offset_days'] !== null && $comic['published_offset_days'] <= 0,
                'genres' => $comic['genres'],
                'tags' => array_values(array_unique($flatTags)),
                'status' => $comic['status'],
            ];
        }
    }

    public function cleanup(): void
    {
        Comic::withTrashed()->where('slug', 'like', 'nlp-eval-%')->forceDelete();
    }

    /** @return array<string, int> */
    public function ids(): array
    {
        return $this->ids;
    }

    /** @return array<string, array<string, mixed>> */
    public function meta(): array
    {
        return $this->meta;
    }

    public function idFor(string $key): ?int
    {
        return $this->ids[$key] ?? null;
    }

    public function keyFor(int $id): ?string
    {
        $key = array_search($id, $this->ids, true);

        return is_string($key) ? $key : null;
    }
}
