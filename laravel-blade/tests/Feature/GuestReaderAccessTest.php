<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestReaderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_public_reader_pages_without_login(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('genres'))->assertOk();
        $this->get(route('schedule'))->assertOk();
        $this->get(route('originals'))->assertOk();
    }

    public function test_guest_can_open_a_comic_detail_without_login(): void
    {
        $comic = Comic::factory()->create([
            'slug' => 'guest-reader-test',
        ]);

        $this->get(route('comics.show', $comic->slug))
            ->assertOk();
    }

    public function test_guest_comment_action_requires_authentication(): void
    {
        $comic = Comic::factory()->create();

        $this->postJson(route('comments.store'), [
            'comic_id' => $comic->id,
            'content' => 'Guest comment should be rejected',
        ])->assertStatus(401);
    }

    public function test_member_can_reach_authenticated_library_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('user.library'))
            ->assertOk();
    }
}
