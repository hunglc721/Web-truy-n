<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use App\Models\Comic;
use App\Policies\CommentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests cho CommentPolicy — kiểm tra trực tiếp từng method.
 *
 * Covers:
 *  - create(): user thường OK | user bị ban → denied
 *  - update(): chủ BL trong 15p → OK | quá 15p → denied | user khác → denied | admin → OK
 *  - delete(): chủ BL → OK | user khác → denied | admin → OK
 *  - restore(): chỉ admin → OK | user thường → denied
 */
class CommentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CommentPolicy $policy;
    private User $user;
    private User $admin;
    private User $otherUser;
    private User $bannedUser;
    private Comic $comic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy    = new CommentPolicy();
        $this->user      = User::factory()->create();
        $this->admin     = User::factory()->admin()->create();
        $this->otherUser = User::factory()->create();
        $this->bannedUser = User::factory()->banned()->create();
        $this->comic     = Comic::factory()->create();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // create()
    // ─────────────────────────────────────────────────────────────────────────

    public function test_normal_user_can_create_comment(): void
    {
        $response = $this->policy->create($this->user);
        $this->assertTrue($response->allowed());
    }

    public function test_banned_user_cannot_create_comment(): void
    {
        $response = $this->policy->create($this->bannedUser);
        $this->assertTrue($response->denied());
        $this->assertSame(403, $response->status());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // update()
    // ─────────────────────────────────────────────────────────────────────────

    public function test_owner_can_update_comment_within_15_minutes(): void
    {
        $comment = Comment::factory()->create([
            'user_id'    => $this->user->id,
            'comic_id'   => $this->comic->id,
            'created_at' => now()->subMinutes(5), // 5 phút trước — còn trong window
        ]);

        $response = $this->policy->update($this->user, $comment);
        $this->assertTrue($response->allowed());
    }

    public function test_owner_cannot_update_comment_after_15_minutes(): void
    {
        $comment = Comment::factory()->create([
            'user_id'    => $this->user->id,
            'comic_id'   => $this->comic->id,
            'created_at' => now()->subMinutes(20), // 20 phút trước — quá window
        ]);

        $response = $this->policy->update($this->user, $comment);
        $this->assertTrue($response->denied());
        $this->assertSame(403, $response->status());
    }

    public function test_non_owner_cannot_update_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id'    => $this->user->id,
            'comic_id'   => $this->comic->id,
            'created_at' => now()->subMinutes(1),
        ]);

        $response = $this->policy->update($this->otherUser, $comment);
        $this->assertTrue($response->denied());
    }

    public function test_admin_can_update_any_comment_anytime(): void
    {
        $comment = Comment::factory()->create([
            'user_id'    => $this->user->id,
            'comic_id'   => $this->comic->id,
            'created_at' => now()->subDays(30), // 30 ngày trước
        ]);

        $response = $this->policy->update($this->admin, $comment);
        $this->assertTrue($response->allowed());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // delete()
    // ─────────────────────────────────────────────────────────────────────────

    public function test_owner_can_delete_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id'  => $this->user->id,
            'comic_id' => $this->comic->id,
        ]);

        $response = $this->policy->delete($this->user, $comment);
        $this->assertTrue($response->allowed());
    }

    public function test_non_owner_cannot_delete_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id'  => $this->user->id,
            'comic_id' => $this->comic->id,
        ]);

        $response = $this->policy->delete($this->otherUser, $comment);
        $this->assertTrue($response->denied());
        $this->assertSame(403, $response->status());
    }

    public function test_admin_can_delete_any_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id'  => $this->user->id,
            'comic_id' => $this->comic->id,
        ]);

        $response = $this->policy->delete($this->admin, $comment);
        $this->assertTrue($response->allowed());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // restore()
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_can_restore_soft_deleted_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id'  => $this->user->id,
            'comic_id' => $this->comic->id,
        ]);

        $response = $this->policy->restore($this->admin, $comment);
        $this->assertTrue($response->allowed());
    }

    public function test_normal_user_cannot_restore_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id'  => $this->user->id,
            'comic_id' => $this->comic->id,
        ]);

        $response = $this->policy->restore($this->user, $comment);
        $this->assertTrue($response->denied());
        $this->assertSame(403, $response->status());
    }
}
