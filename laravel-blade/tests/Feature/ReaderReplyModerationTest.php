<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderReplyModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_only_shows_approved_replies(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
            'published_at'   => now()->subDay(),
        ]);

        // Comment cha đã duyệt.
        $parent = Comment::create([
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
            'content'    => 'Bình luận cha hợp lệ',
            'status'     => Comment::STATUS_APPROVED,
        ]);

        // Reply hợp lệ — phải hiển thị.
        Comment::create([
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
            'parent_id'  => $parent->id,
            'content'    => 'Reply đã được duyệt',
            'status'     => Comment::STATUS_APPROVED,
        ]);

        // Reply bị đánh spam (chứa link/phone) — KHÔNG được hiển thị công khai.
        Comment::create([
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
            'parent_id'  => $parent->id,
            'content'    => 'Reply spam chứa link zalo 0901234567',
            'status'     => Comment::STATUS_SPAM,
        ]);

        // Reply đang chờ duyệt — KHÔNG được hiển thị công khai.
        Comment::create([
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
            'parent_id'  => $parent->id,
            'content'    => 'Reply đang chờ duyệt',
            'status'     => Comment::STATUS_PENDING,
        ]);

        $response = $this->get(route('chapters.show', [$comic->slug, $chapter->slug]));
        $response->assertOk();

        $response->assertSee('Reply đã được duyệt');
        $response->assertDontSee('Reply spam chứa link zalo 0901234567');
        $response->assertDontSee('Reply đang chờ duyệt');
    }

    public function test_reader_still_shows_approved_reply_when_sibling_is_spam(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
            'published_at'   => now()->subDay(),
        ]);

        $parent = Comment::create([
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
            'content'    => 'Bình luận cha',
            'status'     => Comment::STATUS_APPROVED,
        ]);

        Comment::create([
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
            'parent_id'  => $parent->id,
            'content'    => 'Reply spam',
            'status'     => Comment::STATUS_SPAM,
        ]);

        Comment::create([
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
            'parent_id'  => $parent->id,
            'content'    => 'Reply hợp lệ vẫn hiển thị',
            'status'     => Comment::STATUS_APPROVED,
        ]);

        $response = $this->get(route('chapters.show', [$comic->slug, $chapter->slug]));
        $response->assertOk();

        $response->assertSee('Reply hợp lệ vẫn hiển thị');
        $response->assertDontSee('Reply spam');
    }
}
