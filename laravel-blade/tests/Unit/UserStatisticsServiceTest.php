<?php

namespace Tests\Unit;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\Genre;
use App\Models\Library;
use App\Models\Rating;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Services\UserStatisticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserStatisticsService $statisticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statisticsService = new UserStatisticsService();
    }

    public function test_get_overview_calculates_all_metrics_accurately(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);

        // Tạo 1 library, 1 history, 1 rating, 1 comment, 1 like
        Library::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'status' => 'reading']);
        ReadingHistory::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'chapter_id' => $chapter->id, 'last_read_at' => now()]);
        Rating::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'score' => 5.0]);
        Comment::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'content' => 'Hay']);
        DB::table('comic_likes')->insert(['user_id' => $user->id, 'comic_id' => $comic->id, 'liked_at' => now()]);

        $overview = $this->statisticsService->getOverview($user);

        $this->assertEquals(1, $overview['total_library_comics']);
        $this->assertEquals(1, $overview['total_chapters_read']);
        $this->assertEquals(1, $overview['total_ratings']);
        $this->assertEquals(1, $overview['total_comments']);
        $this->assertEquals(1, $overview['total_likes']);
        $this->assertEquals(1, $overview['reading_streak_days']);
        $this->assertEquals(1, $overview['reader_tier']['level']);
        $this->assertEquals('Tân Thủ (Bronze)', $overview['reader_tier']['name']);
    }

    public function test_calculate_streak_days_continuous(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chap1 = Chapter::factory()->create(['comic_id' => $comic->id]);
        $chap2 = Chapter::factory()->create(['comic_id' => $comic->id]);
        $chap3 = Chapter::factory()->create(['comic_id' => $comic->id]);

        // Đọc liên tục 3 ngày: hôm nay, hôm qua, hôm kia
        ReadingHistory::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'chapter_id' => $chap1->id, 'last_read_at' => Carbon::today()]);
        ReadingHistory::create(['user_id' => $user->id, 'comic_id' => Comic::factory()->create()->id, 'chapter_id' => $chap2->id, 'last_read_at' => Carbon::yesterday()]);
        ReadingHistory::create(['user_id' => $user->id, 'comic_id' => Comic::factory()->create()->id, 'chapter_id' => $chap3->id, 'last_read_at' => Carbon::today()->subDays(2)]);

        $streak = $this->statisticsService->calculateStreakDays($user);
        $this->assertEquals(3, $streak);
    }

    public function test_calculate_streak_days_broken_when_day_missed(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);

        // Đọc 4 ngày trước -> chuỗi = 0
        ReadingHistory::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'chapter_id' => $chapter->id, 'last_read_at' => Carbon::today()->subDays(4)]);

        $streak = $this->statisticsService->calculateStreakDays($user);
        $this->assertEquals(0, $streak);
    }

    public function test_calculate_reader_tier_levels(): void
    {
        $tier0 = $this->statisticsService->calculateReaderTier(5);
        $this->assertEquals(1, $tier0['level']);
        $this->assertEquals('Tân Thủ (Bronze)', $tier0['name']);

        $tier1 = $this->statisticsService->calculateReaderTier(25);
        $this->assertEquals(2, $tier1['level']);
        $this->assertEquals('Mọt Sách (Silver)', $tier1['name']);

        $tier2 = $this->statisticsService->calculateReaderTier(75);
        $this->assertEquals(3, $tier2['level']);
        $this->assertEquals('Cao Thủ (Gold)', $tier2['name']);

        $tier3 = $this->statisticsService->calculateReaderTier(150);
        $this->assertEquals(4, $tier3['level']);
        $this->assertEquals('Đại Tông Sư (Diamond)', $tier3['name']);

        $tier4 = $this->statisticsService->calculateReaderTier(250);
        $this->assertEquals(5, $tier4['level']);
        $this->assertEquals('Thần Thoại (Mythic)', $tier4['name']);
    }

    public function test_get_favorite_genres_breakdown(): void
    {
        $user = User::factory()->create();
        $genreA = Genre::create(['name' => 'Action', 'slug' => 'action']);
        $genreB = Genre::create(['name' => 'Romance', 'slug' => 'romance']);

        $comic1 = Comic::factory()->create();
        $comic1->genres()->attach([$genreA->id]);

        $comic2 = Comic::factory()->create();
        $comic2->genres()->attach([$genreA->id, $genreB->id]);

        $chapter1 = Chapter::factory()->create(['comic_id' => $comic1->id]);
        $chapter2 = Chapter::factory()->create(['comic_id' => $comic2->id]);

        ReadingHistory::create(['user_id' => $user->id, 'comic_id' => $comic1->id, 'chapter_id' => $chapter1->id, 'last_read_at' => now()]);
        ReadingHistory::create(['user_id' => $user->id, 'comic_id' => $comic2->id, 'chapter_id' => $chapter2->id, 'last_read_at' => now()]);

        $genres = $this->statisticsService->getFavoriteGenres($user);

        $this->assertCount(2, $genres);
        $this->assertEquals('Action', $genres[0]['genre']);
        $this->assertEquals(2, $genres[0]['count']);
        $this->assertEquals(66.7, $genres[0]['percentage']);
        $this->assertEquals('Romance', $genres[1]['genre']);
        $this->assertEquals(1, $genres[1]['count']);
        $this->assertEquals(33.3, $genres[1]['percentage']);
    }

    public function test_badges_unlocking_logic(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);

        // Đọc 1 chương
        ReadingHistory::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'chapter_id' => $chapter->id, 'last_read_at' => now()]);

        $badges = $this->statisticsService->getBadges($user);

        $firstStepBadge = collect($badges)->firstWhere('id', 'first_step');
        $bookwormBadge  = collect($badges)->firstWhere('id', 'bookworm');

        $this->assertTrue($firstStepBadge['is_unlocked']);
        $this->assertFalse($bookwormBadge['is_unlocked']);
    }

    public function test_get_weekly_activity_returns_seven_days(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);

        ReadingHistory::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'chapter_id' => $chapter->id, 'last_read_at' => Carbon::today()]);

        $weekly = $this->statisticsService->getWeeklyActivity($user);

        $this->assertCount(7, $weekly);
        $todayEntry = collect($weekly)->firstWhere('date', Carbon::today()->toDateString());
        $this->assertNotNull($todayEntry);
        $this->assertEquals(1, $todayEntry['count']);
    }

    public function test_export_user_reading_data(): void
    {
        $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $comic = Comic::factory()->create(['title' => 'Legendary Hero', 'slug' => 'legendary-hero']);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'chapter_number' => 12]);

        Library::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'status' => 'reading']);
        ReadingHistory::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'chapter_id' => $chapter->id, 'last_read_at' => now(), 'scroll_percent' => 85.5]);

        $exported = $this->statisticsService->exportUserData($user);

        $this->assertEquals('John Doe', $exported['user']['name']);
        $this->assertCount(1, $exported['library']);
        $this->assertEquals('Legendary Hero', $exported['library'][0]['title']);
        $this->assertCount(1, $exported['reading_history']);
        $this->assertEquals(12, $exported['reading_history'][0]['chapter']);
        $this->assertEquals(85.5, $exported['reading_history'][0]['scroll_percent']);
    }
}
