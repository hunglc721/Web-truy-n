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

    public function test_live_search_matches_author_without_accents(): void
    {
        $author = Author::create(['name' => 'Mặc Tử']);
        $comic = Comic::factory()->create(['title' => 'Võ Luyện Đỉnh Phong']);
        $comic->authors()->attach($author->id, ['role' => 'story']);

        $results = $this->searchService->liveSearch('mac tu');

        $this->assertTrue($results->pluck('id')->contains($comic->id));
    }

    public function test_live_search_hides_unpublished_comics(): void
    {
        $published = Comic::factory()->create(['title' => 'Hidden Gem Published']);
        $draft = Comic::factory()->create(['title' => 'Hidden Gem Draft', 'published_at' => null]);
        $scheduled = Comic::factory()->create(['title' => 'Hidden Gem Scheduled', 'published_at' => now()->addDay()]);

        $ids = $this->searchService->liveSearch('hidden gem')->pluck('id');

        $this->assertTrue($ids->contains($published->id));
        $this->assertFalse($ids->contains($draft->id));
        $this->assertFalse($ids->contains($scheduled->id));
    }

    public function test_advanced_search_matches_description_without_accents(): void
    {
        $comic = Comic::factory()->create([
            'title' => 'Trinh Thám Đêm Mưa',
            'description' => 'Thám tử phá án trong thành phố hiện đại.',
        ]);

        $results = $this->searchService->advancedSearch([
            'q' => 'tham tu pha an trong thanh pho',
        ]);

        $this->assertSame(1, $results->total());
        $this->assertSame($comic->id, $results->items()[0]->id);
    }

    public function test_advanced_search_hides_unpublished_comics(): void
    {
        $published = Comic::factory()->create(['title' => 'Public Case Files']);
        $draft = Comic::factory()->create(['title' => 'Draft Case Files', 'published_at' => null]);
        $scheduled = Comic::factory()->create(['title' => 'Scheduled Case Files', 'published_at' => now()->addDay()]);

        $ids = collect($this->searchService->advancedSearch(['q' => 'case files'])->items())->pluck('id');

        $this->assertTrue($ids->contains($published->id));
        $this->assertFalse($ids->contains($draft->id));
        $this->assertFalse($ids->contains($scheduled->id));
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

    public function test_live_search_matches_vietnamese_without_accents(): void
    {
        $comic1 = Comic::factory()->create(['title' => 'Độc Tôn Tam Giới']);
        $comic2 = Comic::factory()->create(['title' => 'Tôi Thăng Cấp Một Mình', 'alt_titles' => 'Solo Leveling']);

        // User gõ không dấu: "doc ton" -> match "Độc Tôn Tam Giới"
        $results1 = $this->searchService->liveSearch('doc ton');
        $this->assertTrue($results1->pluck('id')->contains($comic1->id));

        // User gõ không dấu: "thang cap" -> match "Tôi Thăng Cấp Một Mình"
        $results2 = $this->searchService->liveSearch('thang cap');
        $this->assertTrue($results2->pluck('id')->contains($comic2->id));

        // User gõ tên tiếng Anh: "solo leveling" -> match qua alt_titles
        $results3 = $this->searchService->liveSearch('solo leveling');
        $this->assertTrue($results3->pluck('id')->contains($comic2->id));
    }

    public function test_search_records_and_retrieves_hot_keywords(): void
    {
        Comic::factory()->create(['title' => 'Solo Leveling']);

        // Thực hiện tìm kiếm để ghi nhận keywords
        $this->searchService->liveSearch('Solo Leveling');
        $this->searchService->liveSearch('Solo Leveling');
        $this->searchService->liveSearch('One Piece');

        $hot = $this->searchService->getHotKeywords(5);
        $this->assertNotEmpty($hot);
        $this->assertEquals('Solo Leveling', $hot->first()->keyword);
        $this->assertGreaterThanOrEqual(2, $hot->first()->hits);
    }

    public function test_advanced_search_filters_by_country_and_exclude_genres(): void
    {
        $genreAction = Genre::create(['name' => 'Action', 'slug' => 'action']);
        $genreHorror = Genre::create(['name' => 'Horror', 'slug' => 'horror']);

        $manga = Comic::factory()->create([
            'title'   => 'Tokyo Ghoul',
            'country' => 'JP',
        ]);
        $manga->genres()->attach([$genreAction->id, $genreHorror->id]);

        $manhwa = Comic::factory()->create([
            'title'   => 'Omniscient Reader',
            'country' => 'KR',
        ]);
        $manhwa->genres()->attach([$genreAction->id]);

        // Lọc genre = action NHƯNG loại trừ horror
        $resExclude = $this->searchService->advancedSearch([
            'genres'         => ['action'],
            'exclude_genres' => ['horror'],
        ]);
        $this->assertEquals(1, $resExclude->total());
        $this->assertEquals($manhwa->id, $resExclude->items()[0]->id);
    }

    public function test_advanced_search_min_chapters_excludes_comics_below_threshold(): void
    {
        $belowThreshold = Comic::factory()->create(['title' => 'Only Two Chapters']);
        $this->createPublishedChapters($belowThreshold, 2);

        $atThreshold = Comic::factory()->create(['title' => 'Exactly Three Chapters']);
        $this->createPublishedChapters($atThreshold, 3);

        $results = $this->searchService->advancedSearch(['min_chapters' => 3]);
        $ids = collect($results->items())->pluck('id');

        $this->assertSame(1, $results->total());
        $this->assertFalse($ids->contains($belowThreshold->id), 'Comic with fewer chapters than the threshold must be excluded.');
        $this->assertTrue($ids->contains($atThreshold->id), 'Comic at the threshold must still be returned.');
    }

    public function test_advanced_search_min_chapters_includes_comic_at_threshold(): void
    {
        $atThreshold = Comic::factory()->create(['title' => 'Exactly Four Chapters']);
        $this->createPublishedChapters($atThreshold, 4);

        $results = $this->searchService->advancedSearch(['min_chapters' => 4]);
        $ids = collect($results->items())->pluck('id');

        $this->assertSame(1, $results->total());
        $this->assertTrue($ids->contains($atThreshold->id));
    }

    public function test_advanced_search_min_chapters_includes_comics_above_threshold(): void
    {
        $aboveThreshold = Comic::factory()->create(['title' => 'Seven Chapters']);
        $this->createPublishedChapters($aboveThreshold, 7);

        $results = $this->searchService->advancedSearch(['min_chapters' => 4]);
        $ids = collect($results->items())->pluck('id');

        $this->assertSame(1, $results->total());
        $this->assertTrue($ids->contains($aboveThreshold->id));
    }

    public function test_advanced_search_min_chapters_still_hides_unpublished_comics(): void
    {
        $published = Comic::factory()->create(['title' => 'Published Plenty']);
        $this->createPublishedChapters($published, 5);

        $draft = Comic::factory()->create(['title' => 'Draft Plenty', 'published_at' => null]);
        $this->createPublishedChapters($draft, 10);

        $scheduled = Comic::factory()->create(['title' => 'Scheduled Plenty', 'published_at' => now()->addDay()]);
        $this->createPublishedChapters($scheduled, 10);

        $results = $this->searchService->advancedSearch(['min_chapters' => 3]);
        $ids = collect($results->items())->pluck('id');

        $this->assertSame(1, $results->total());
        $this->assertTrue($ids->contains($published->id));
        $this->assertFalse($ids->contains($draft->id), 'Unpublished comics must stay hidden even when they have enough chapters.');
        $this->assertFalse($ids->contains($scheduled->id), 'Scheduled comics must stay hidden even when they have enough chapters.');
    }

    public function test_advanced_search_min_chapters_counts_only_published_chapters(): void
    {
        $futureHeavy = Comic::factory()->create(['title' => 'Mostly Scheduled']);
        $this->createPublishedChapters($futureHeavy, 2);
        $this->createScheduledChapters($futureHeavy, 5, startNumber: 3);

        $results = $this->searchService->advancedSearch(['min_chapters' => 3]);
        $ids = collect($results->items())->pluck('id');

        $this->assertSame(0, $results->total());
        $this->assertFalse($ids->contains($futureHeavy->id), 'Only published chapters count toward min_chapters.');
    }

    public function test_advanced_search_without_min_chapters_keeps_existing_behavior(): void
    {
        $noChapters = Comic::factory()->create(['title' => 'No Chapters Yet']);
        $manyChapters = Comic::factory()->create(['title' => 'Many Chapters']);
        $this->createPublishedChapters($manyChapters, 6);

        $results = $this->searchService->advancedSearch([]);
        $ids = collect($results->items())->pluck('id');

        $this->assertSame(2, $results->total());
        $this->assertTrue($ids->contains($noChapters->id));
        $this->assertTrue($ids->contains($manyChapters->id));
    }

    public function test_advanced_search_ignores_zero_or_invalid_min_chapters(): void
    {
        $noChapters = Comic::factory()->create(['title' => 'Empty Catalogue Entry']);
        $manyChapters = Comic::factory()->create(['title' => 'Full Catalogue Entry']);
        $this->createPublishedChapters($manyChapters, 5);

        // The service has no request-level validation for min_chapters; it treats
        // empty, zero and non-positive/non-numeric values as "no filter".
        foreach ([0, '0', -5, '-5', 'abc', ''] as $invalidValue) {
            $results = $this->searchService->advancedSearch(['min_chapters' => $invalidValue]);
            $ids = collect($results->items())->pluck('id');

            $this->assertSame(2, $results->total(), 'min_chapters=' . var_export($invalidValue, true) . ' should not filter results.');
            $this->assertTrue($ids->contains($noChapters->id));
            $this->assertTrue($ids->contains($manyChapters->id));
        }
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

    /**
     * Create real, already-published chapters so the database exercises the
     * published-chapter COUNT used by the min_chapters filter.
     */
    private function createPublishedChapters(Comic $comic, int $count, int $startNumber = 1): void
    {
        Chapter::factory()
            ->for($comic)
            ->count($count)
            ->sequence(fn ($sequence) => [
                'chapter_number' => $startNumber + $sequence->index,
                'published_at'   => now()->subDays($count - $sequence->index)->subMinute(),
            ])
            ->create();
    }

    /**
     * Create chapters scheduled in the future. They must not be counted by
     * min_chapters because they are not publicly readable yet.
     */
    private function createScheduledChapters(Comic $comic, int $count, int $startNumber = 1): void
    {
        Chapter::factory()
            ->for($comic)
            ->count($count)
            ->sequence(fn ($sequence) => [
                'chapter_number' => $startNumber + $sequence->index,
                'published_at'   => now()->addDay(),
            ])
            ->create();
    }
}
