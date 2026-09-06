<?php

namespace Tests\Feature;

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

        $service->forUser($user, 6);
        $this->assertTrue(Cache::has("recommendations.user.{$user->id}.v0.limit_6.ex_all"));
        $this->assertEquals(0, Cache::get("rec_ver.user.{$user->id}", 0));

        $this->actingAs($user)->postJson(route('history.save'), [
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
        ])->assertOk();

        $this->assertEquals(1, Cache::get("rec_ver.user.{$user->id}"));

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->postJson(route('history.save'), [
                'comic_id'   => $comic->id,
                'chapter_id' => $chapter->id,
            ])->assertOk();
        }

        $this->assertEquals(1, Cache::get("rec_ver.user.{$user->id}"), 'Version recommendation không được tăng dồn dập khi user đọc liên tục trong debounce window');

        $service->forUser($user, 6);
        $this->assertTrue(Cache::has("recommendations.user.{$user->id}.v1.limit_6.ex_all"));

        $this->actingAs($user)->postJson(route('history.save'), [
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
        ])->assertOk();

        $this->assertTrue(Cache::has("recommendations.user.{$user->id}.v1.limit_6.ex_all"), 'Cache v1 vẫn còn tồn tại và cho tỉ lệ Cache Hit > 0');
    }
}
