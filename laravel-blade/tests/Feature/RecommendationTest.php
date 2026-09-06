<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\Genre;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_guest_can_fetch_trending_recommendations(): void
    {
        Comic::factory(8)->trending()->create();

        $response = $this->getJson(route('recommendations.index'));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('source', 'trending')
            ->assertJsonCount(6, 'comics');
    }

    public function test_recommendations_respect_custom_limit(): void
    {
        Comic::factory(10)->trending()->create();

        $response = $this->getJson(route('recommendations.index', ['limit' => 3]));

        $response->assertStatus(200)
            ->assertJsonPath('count', 3)
            ->assertJsonCount(3, 'comics');
    }

    public function test_authenticated_user_receives_personalized_recommendations(): void
    {
        $user = User::factory()->create();
        $actionGenre = Genre::create(['name' => 'Action', 'slug' => 'action']);
        $romanceGenre = Genre::create(['name' => 'Romance', 'slug' => 'romance']);

        $readComic = Comic::factory()->create(['title' => 'Read Action Comic']);
        $readComic->genres()->attach($actionGenre);
        $chapter = Chapter::factory()->create(['comic_id' => $readComic->id]);

        ReadingHistory::create([
            'user_id'      => $user->id,
            'comic_id'     => $readComic->id,
            'chapter_id'   => $chapter->id,
            'last_read_at' => now(),
        ]);

        $recommendedActionComic = Comic::factory()->create([
            'title'      => 'Recommended Action Comic',
            'avg_rating' => 9.5,
            'views'      => 50000,
        ]);
        $recommendedActionComic->genres()->attach($actionGenre);

        $romanceComic = Comic::factory()->create([
            'title'      => 'Romance Comic',
            'avg_rating' => 8.0,
            'views'      => 1000,
        ]);
        $romanceComic->genres()->attach($romanceGenre);

        $response = $this->actingAs($user)->getJson(route('recommendations.index', ['limit' => 2]));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('source', 'personalized');

        $returnedComicIds = collect($response->json('comics'))->pluck('id')->toArray();
        $this->assertContains($recommendedActionComic->id, $returnedComicIds);
        $this->assertNotContains($readComic->id, $returnedComicIds);
    }

    public function test_similar_comics_recommendation_by_comic_id(): void
    {
        $genre = Genre::create(['name' => 'Fantasy', 'slug' => 'fantasy']);

        $sourceComic = Comic::factory()->create(['title' => 'Main Fantasy Comic']);
        $sourceComic->genres()->attach($genre);

        $similarComic = Comic::factory()->create(['title' => 'Similar Fantasy Comic', 'avg_rating' => 9.0]);
        $similarComic->genres()->attach($genre);

        $response = $this->getJson(route('recommendations.index', ['comic_id' => $sourceComic->id, 'limit' => 4]));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('source', 'similar');

        $returnedIds = collect($response->json('comics'))->pluck('id')->toArray();
        $this->assertContains($similarComic->id, $returnedIds);
        $this->assertNotContains($sourceComic->id, $returnedIds);
    }

    public function test_recommendation_cache_is_invalidated_when_user_saves_history(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'     => $comic->id,
            'published_at' => now()->subMinute(),
        ]);

        $service = app(RecommendationService::class);

        $service->forUser($user, 6);
        $this->assertTrue(Cache::has("recommendations.user.{$user->id}.v0.limit_6.ex_all"));

        $this->actingAs($user)->postJson(route('history.save'), [
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
        ])->assertStatus(200);

        $this->assertEquals(1, Cache::get("rec_ver.user.{$user->id}"));
    }
}
