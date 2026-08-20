<?php

namespace Tests\Feature;

use App\Models\Comic;
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

    public function test_user_can_toggle_library_via_ajax(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        // 1. Follow comic
        $response = $this->actingAs($user)->postJson(route('library.toggle', $comic));
        $response->assertStatus(200)
            ->assertJson([
                'status'      => 'success',
                'is_followed' => true,
            ]);

        // 2. Unfollow comic
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
