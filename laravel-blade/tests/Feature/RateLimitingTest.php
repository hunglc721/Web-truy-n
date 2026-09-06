<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

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

        RateLimiter::clear('comments|' . $this->user->id);
        RateLimiter::clear('library-toggle|' . $this->user->id);
        RateLimiter::clear('like-toggle|' . $this->user->id);
        RateLimiter::clear('history-save|' . $this->user->id);
    }

    public function test_comment_rate_limit_allows_5_per_minute(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson(route('comments.store'), [
                    'comic_id' => $this->comic->id,
                    'content'  => "Bình luận số {$i}",
                ]);

            $this->assertNotEquals(429, $response->status(), "Request #{$i} không được bị rate limit");
        }
    }

    public function test_comment_rate_limit_blocks_6th_request(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($this->user)
                ->postJson(route('comments.store'), [
                    'comic_id' => $this->comic->id,
                    'content'  => "Bình luận số {$i}",
                ]);
        }

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

        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($this->user)
                ->postJson(route('comments.store'), [
                    'comic_id' => $this->comic->id,
                    'content'  => "User1 bình luận {$i}",
                ]);
        }

        $response = $this->actingAs($otherUser)
            ->postJson(route('comments.store'), [
                'comic_id' => $this->comic->id,
                'content'  => 'User2 bình luận 1',
            ]);

        $this->assertNotEquals(429, $response->status(), 'Rate limit phải per-user, không phải global');
    }

    public function test_library_toggle_rate_limit_blocks_after_30(): void
    {
        $comics = Comic::factory(30)->create();

        foreach ($comics as $comic) {
            $this->actingAs($this->user)
                ->postJson(route('comics.toggleLibrary', ['comic' => $comic->id]));
        }

        $extraComic = Comic::factory()->create();
        $response = $this->actingAs($this->user)
            ->postJson(route('comics.toggleLibrary', ['comic' => $extraComic->id]));

        $response->assertStatus(429);
    }

    public function test_unauthenticated_comment_returns_401_not_429(): void
    {
        $response = $this->postJson(route('comments.store'), [
            'comic_id' => $this->comic->id,
            'content'  => 'Test',
        ]);

        $response->assertStatus(401);
    }
}
