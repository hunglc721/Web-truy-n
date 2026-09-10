<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Library;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_library_page(): void
    {
        $response = $this->get('/user/library');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_library_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/user/library');
        $response->assertStatus(200);
        $response->assertViewIs('user.library');
    }

    public function test_library_page_renders_search_and_read_state_filters(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create(['title' => 'Solo Reader']);
        Library::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'status' => 'reading',
            'added_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('user.library'));

        $response->assertOk();
        $response->assertSee('id="library-search"', false);
        $response->assertSee('data-filter="all"', false);
        $response->assertSee('data-filter="unread"', false);
        $response->assertSee('data-filter="caught-up"', false);
        $response->assertSee('Solo Reader');
    }

    public function test_user_can_toggle_library_via_ajax(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        $response = $this->actingAs($user)->postJson(route('library.toggle', $comic));
        $response->assertStatus(200)
            ->assertJson([
                'status'      => 'success',
                'is_followed' => true,
            ]);

        $response2 = $this->actingAs($user)->postJson(route('library.toggle', $comic));
        $response2->assertStatus(200)
            ->assertJson([
                'status'      => 'success',
                'is_followed' => false,
            ]);
    }

    public function test_user_can_clear_reading_history(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson(route('history.clear'));
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);
    }
}
