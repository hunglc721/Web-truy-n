<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommentSeparationAndNPlusOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_comic_level_comments_do_not_appear_in_chapter_reader(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        $chapter1 = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
            'published_at'   => now()->subDay(),
        ]);

        $chapter2 = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 2,
            'slug'           => 'chapter-2',
            'published_at'   => now()->subDay(),
        ]);

        // 1. Comment cấp truyện (chapter_id = null)
        $comicComment = Comment::create([
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => null,
            'content'    => 'Bình luận cấp truyện tổng quan',
            'status'     => Comment::STATUS_APPROVED,
        ]);

        // 2. Comment của Chapter 1
        $chap1Comment = Comment::create([
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter1->id,
            'content'    => 'Bình luận riêng của Chapter 1',
            'status'     => Comment::STATUS_APPROVED,
        ]);

        // 3. Comment của Chapter 2
        $chap2Comment = Comment::create([
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter2->id,
            'content'    => 'Bình luận riêng của Chapter 2',
            'status'     => Comment::STATUS_APPROVED,
        ]);

        // Truy cập Reader Chapter 1
        $responseChap1 = $this->get(route('chapters.show', [$comic->slug, $chapter1->slug]));
        $responseChap1->assertOk();
        $responseChap1->assertSee('Bình luận riêng của Chapter 1');
        $responseChap1->assertDontSee('Bình luận cấp truyện tổng quan');
        $responseChap1->assertDontSee('Bình luận riêng của Chapter 2');

        // Truy cập Reader Chapter 2
        $responseChap2 = $this->get(route('chapters.show', [$comic->slug, $chapter2->slug]));
        $responseChap2->assertOk();
        $responseChap2->assertSee('Bình luận riêng của Chapter 2');
        $responseChap2->assertDontSee('Bình luận cấp truyện tổng quan');
        $responseChap2->assertDontSee('Bình luận riêng của Chapter 1');
    }

    public function test_api_comments_separates_comic_and_chapter_flows(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'     => $comic->id,
            'published_at' => now()->subDay(),
        ]);

        Comment::create([
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => null,
            'content'    => 'Bình luận cấp truyện qua API',
            'status'     => Comment::STATUS_APPROVED,
        ]);

        Comment::create([
            'user_id'    => $user->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
            'content'    => 'Bình luận chapter qua API',
            'status'     => Comment::STATUS_APPROVED,
        ]);

        // Luồng 1: Lấy comment cấp truyện (không truyền chapter_id)
        $comicRes = $this->getJson(route('comments.index', ['comic_id' => $comic->id]));
        $comicRes->assertOk();
        $comicComments = $comicRes->json('comments.data');
        $this->assertCount(1, $comicComments);
        $this->assertEquals('Bình luận cấp truyện qua API', $comicComments[0]['content']);
        $this->assertNull($comicComments[0]['chapter_id']);

        // Luồng 2: Lấy comment cấp chapter
        $chapRes = $this->getJson(route('comments.index', [
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
        ]));
        $chapRes->assertOk();
        $chapComments = $chapRes->json('comments.data');
        $this->assertCount(1, $chapComments);
        $this->assertEquals('Bình luận chapter qua API', $chapComments[0]['content']);
        $this->assertEquals($chapter->id, $chapComments[0]['chapter_id']);
    }

    public function test_no_n_plus_one_queries_when_loading_comments_with_replies(): void
    {
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'     => $comic->id,
            'published_at' => now()->subDay(),
        ]);

        // Tạo 20 top-level comments, mỗi comment có 2 replies từ các user khác nhau
        for ($i = 1; $i <= 20; $i++) {
            $author = User::factory()->create();
            $parent = Comment::create([
                'user_id'    => $author->id,
                'comic_id'   => $comic->id,
                'chapter_id' => $chapter->id,
                'content'    => "Comment chính số {$i}",
                'status'     => Comment::STATUS_APPROVED,
            ]);

            for ($j = 1; $j <= 2; $j++) {
                $replier = User::factory()->create();
                Comment::create([
                    'user_id'    => $replier->id,
                    'comic_id'   => $comic->id,
                    'chapter_id' => $chapter->id,
                    'parent_id'  => $parent->id,
                    'content'    => "Reply {$j} cho comment {$i}",
                    'status'     => Comment::STATUS_APPROVED,
                ]);
            }
        }

        // Đo số câu truy vấn khi render 20 comments + 40 replies
        DB::flushQueryLog();
        DB::enableQueryLog();

        $comments = Comment::with(['user', 'replies.user'])
            ->where('comic_id', $comic->id)
            ->where('chapter_id', $chapter->id)
            ->whereNull('parent_id')
            ->approved()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Duyệt qua tất cả comment, user, reply, reply user để kích hoạt quan hệ
        $totalRenderedUsers = 0;
        foreach ($comments as $comment) {
            $totalRenderedUsers += $comment->user ? 1 : 0;
            foreach ($comment->replies as $reply) {
                $totalRenderedUsers += $reply->user ? 1 : 0;
            }
        }

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // 1 count query + 1 comments query + 1 users query + 1 replies query + 1 reply users query = tổng cộng tối đa 5 queries
        // Nếu bị N+1 sẽ lên đến > 60 queries (20 comment user + 20 replies + 40 reply users)
        $this->assertLessThanOrEqual(5, count($queries), "Số câu query phải là O(1) không vượt quá 5, thực tế: " . count($queries));
        $this->assertEquals(60, $totalRenderedUsers); // 20 comment authors + 40 reply authors
    }
}
