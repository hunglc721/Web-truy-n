<?php

namespace Tests\Unit;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Library;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Services\LibraryService;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryServiceTest extends TestCase
{
    use RefreshDatabase;

    private LibraryService $libraryService;

    protected function setUp(): void
    {
        parent::setUp();
        $recommendationService = new RecommendationService();
        $this->libraryService = new LibraryService($recommendationService);
    }

    public function test_toggle_adds_comic_to_library_when_not_present(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create(['title' => 'Test Solo Leveling']);

        $result = $this->libraryService->toggle($user, $comic);

        $this->assertTrue($result['is_followed']);
        $this->assertEquals(1, $result['total_followers']);
        $this->assertDatabaseHas('libraries', [
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
        ]);
    }

    public function test_toggle_removes_comic_from_library_when_already_present(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create(['title' => 'Test Tower of God']);

        Library::create([
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
            'status'   => 'reading',
        ]);

        $result = $this->libraryService->toggle($user, $comic);

        $this->assertFalse($result['is_followed']);
        $this->assertEquals(0, $result['total_followers']);
        $this->assertDatabaseMissing('libraries', [
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
        ]);
    }

    public function test_record_reading_creates_and_updates_reading_history(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter1 = Chapter::factory()->create(['comic_id' => $comic->id, 'chapter_number' => 1]);
        $chapter2 = Chapter::factory()->create(['comic_id' => $comic->id, 'chapter_number' => 2]);

        $this->libraryService->recordReading($user, $comic, $chapter1);
        $this->assertDatabaseHas('reading_histories', [
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter1->id,
        ]);

        $this->libraryService->recordReading($user, $comic, $chapter2);
        $this->assertEquals(1, ReadingHistory::where('user_id', $user->id)->where('comic_id', $comic->id)->count());
        $this->assertDatabaseHas('reading_histories', [
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter2->id,
        ]);
    }

    public function test_record_reading_syncs_last_read_chapter_in_library_if_bookmarked(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'chapter_number' => 10]);

        $library = Library::create([
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
            'status'   => 'reading',
        ]);

        $this->libraryService->recordReading($user, $comic, $chapter);

        $library->refresh();
        $this->assertEquals($chapter->id, $library->last_read_chapter_id);
    }

    public function test_last_read_chapter_does_not_regress_when_user_reopens_old_chapter(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $older = Chapter::factory()->create(['comic_id' => $comic->id, 'chapter_number' => 3]);
        $newer = Chapter::factory()->create(['comic_id' => $comic->id, 'chapter_number' => 8]);

        $library = Library::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'last_read_chapter_id' => $newer->id,
            'status' => 'reading',
        ]);

        $this->libraryService->recordReading($user, $comic, $older);

        $this->assertEquals($newer->id, $library->fresh()->last_read_chapter_id);
    }

    public function test_user_library_counts_only_published_unread_chapters_and_exposes_next_unread(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter1 = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'published_at' => now()->subDays(3),
        ]);
        $chapter2 = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 2,
            'published_at' => now()->subDays(2),
        ]);
        $chapter3 = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 3,
            'published_at' => now()->subDay(),
        ]);
        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 4,
            'published_at' => now()->addDay(),
        ]);

        Library::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'last_read_chapter_id' => $chapter1->id,
            'status' => 'reading',
        ]);

        $item = $this->libraryService->getUserLibrary($user, 12)->first();

        $this->assertSame(2, (int) $item->unread_chapters_count);
        $this->assertNotNull($item->nextUnreadChapter);
        $this->assertEquals($chapter2->id, $item->nextUnreadChapter->id);
        $this->assertNotEquals($chapter3->id, $item->nextUnreadChapter->id);
    }

    public function test_get_user_reading_stats_calculates_top_genres_and_counts(): void
    {
        $user = User::factory()->create();
        $action = Genre::create(['name' => 'Action', 'slug' => 'action']);
        $fantasy = Genre::create(['name' => 'Fantasy', 'slug' => 'fantasy']);

        $comic1 = Comic::factory()->create();
        $comic1->genres()->attach([$action->id, $fantasy->id]);

        $comic2 = Comic::factory()->create();
        $comic2->genres()->attach([$action->id]);

        Library::create(['user_id' => $user->id, 'comic_id' => $comic1->id, 'status' => 'reading']);

        $chap1 = Chapter::factory()->create(['comic_id' => $comic1->id]);
        $chap2 = Chapter::factory()->create(['comic_id' => $comic2->id]);
        $this->libraryService->recordReading($user, $comic1, $chap1);
        $this->libraryService->recordReading($user, $comic2, $chap2);

        $stats = $this->libraryService->getUserReadingStats($user);

        $this->assertEquals(1, $stats['total_bookmarks']);
        $this->assertEquals(2, $stats['total_read_comics']);
        $this->assertContains('Action', $stats['top_genres']);
    }

    public function test_clear_user_history_removes_records_and_resets_library_progress(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);
        $library = Library::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'last_read_chapter_id' => $chapter->id,
            'status' => 'reading',
        ]);

        $this->libraryService->recordReading($user, $comic, $chapter);
        $this->assertEquals(1, ReadingHistory::where('user_id', $user->id)->count());

        $this->libraryService->clearUserHistory($user);

        $this->assertEquals(0, ReadingHistory::where('user_id', $user->id)->count());
        $this->assertNull($library->fresh()->last_read_chapter_id);
    }
}
