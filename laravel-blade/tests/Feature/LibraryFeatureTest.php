<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Library;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LibraryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_stats_reuse_pagination_total_including_empty_library(): void
    {
        foreach ([13, 0] as $total) {
            $user = User::factory()->create();
            foreach (Comic::factory()->count($total)->create() as $comic) {
                Library::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'status' => 'reading']);
            }

            DB::flushQueryLog();
            DB::enableQueryLog();
            try {
                $response = $this->actingAs($user)->get('/user/library?library_page=2');
                $queries = DB::getQueryLog();
            } finally {
                DB::disableQueryLog();
                DB::flushQueryLog();
            }

            $response->assertOk()->assertViewHas('stats', fn ($stats) => $stats['total_bookmarks'] === $total);
            $response->assertViewHas('libraries', fn ($libraries) =>
                $libraries->total() === $total && $libraries->count() === ($total ? 1 : 0));
            $counts = array_filter($queries, fn ($query) => str_contains(
                str_replace(['"', '`'], '', $query['query']),
                'select count(*) as aggregate from libraries where user_id = ?'
            ));
            $this->assertCount(1, $counts, 'Library total should be counted only by the paginator.');
        }
    }

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
