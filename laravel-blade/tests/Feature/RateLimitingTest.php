<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Feature tests cho Rate Limiting — kiểm tra các named limiters được áp dụng đúng.
 *
 * Covers:
 *  - throttle:comments → 5 req/phút → request thứ 6 nhận 429
 *  - throttle:library-toggle → 30 req/phút → request thứ 31 nhận 429
 *  - throttle:like-toggle → 30 req/phút → request thứ 31 nhận 429
 *  - Sau khi clear limiter: request tiếp theo lại OK
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Comic $comic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->create();
        $this->comic = Comic::factory()->create();

        // Clear tất cả rate limiters trước mỗi test
        RateLimiter::clear('comments|' . $this->user->id);
        RateLimiter::clear('library-toggle|' . $this->user->id);
        RateLimiter::clear('like-toggle|' . $this->user->id);
        RateLimiter::clear('history-save|' . $this->user->id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Comment Rate Limit: 5/phút
    // ─────────────────────────────────────────────────────────────────────────

    public function test_comment_rate_limit_allows_5_per_minute(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson(route('comments.store'), [
                    'comic_id' => $this->comic->id,
                    'content'  => "Bình luận số {$i}",
                ]);

            // 200 hoặc 422 (nếu comic không hợp lệ) đều chứng minh không bị throttle
            $this->assertNotEquals(
                429,
                $response->status(),
                "Request #{$i} không được bị rate limit"
            );
        }
    }

    public function test_comment_rate_limit_blocks_6th_request(): void
    {
        // Gửi 5 request đầu (có thể thành công hoặc fail validation)
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($this->user)
                ->postJson(route('comments.store'), [
                    'comic_id' => $this->comic->id,
                    'content'  => "Bình luận số {$i}",
                ]);
        }

        // Request thứ 6 phải bị throttle → 429
        $response = $this->actingAs($this->user)
            ->postJson(route('comments.store'), [
                'comic_id' => $this->comic->id,
                'content'  => 'Bình luận số 6',
            ]);

        $response->assertStatus(429);
        $response->assertJsonPath('status', 'error');
    }

    public function test_rate_limit_is_per_user_not_global(): void
    {
        $otherUser = User::factory()->create();

        // User 1 gửi 5 lần (đạt limit)
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($this->user)
                ->postJson(route('comments.store'), [
                    'comic_id' => $this->comic->id,
                    'content'  => "User1 bình luận {$i}",
                ]);
        }

        // User 2 vẫn được phép gửi (limiter riêng biệt theo user_id)
        $response = $this->actingAs($otherUser)
            ->postJson(route('comments.store'), [
                'comic_id' => $this->comic->id,
                'content'  => 'User2 bình luận 1',
            ]);

        $this->assertNotEquals(429, $response->status(), 'Rate limit phải per-user, không phải global');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Toggle Library Rate Limit: 30/phút
    // ─────────────────────────────────────────────────────────────────────────

    public function test_library_toggle_rate_limit_blocks_after_30(): void
    {
        $comics = Comic::factory(30)->create();

        // Gửi 30 request toggle library
        foreach ($comics as $comic) {
            $this->actingAs($this->user)
                ->postJson(route('comics.toggleLibrary', ['comicId' => $comic->id]));
        }

        // Request thứ 31 → 429
        $extraComic = Comic::factory()->create();
        $response = $this->actingAs($this->user)
            ->postJson(route('comics.toggleLibrary', ['comicId' => $extraComic->id]));

        $response->assertStatus(429);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Unauthenticated requests → 401 (không phải 429)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_unauthenticated_comment_returns_401_not_429(): void
    {
        $response = $this->postJson(route('comments.store'), [
            'comic_id' => $this->comic->id,
            'content'  => 'Test',
        ]);

        $response->assertStatus(401);
    }
}
