<?php

namespace Tests\Feature;

use App\Jobs\FlushViewCounters;
use App\Models\Comic;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FlushViewCountersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_f5_20_times_only_increments_cache_counter_by_one(): void
    {
        $comic = Comic::factory()->create(['views' => 0]);
        $chapter = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
            'views'          => 0,
            'published_at'   => now()->subDay(),
        ]);

        $user = User::factory()->create(['is_admin' => false]);

        // F5 liên tục 20 lần trong cùng 1 phiên
        for ($i = 0; $i < 20; $i++) {
            $response = $this->actingAs($user)->get(route('chapters.show', [$comic->slug, $chapter->slug]));
            $response->assertOk();

            // Session KHÔNG còn lưu bất kỳ key nào liên quan đến chapter (tránh phình session)
            $response->assertSessionMissing("viewed_chapter_{$chapter->id}");
        }

        // Kiểm tra counter trong cache buffer chỉ tăng đúng 1
        $chapterPending = (int) Cache::get(FlushViewCounters::BUFFER_CHAPTER_PREFIX . $chapter->id, 0);
        $comicPending   = (int) Cache::get(FlushViewCounters::BUFFER_COMIC_PREFIX . $comic->id, 0);

        $this->assertEquals(1, $chapterPending, 'Counter chapter trong cache chỉ được tăng đúng 1 lần sau 20 lần F5');
        $this->assertEquals(1, $comicPending, 'Counter comic trong cache chỉ được tăng đúng 1 lần sau 20 lần F5');

        // DB lúc này chưa bị tăng ngay (được gom theo batch)
        $this->assertEquals(0, $chapter->fresh()->views);
        $this->assertEquals(0, $comic->fresh()->views);

        // Chạy job FlushViewCounters (giả lập sau 5 phút scheduler kích hoạt)
        (new FlushViewCounters())->handle();

        // Sau khi flush, số view trong DB khớp chính xác
        $this->assertEquals(1, $chapter->fresh()->views);
        $this->assertEquals(1, $comic->fresh()->views);

        // Cache buffer đã được dọn sạch
        $this->assertNull(Cache::get(FlushViewCounters::BUFFER_CHAPTER_PREFIX . $chapter->id));
        $this->assertNull(Cache::get(FlushViewCounters::BUFFER_COMIC_PREFIX . $comic->id));
    }

    public function test_flush_view_counters_job_updates_multiple_records_in_single_batch(): void
    {
        $comic1 = Comic::factory()->create(['views' => 100]);
        $comic2 = Comic::factory()->create(['views' => 200]);

        $chap1 = Chapter::factory()->create(['comic_id' => $comic1->id, 'views' => 10]);
        $chap2 = Chapter::factory()->create(['comic_id' => $comic1->id, 'views' => 20]);
        $chap3 = Chapter::factory()->create(['comic_id' => $comic2->id, 'views' => 30]);

        // Giả lập nhiều người đọc cộng dồn vào cache buffer
        for ($i = 0; $i < 7; $i++) {
            FlushViewCounters::recordView($comic1->id, $chap1->id);
        }
        for ($i = 0; $i < 3; $i++) {
            FlushViewCounters::recordView($comic1->id, $chap2->id);
        }
        for ($i = 0; $i < 5; $i++) {
            FlushViewCounters::recordView($comic2->id, $chap3->id);
        }

        // Đo số câu lệnh query khi job thực thi
        DB::flushQueryLog();
        DB::enableQueryLog();

        $job = new FlushViewCounters();
        $job->handle();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // 1 UPDATE cho bảng chapters + 1 UPDATE cho bảng comics = đúng 2 query update gộp
        $updateQueries = array_filter($queries, fn($q) => str_starts_with(strtoupper($q['query']), 'UPDATE'));
        $this->assertCount(2, $updateQueries, 'Phải thực thi đúng 1 query UPDATE gộp cho chapters và 1 query cho comics');

        // Kiểm tra kết quả trong DB đã cộng dồn chính xác
        $this->assertEquals(17, $chap1->fresh()->views); // 10 + 7
        $this->assertEquals(23, $chap2->fresh()->views); // 20 + 3
        $this->assertEquals(110, $comic1->fresh()->views); // 100 + 7 + 3

        $this->assertEquals(35, $chap3->fresh()->views); // 30 + 5
        $this->assertEquals(205, $comic2->fresh()->views); // 200 + 5
    }

    public function test_artisan_command_views_flush(): void
    {
        $comic = Comic::factory()->create(['views' => 0]);
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id, 'views' => 0]);

        FlushViewCounters::recordView($comic->id, $chapter->id);
        FlushViewCounters::recordView($comic->id, $chapter->id);

        $this->artisan('views:flush')
            ->expectsOutputToContain('Đã flush view counters xuống database')
            ->assertExitCode(0);

        $this->assertEquals(2, $chapter->fresh()->views);
        $this->assertEquals(2, $comic->fresh()->views);
    }
}
