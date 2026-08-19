<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests cho CommentController::store()
 *
 * Covers:
 *  - Fix #1: status được lưu vào DB
 *  - Fix #2: chapter_id scoped theo comic_id
 *  - Fix #3: user bị ban bị từ chối (CommentPolicy)
 *  - Fix #4: parent_id cross-comic bị từ chối
 *  - Unauthenticated → 401/redirect
 */
class CommentStoreTest extends TestCase
{
    use RefreshDatabase;

    private Comic $comic;
    private Chapter $chapter;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->comic = Comic::factory()->create();
        $this->chapter = Chapter::factory()->create([
            'comic_id'       => $this->comic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
        ]);
        $this->user = User::factory()->create();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HAPPY PATH
    // ─────────────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_post_comment(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('comments.store'), [
                'comic_id' => $this->comic->id,
                'content'  => 'Truyện hay quá!',
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('comments', [
            'user_id'  => $this->user->id,
            'comic_id' => $this->comic->id,
            'content'  => 'Truyện hay quá!',
        ]);
    }

    public function test_comment_status_is_persisted_to_database(): void
    {
        // Fix #1: status phải có trong $fillable → được lưu vào DB
        $response = $this->actingAs($this->user)
            ->postJson(route('comments.store'), [
                'comic_id' => $this->comic->id,
                'content'  => 'Good chapter!',
            ]);

        $response->assertStatus(200);

        $comment = Comment::where('user_id', $this->user->id)->first();
        $this->assertNotNull($comment, 'Comment phải được tạo trong DB');
        $this->assertNotNull($comment->status, 'status không được null – fix #1');
        $this->assertContains($comment->status, [
            Comment::STATUS_APPROVED,
            Comment::STATUS_SPAM,
        ]);
    }

    public function test_user_can_reply_to_parent_comment_in_same_comic(): void
    {
        $parent = Comment::factory()->create([
            'comic_id'   => $this->comic->id,
            'chapter_id' => null,
            'user_id'    => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('comments.store'), [
                'comic_id'  => $this->comic->id,
                'parent_id' => $parent->id,
                'content'   => 'Đồng ý với bạn!',
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VALIDATION FAILURES
    // ─────────────────────────────────────────────────────────────────────────

    public function test_content_is_required(): void
    {
        $this->actingAs($this->user)
             ->postJson(route('comments.store'), [
                 'comic_id' => $this->comic->id,
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['content']);
    }

    public function test_chapter_id_must_belong_to_comic(): void
    {
        // Fix #2: chapter của comic khác → 422
        $otherComic   = Comic::factory()->create();
        $otherChapter = Chapter::factory()->create([
            'comic_id'       => $otherComic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
        ]);

        $this->actingAs($this->user)
             ->postJson(route('comments.store'), [
                 'comic_id'   => $this->comic->id,
                 'chapter_id' => $otherChapter->id, // chapter thuộc comic khác
                 'content'    => 'Test',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['chapter_id']);
    }

    public function test_parent_id_must_belong_to_same_comic(): void
    {
        // Fix #4: parent của comic khác → 422
        $otherComic = Comic::factory()->create();
        $parent = Comment::factory()->create([
            'comic_id' => $otherComic->id,
            'user_id'  => $this->user->id,
        ]);

        $this->actingAs($this->user)
             ->postJson(route('comments.store'), [
                 'comic_id'  => $this->comic->id,
                 'parent_id' => $parent->id,
                 'content'   => 'Test cross-comic reply',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_parent_id_must_belong_to_same_chapter(): void
    {
        // Fix #4: parent ở chapter A, reply gửi vào chapter B → 422
        $chapterB = Chapter::factory()->create([
            'comic_id'       => $this->comic->id,
            'chapter_number' => 2,
            'slug'           => 'chapter-2',
        ]);

        $parent = Comment::factory()->create([
            'comic_id'   => $this->comic->id,
            'chapter_id' => $this->chapter->id, // parent ở chapter 1
            'user_id'    => $this->user->id,
        ]);

        $this->actingAs($this->user)
             ->postJson(route('comments.store'), [
                 'comic_id'   => $this->comic->id,
                 'chapter_id' => $chapterB->id, // reply vào chapter 2
                 'parent_id'  => $parent->id,
                 'content'    => 'Test cross-chapter reply',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['parent_id']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AUTHORIZATION
    // ─────────────────────────────────────────────────────────────────────────

    public function test_banned_user_cannot_post_comment(): void
    {
        // Fix #3: CommentPolicy từ chối user bị ban
        $bannedUser = User::factory()->create(['banned_at' => now()]);

        $this->actingAs($bannedUser)
             ->postJson(route('comments.store'), [
                 'comic_id' => $this->comic->id,
                 'content'  => 'Test',
             ])
             ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_post_comment(): void
    {
        $this->postJson(route('comments.store'), [
                 'comic_id' => $this->comic->id,
                 'content'  => 'Test',
             ])
             ->assertStatus(401);
    }
}
