<?php

namespace Tests\Unit;

use App\Models\Author;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Tag;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private SearchService $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = new SearchService();
    }

    public function test_live_search_returns_empty_when_keyword_is_too_short(): void
    {
        Comic::factory()->create(['title' => 'Solo Leveling']);

        $results = $this->searchService->liveSearch('s');
        $this->assertCount(0, $results);

        $emptyResults = $this->searchService->liveSearch('   ');
        $this->assertCount(0, $emptyResults);
    }

    public function test_live_search_matches_by_title(): void
    {
        $comic1 = Comic::factory()->create(['title' => 'Solo Leveling', 'views' => 1000]);
        $comic2 = Comic::factory()->create(['title' => 'Level 1 Player', 'views' => 500]);
        $comic3 = Comic::factory()->create(['title' => 'Tower of God', 'views' => 2000]);

        $results = $this->searchService->liveSearch('level');

        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('id')->contains($comic1->id));
        $this->assertTrue($results->pluck('id')->contains($comic2->id));
        $this->assertFalse($results->pluck('id')->contains($comic3->id));
    }

    public function test_live_search_matches_by_author_or_genre(): void
    {
        $author = Author::create(['name' => 'Chugong', 'slug' => 'chugong']);
        $genre  = Genre::create(['name' => 'Isekai Fantasy', 'slug' => 'isekai-fantasy']);

        $comic1 = Comic::factory()->create(['title' => 'Shadow Monarch']);
        $comic1->authors()->attach($author->id, ['role' => 'story']);

        $comic2 = Comic::factory()->create(['title' => 'Reborn in Another World']);
        $comic2->genres()->attach($genre->id);

        $resByAuthor = $this->searchService->liveSearch('Chugong');
        $this->assertTrue($resByAuthor->pluck('id')->contains($comic1->id));

        $resByGenre = $this->searchService->liveSearch('Isekai');
        $this->assertTrue($resByGenre->pluck('id')->contains($comic2->id));
    }

    public function test_advanced_search_filters_by_multiple_criteria(): void
    {
        $genreAction = Genre::create(['name' => 'Action', 'slug' => 'action']);
        $genreComedy = Genre::create(['name' => 'Comedy', 'slug' => 'comedy']);

        $comic1 = Comic::factory()->create([
            'title'        => 'Hero Returns',
            'status'       => 'ONGOING',
            'is_original'  => true,
            'avg_rating'   => 4.7,
            'views'        => 5000,
        ]);
        $comic1->genres()->attach([$genreAction->id]);

        $comic2 = Comic::factory()->create([
            'title'        => 'Daily Life in Dungeon',
            'status'       => 'COMPLETED',
            'is_original'  => false,
            'avg_rating'   => 3.5,
            'views'        => 2000,
        ]);
        $comic2->genres()->attach([$genreAction->id, $genreComedy->id]);

        // 1. Lọc theo genre action + status ONGOING
        $res1 = $this->searchService->advancedSearch([
            'genres' => ['action'],
            'status' => 'ONGOING',
        ]);
        $this->assertEquals(1, $res1->total());
        $this->assertEquals($comic1->id, $res1->items()[0]->id);

        // 2. Lọc theo min_rating = 4.0
        $res2 = $this->searchService->advancedSearch([
            'min_rating' => 4.0,
        ]);
        $this->assertEquals(1, $res2->total());
        $this->assertEquals($comic1->id, $res2->items()[0]->id);

        // 3. Lọc theo is_original = true
        $res3 = $this->searchService->advancedSearch([
            'is_original' => 'true',
        ]);
        $this->assertEquals(1, $res3->total());
        $this->assertEquals($comic1->id, $res3->items()[0]->id);
    }

    public function test_advanced_search_sorts_correctly(): void
    {
        $comicA = Comic::factory()->create(['title' => 'Alpha Hero', 'views' => 100, 'avg_rating' => 4.2]);
        $comicB = Comic::factory()->create(['title' => 'Beta Hero', 'views' => 500, 'avg_rating' => 4.9]);

        // Sắp xếp views DESC
        $resViews = $this->searchService->advancedSearch(['sort' => 'views']);
        $this->assertEquals($comicB->id, $resViews->items()[0]->id);

        // Sắp xếp rating DESC
        $resRating = $this->searchService->advancedSearch(['sort' => 'rating']);
        $this->assertEquals($comicB->id, $resRating->items()[0]->id);

        // Sắp xếp Alphabetical ASC
        $resAlpha = $this->searchService->advancedSearch(['sort' => 'alphabetical']);
        $this->assertEquals($comicA->id, $resAlpha->items()[0]->id);
    }
}
