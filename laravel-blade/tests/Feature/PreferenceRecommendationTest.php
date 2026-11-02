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
        $genreOnly = $this->comic(attributes: ['status' => 'completed']);
        $closest = $this->comic(['Fantasy', 'Action'], ['System', 'Dungeon', 'Overpowered MC', 'Modern', 'Dark'], ['status' => 'completed']);
        $unrelated = $this->comic(['Horror']);
        // No fixture has the Serious tone, so this exercises the partial
        // fallback while still checking the documented dimension weights.
        $results = $this->results(['genres' => ['Fantasy', 'Action', 'Fantasy'], 'themes' => ['System', 'Dungeon'],
            'character_traits' => ['Overpowered MC'], 'settings' => ['Modern'], 'tones' => ['Dark', 'Serious'], 'status' => 'completed']);
        $this->assertSame([$closest->id, $genreOnly->id], $results->pluck('comic.id')->all());
        $this->assertSame([105, 35], $results->pluck('score')->all());
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

    public function test_each_dimension_has_its_documented_weight(): void
    {
        $comic = $this->comic(['Fantasy'], ['System', 'Overpowered MC', 'Modern', 'Dark'], ['status' => 'completed']);
        foreach (['genres' => ['Fantasy', 30], 'themes' => ['System', 25],
            'character_traits' => ['Overpowered MC', 20], 'settings' => ['Modern', 15],
            'tones' => ['Dark', 10], 'status' => ['completed', 5]] as $dimension => [$value, $weight]) {
            $results = $this->results([$dimension => $dimension === 'status' ? $value : [$value]]);
            $this->assertSame([$comic->id], $results->pluck('comic.id')->all(), $dimension);
            $this->assertSame($weight, $results[0]['score'], $dimension);
        }
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
        $this->assertSame([$newer->id, $older->id], $results->pluck('comic.id')->all());
        $this->assertSame([35, 35], $results->pluck('score')->all());
        $this->assertNotContains($ongoing->id, $results->pluck('comic.id')->all());
        $this->assertSame($results->pluck('comic.id')->all(), $this->results($data)->pluck('comic.id')->all());
        $this->assertSame([$newer->id], $this->results($data, 1)->pluck('comic.id')->all());
        $this->assertSame([5, 5], $this->results(['status' => 'completed'])->pluck('score')->all());
    }

    public function test_multi_condition_query_returns_only_full_matches(): void
    {
        $full = $this->comic(['Fantasy'], ['System', 'Overpowered MC'], ['status' => 'completed']);
        $genreOnly = $this->comic(['Fantasy'], [], ['status' => 'completed']);
        $tagOnly = $this->comic(['Action'], ['System', 'Overpowered MC'], ['status' => 'completed']);

        $results = $this->results([
            'genres' => ['Fantasy'], 'themes' => ['System'],
            'character_traits' => ['Overpowered MC'], 'status' => 'completed',
        ]);

        $this->assertSame([$full->id], $results->pluck('comic.id')->all());
        $this->assertSame([80], $results->pluck('score')->all());
        $this->assertNotContains($genreOnly->id, $results->pluck('comic.id')->all());
        $this->assertNotContains($tagOnly->id, $results->pluck('comic.id')->all());
    }

    public function test_status_is_a_hard_constraint_before_partial_ranking(): void
    {
        // The ongoing comic has every positive signal, but the user asked for
        // completed stories. Status must win over score and matched values.
        $completedPartial = $this->comic(['Fantasy'], [], ['status' => 'completed']);
        $this->comic(['Fantasy'], ['System', 'Overpowered MC', 'Modern', 'Dark'], ['status' => 'ongoing']);

        $results = $this->results([
            'genres' => ['Fantasy'], 'themes' => ['System'],
            'character_traits' => ['Overpowered MC'], 'settings' => ['Modern'],
            'tones' => ['Dark'], 'status' => 'completed',
        ]);

        $this->assertSame([$completedPartial->id], $results->pluck('comic.id')->all());
        $this->assertSame([35], $results->pluck('score')->all());
    }

    public function test_partial_fallback_ranks_by_matched_conditions_and_keeps_hard_constraints(): void
    {
        // No comic has both Fantasy and Horror, so the fallback must return
        // partial matches ranked by how many requested values they satisfy.
        $twoMatches = $this->comic(['Fantasy'], ['System'], ['status' => 'completed']);
        $oneMatch = $this->comic(['Horror'], [], ['status' => 'completed']);
        $excluded = $this->comic(['Fantasy'], ['System'], ['status' => 'completed']);
        $excluded->tags()->attach(Tag::where('name', 'Harem')->value('id'));
        $this->comic(['Fantasy'], ['System'], ['status' => 'ongoing']);
        $this->comic(['Fantasy'], ['System'], ['status' => 'completed', 'published_at' => null]);

        $results = $this->results([
            'genres' => ['Fantasy', 'Horror'], 'themes' => ['System'],
            'status' => 'completed', 'exclude' => ['Harem'],
        ], 10);

        $this->assertSame([$twoMatches->id, $oneMatch->id], $results->pluck('comic.id')->all());
        $this->assertSame([60, 35], $results->pluck('score')->all());
        $this->assertNotContains($excluded->id, $results->pluck('comic.id')->all());
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
            $this->assertFalse($result['comic']->relationLoaded('authors'), 'Unused authors must not be loaded.');
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
