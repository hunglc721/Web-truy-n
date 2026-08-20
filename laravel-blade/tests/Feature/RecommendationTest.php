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

/**
 * Feature tests cho Recommendation API & Engine
 *
 * Covers:
 *  - Guest: GET /api/recommendations -> source: trending
 *  - Authenticated: GET /api/recommendations -> source: personalized (genre-based)
 *  - Similar: GET /api/recommendations?comic_id=X -> source: similar
 *  - Limit param validation (min 1, max 24, default 6)
 *  - Cache invalidation on reading history save
 */
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
        // Tạo 8 comics trending
        $comics = Comic::factory(8)->trending()->create();

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

        // Tạo 2 genres: Action và Romance
        $actionGenre = Genre::create(['name' => 'Action', 'slug' => 'action']);
        $romanceGenre = Genre::create(['name' => 'Romance', 'slug' => 'romance']);

        // Comic 1: Action (user đã đọc)
        $readComic = Comic::factory()->create(['title' => 'Read Action Comic']);
        $readComic->genres()->attach($actionGenre);
        $chapter = Chapter::factory()->create(['comic_id' => $readComic->id]);

        // Tạo lịch sử đọc
        ReadingHistory::create([
            'user_id'      => $user->id,
            'comic_id'     => $readComic->id,
            'chapter_id'   => $chapter->id,
            'last_read_at' => now(),
        ]);

        // Comic 2: Action khác (chưa đọc, rating cao)
        $recommendedActionComic = Comic::factory()->create([
            'title'      => 'Recommended Action Comic',
            'avg_rating' => 9.5,
            'views'      => 50000,
        ]);
        $recommendedActionComic->genres()->attach($actionGenre);

        // Comic 3: Romance (không liên quan)
        $romanceComic = Comic::factory()->create([
            'title'      => 'Romance Comic',
            'avg_rating' => 8.0,
            'views'      => 1000,
        ]);
        $romanceComic->genres()->attach($romanceGenre);

        $response = $this->actingAs($user)->getJson(route('recommendations.index'));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('source', 'personalized');

        $returnedComicIds = collect($response->json('comics'))->pluck('id')->toArray();

        // Truyện cùng thể loại Action phải được ưu tiên gợi ý
        $this->assertContains($recommendedActionComic->id, $returnedComicIds);
        // Truyện đã đọc không xuất hiện lại trong gợi ý
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
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);

        $service = app(RecommendationService::class);

        // Prime cache
        $service->forUser($user, 6);
        $this->assertTrue(Cache::has("recommendations.user.{$user->id}.limit_6"));

        // Gửi request save reading history
        $this->actingAs($user)->postJson(route('history.save'), [
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
        ])->assertStatus(200);

        // Cache cho user phải bị invalidate
        $this->assertFalse(Cache::has("recommendations.user.{$user->id}.limit_6"));
    }
}
