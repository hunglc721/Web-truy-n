<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentAntiSpamAndHoneypotTest extends TestCase
{
    use RefreshDatabase;

    public function test_honeypot_field_filled_blocks_comment_submission(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        $response = $this->actingAs($user)->postJson(route('comments.store'), [
            'comic_id'          => $comic->id,
            'content'           => 'Bình luận hợp lệ',
            '_hp_website_title' => 'bot filled content', // Honeypot trap
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('comments', [
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
        ]);
    }

    public function test_comment_with_spam_link_is_flagged_as_spam_or_pending(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        $response = $this->actingAs($user)->postJson(route('comments.store'), [
            'comic_id' => $comic->id,
            'content'  => 'Vào nhóm kiếm tiền https://spam-link.xyz/tele nhé các bạn',
        ]);

        $response->assertOk()
            ->assertJson([
                'status'  => 'success',
                'is_spam' => true,
            ]);

        $this->assertDatabaseHas('comments', [
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
            'status'   => Comment::STATUS_SPAM,
        ]);
    }
}
