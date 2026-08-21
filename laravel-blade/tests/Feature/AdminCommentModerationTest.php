<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommentModerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $member;
    protected Comic $comic;
    protected Chapter $chapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->member = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->comic = Comic::factory()->create();
        $this->chapter = Chapter::factory()->create([
            'comic_id'     => $this->comic->id,
            'published_at' => now()->subDay(),
        ]);
    }

    public function test_admin_can_view_comments_index_with_stats_and_filters(): void
    {
        Comment::factory()->create([
            'comic_id' => $this->comic->id,
            'user_id'  => $this->member->id,
            'status'   => Comment::STATUS_APPROVED,
        ]);

        Comment::factory()->create([
            'comic_id' => $this->comic->id,
            'user_id'  => $this->member->id,
            'status'   => Comment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.comments.index'));
        $response->assertOk();
        $response->assertViewHas('comments');
        $response->assertViewHas('stats');
        $response->assertSee('Quản lý & Kiểm duyệt Bình luận', false);
    }

    public function test_admin_can_approve_comment_and_it_reflects_on_public_api(): void
    {
        $comment = Comment::factory()->create([
            'comic_id'   => $this->comic->id,
            'chapter_id' => $this->chapter->id,
            'user_id'    => $this->member->id,
            'status'     => Comment::STATUS_PENDING,
            'content'    => 'Bình luận đang chờ duyệt',
        ]);

        // 1. Phía public chưa thấy bình luận pending
        $publicRes = $this->getJson(route('comments.index', [
            'comic_id'   => $this->comic->id,
            'chapter_id' => $this->chapter->id,
        ]));
        $publicRes->assertOk()->assertJsonMissing(['content' => 'Bình luận đang chờ duyệt']);

        // 2. Admin duyệt bình luận
        $response = $this->actingAs($this->admin)->patch(route('admin.comments.approve', $comment));
        $response->assertRedirect();

        $comment->refresh();
        $this->assertEquals(Comment::STATUS_APPROVED, $comment->status);

        // 3. Phía public lập tức thấy bình luận đã duyệt
        $publicRes = $this->getJson(route('comments.index', [
            'comic_id'   => $this->comic->id,
            'chapter_id' => $this->chapter->id,
        ]));
        $publicRes->assertOk()->assertJsonFragment(['content' => 'Bình luận đang chờ duyệt']);
    }

    public function test_admin_can_hide_comment(): void
    {
        $comment = Comment::factory()->create([
            'comic_id'   => $this->comic->id,
            'chapter_id' => $this->chapter->id,
            'user_id'    => $this->member->id,
            'status'     => Comment::STATUS_APPROVED,
            'content'    => 'Bình luận nhạy cảm cần ẩn',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.comments.hide', $comment));
        $response->assertRedirect();

        $comment->refresh();
        $this->assertEquals(Comment::STATUS_HIDDEN, $comment->status);

        // Public không còn thấy
        $publicRes = $this->getJson(route('comments.index', [
            'comic_id'   => $this->comic->id,
            'chapter_id' => $this->chapter->id,
        ]));
        $publicRes->assertJsonMissing(['content' => 'Bình luận nhạy cảm cần ẩn']);
    }

    public function test_admin_can_soft_delete_and_restore_comment(): void
    {
        $comment = Comment::factory()->create([
            'comic_id' => $this->comic->id,
            'user_id'  => $this->member->id,
            'status'   => Comment::STATUS_APPROVED,
        ]);

        // 1. Xóa mềm
        $response = $this->actingAs($this->admin)->delete(route('admin.comments.destroy', $comment));
        $response->assertRedirect();

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);

        // 2. Khôi phục từ thùng rác
        $restoreRes = $this->actingAs($this->admin)->post(route('admin.comments.restore', $comment->id));
        $restoreRes->assertRedirect();

        $this->assertNotSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_admin_can_quick_ban_violating_comment_author(): void
    {
        $violator = User::factory()->create(['is_admin' => false, 'banned_at' => null]);
        $comment = Comment::factory()->create([
            'comic_id' => $this->comic->id,
            'user_id'  => $violator->id,
            'status'   => Comment::STATUS_APPROVED,
            'content'  => 'Link độc hại lừa đảo',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.comments.banUser', $comment));
        $response->assertRedirect();

        $violator->refresh();
        $comment->refresh();

        $this->assertNotNull($violator->banned_at, 'Tác giả phải bị khóa');
        $this->assertEquals(Comment::STATUS_HIDDEN, $comment->status, 'Bình luận vi phạm phải bị ẩn');
    }
}
