<?php

namespace Tests\Feature;

use App\Jobs\InvalidateUserRecommendation;
use App\Models\Comic;
use App\Models\Chapter;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ReadingHistoryDebounceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_reading_history_saves_with_debounced_cache_invalidation(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'     => $comic->id,
            'published_at' => now()->subDay(),
        ]);

        $service = app(RecommendationService::class);

        // 1. Initial recommendation fetch (caches version 0)
        $service->forUser($user, 6);
        $this->assertTrue(Cache::has("recommendations.user.{$user->id}.v0.limit_6"));
        $this->assertEquals(0, Cache::get("rec_ver.user.{$user->id}", 0));

        // 2. Request save history lần 1 → Version tăng lên 1
        $this->actingAs($user)->postJson(route('history.save'), [
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
        ])->assertOk();

        $this->assertEquals(1, Cache::get("rec_ver.user.{$user->id}"));

        // 3. User tiếp tục cuộn/gửi heartbeat save history lần 2, lần 3, lần 4 trong vòng debounce (60s)
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->postJson(route('history.save'), [
                'comic_id'   => $comic->id,
                'chapter_id' => $chapter->id,
            ])->assertOk();
        }

        // Version KHÔNG bị tăng liên tục (vẫn giữ là 1 nhờ debounce), bảo vệ Cache Hit Rate
        $this->assertEquals(1, Cache::get("rec_ver.user.{$user->id}"), 'Version recommendation không được tăng dồn dập khi user đọc liên tục trong debounce window');

        // 4. Lấy recommendation lại sau khi version = 1 → Cache key v1 được tạo
        $service->forUser($user, 6);
        $this->assertTrue(Cache::has("recommendations.user.{$user->id}.v1.limit_6"));

        // Các request đọc tiếp theo trong debounce window không làm mất cache key v1
        $this->actingAs($user)->postJson(route('history.save'), [
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
        ])->assertOk();

        $this->assertTrue(Cache::has("recommendations.user.{$user->id}.v1.limit_6"), 'Cache v1 vẫn còn tồn tại và cho tỉ lệ Cache Hit > 0');
    }
}
