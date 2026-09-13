<?php

namespace Tests\Feature;

use App\Data\RecommendationPreference;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Tag;
use App\Services\AI\AIClientInterface;
use App\Services\PreferenceRecommendationService;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PreferenceRecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RecommendationTaxonomySeeder::class);
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
    }

    private function comic(array $genres = ['Fantasy'], array $tags = [], array $attributes = []): Comic
    {
        $comic = Comic::factory()->create(array_merge(['status' => 'ongoing', 'published_at' => now()->subDay()], $attributes));
        foreach ($genres as $name) {
            $comic->genres()->attach(Genre::firstOrCreate(['name' => $name])->id);
        }
        $comic->tags()->attach(Tag::whereIn('name', $tags)->pluck('id'));

        return $comic;
    }

    private function results(array $data, int $limit = 5)
    {
        return app(PreferenceRecommendationService::class)->recommend(RecommendationPreference::fromArray($data), $limit);
    }

    public function test_weights_reasons_and_dimension_scores_are_bounded(): void
    {
        $genreOnly = $this->comic();
        $full = $this->comic(['Fantasy', 'Action'], ['System', 'Dungeon', 'Overpowered MC', 'Modern', 'Dark'], ['status' => 'completed']);
        $unrelated = $this->comic(['Horror']);
        $results = $this->results(['genres' => ['Fantasy', 'Action', 'Fantasy'], 'themes' => ['System', 'Dungeon'],
            'character_traits' => ['Overpowered MC'], 'settings' => ['Modern'], 'tones' => ['Dark'], 'status' => 'completed']);
        $this->assertSame([$full->id, $genreOnly->id], $results->pluck('comic.id')->all());
        $this->assertSame([105, 30], $results->pluck('score')->all());
        $this->assertEqualsCanonicalizing(['Fantasy', 'Action', 'System', 'Dungeon', 'Overpowered MC', 'Modern', 'Dark', 'completed'], $results[0]['matched_reasons']);
        $this->assertNotContains($unrelated->id, $results->pluck('comic.id')->all());
    }

    public function test_exclusions_remove_genres_tags_and_status_even_for_high_score(): void
    {
        $allowed = $this->comic();
        $this->comic(['Fantasy'], ['Harem']);
        $this->comic(['Fantasy', 'Romance']);
        $this->comic(['Fantasy'], ['Dark']);
        $this->comic(['Fantasy'], [], ['status' => 'hiatus']);
        $results = $this->results(['genres' => ['Fantasy'], 'exclude' => ['Harem', 'Romance', 'Dark', 'hiatus']]);
        $this->assertSame([$allowed->id], $results->pluck('comic.id')->all());
    }

    public function test_publication_and_soft_delete_filter(): void
    {
        $public = $this->comic();
        $this->comic(attributes: ['published_at' => null]);
        $this->comic(attributes: ['published_at' => now()->addDay()]);
        $deleted = $this->comic();
        $deleted->delete();
        $this->assertSame([$public->id], $this->results(['genres' => ['Fantasy']])->pluck('comic.id')->all());
    }

    public function test_status_and_stable_tie_break_and_limit(): void
    {
        $older = $this->comic(attributes: ['status' => 'completed']);
        $newer = $this->comic(attributes: ['status' => 'completed']);
        $ongoing = $this->comic();
        $data = ['genres' => ['Fantasy'], 'status' => 'completed'];
        $results = $this->results($data);
        $this->assertSame([$newer->id, $older->id, $ongoing->id], $results->pluck('comic.id')->all());
        $this->assertSame([35, 35, 30], $results->pluck('score')->all());
        $this->assertSame($results->pluck('comic.id')->all(), $this->results($data)->pluck('comic.id')->all());
        $this->assertSame([$newer->id], $this->results($data, 1)->pluck('comic.id')->all());
        $this->assertSame([5, 5], $this->results(['status' => 'completed'])->pluck('score')->all());
    }

    public function test_empty_preferences_and_zero_limit_do_not_return_random_comics(): void
    {
        $this->comic();
        $this->assertCount(0, $this->results([]));
        $this->assertCount(0, $this->results(['exclude' => ['Harem']]));
        $this->assertCount(0, $this->results(['genres' => ['Fantasy']], 0));
    }

    public function test_eager_loading_has_fixed_query_count_for_multiple_results(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->comic(tags: ['System']);
        }
        DB::enableQueryLog();
        DB::flushQueryLog();
        $results = $this->results(['genres' => ['Fantasy']], 8);
        foreach ($results as $result) {
            $this->assertTrue($result['comic']->relationLoaded('genres'));
            $this->assertTrue($result['comic']->relationLoaded('tags'));
            $this->assertTrue($result['comic']->relationLoaded('authors'));
        }
        $this->assertCount(4, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_tag_matching_uses_category_and_ignores_legacy_exclusions(): void
    {
        $comic = $this->comic();
        $legacy = Tag::create(['name' => 'Legacy', 'slug' => 'legacy']);
        $wrongCategory = Tag::create(['name' => 'System', 'slug' => 'system-tone', 'category' => 'tone']);
        $comic->tags()->attach([$legacy->id, $wrongCategory->id]);
        $results = $this->results(['genres' => ['Fantasy'], 'themes' => ['System'], 'exclude' => ['Legacy']]);
        $this->assertSame(30, $results[0]['score']);
        $this->assertSame(['Fantasy'], $results[0]['matched_reasons']);
    }
}
