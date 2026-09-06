<?php

namespace Tests\Unit;

use App\Models\Comic;
use App\Models\Genre;
use App\Models\Library;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = new RecommendationService();
    }

    public function test_for_guest_returns_trending_and_caches_result(): void
    {
        Comic::factory(6)->trending()->create();

        $version = (int) Cache::get('recommendations.guest.version', 1);
        $result = $this->service->forGuest(4);

        $this->assertCount(4, $result);
        $this->assertTrue(Cache::has("recommendations.guest.v{$version}.limit_4.ex_all"));
    }

    public function test_for_comic_returns_similar_genre_comics(): void
    {
        $action = Genre::create(['name' => 'Action', 'slug' => 'action']);
        $comedy = Genre::create(['name' => 'Comedy', 'slug' => 'comedy']);

        $mainComic = Comic::factory()->create(['title' => 'Original Action']);
        $mainComic->genres()->attach($action);

        $matchingComic = Comic::factory()->create(['title' => 'Matching Action', 'avg_rating' => 9.8]);
        $matchingComic->genres()->attach($action);

        $otherComic = Comic::factory()->create(['title' => 'Other Comedy', 'avg_rating' => 6.0]);
        $otherComic->genres()->attach($comedy);

        $results = $this->service->forComic($mainComic, 2);

        $ids = $results->pluck('id')->toArray();
        $this->assertContains($matchingComic->id, $ids);
        $this->assertNotContains($mainComic->id, $ids);
    }

    public function test_for_user_aggregates_reading_history_and_library_genres(): void
    {
        $user = User::factory()->create();

        $genreA = Genre::create(['name' => 'Genre A', 'slug' => 'genre-a']);
        $genreB = Genre::create(['name' => 'Genre B', 'slug' => 'genre-b']);

        $historyComic = Comic::factory()->create();
        $historyComic->genres()->attach($genreA);
        $historyChapter = \App\Models\Chapter::factory()->create(['comic_id' => $historyComic->id]);
        ReadingHistory::create([
            'user_id'      => $user->id,
            'comic_id'     => $historyComic->id,
            'chapter_id'   => $historyChapter->id,
            'last_read_at' => now(),
        ]);

        $libComic = Comic::factory()->create();
        $libComic->genres()->attach($genreB);
        Library::create([
            'user_id'  => $user->id,
            'comic_id' => $libComic->id,
            'status'   => 'reading',
        ]);

        $candidateA = Comic::factory()->create(['avg_rating' => 9.5]);
        $candidateA->genres()->attach($genreA);

        $candidateB = Comic::factory()->create(['avg_rating' => 9.2]);
        $candidateB->genres()->attach($genreB);

        $candidateC = Comic::factory()->create(['avg_rating' => 9.0]);
        $candidateC->genres()->attach($genreA);

        $candidateD = Comic::factory()->create(['avg_rating' => 8.8]);
        $candidateD->genres()->attach($genreB);

        $recommendations = $this->service->forUser($user, 4);
        $recIds = $recommendations->pluck('id')->toArray();

        $this->assertContains($candidateA->id, $recIds);
        $this->assertContains($candidateB->id, $recIds);
        $this->assertNotContains($historyComic->id, $recIds);
        $this->assertNotContains($libComic->id, $recIds);
    }

    public function test_invalidate_for_user_clears_all_limit_variants(): void
    {
        $user = User::factory()->create();

        $this->service->forUser($user, 4);
        $this->service->forUser($user, 6);

        $this->assertTrue(Cache::has("recommendations.user.{$user->id}.v0.limit_4.ex_all"));
        $this->assertTrue(Cache::has("recommendations.user.{$user->id}.v0.limit_6.ex_all"));

        $this->service->invalidateForUser($user->id);

        $this->assertEquals(1, Cache::get("rec_ver.user.{$user->id}"));
    }
}
