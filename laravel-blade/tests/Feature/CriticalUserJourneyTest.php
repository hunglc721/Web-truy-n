<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Library;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CriticalUserJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_guest_can_discover_open_and_read_a_published_comic(): void
    {
        $comic = Comic::factory()->create([
            'title' => 'Critical Journey Comic',
            'slug' => 'critical-journey-comic',
            'avg_rating' => 4.8,
            'views' => 1500,
        ]);

        $chapterOne = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'title' => 'Khởi đầu',
            'slug' => 'chapter-1',
            'published_at' => now()->subDay(),
        ]);

        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 2,
            'title' => 'Chương tương lai',
            'slug' => 'chapter-2',
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Critical Journey Comic');

        $this->get(route('comics.show', $comic->slug))
            ->assertOk()
            ->assertSee('Critical Journey Comic')
            ->assertSee('Khởi đầu');

        $this->get(route('chapters.show', [$comic->slug, $chapterOne->slug]))
            ->assertOk()
            ->assertSee('Critical Journey Comic');

        $this->get(route('chapters.show', [$comic->slug, 'chapter-2']))
            ->assertNotFound();
    }

    public function test_member_can_follow_save_progress_and_continue_from_library(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $comic = Comic::factory()->create([
            'title' => 'Library Journey Comic',
            'slug' => 'library-journey-comic',
        ]);

        $chapterOne = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'slug' => 'chapter-1',
            'published_at' => now()->subDays(2),
        ]);
        $chapterTwo = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 2,
            'slug' => 'chapter-2',
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->postJson(route('library.toggle', $comic))
            ->assertOk()
            ->assertJsonPath('is_followed', true);

        $this->actingAs($user)
            ->postJson(route('history.save'), [
                'comic_id' => $comic->id,
                'chapter_id' => $chapterOne->id,
                'scroll_percent' => 67.5,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas(ReadingHistory::class, [
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'chapter_id' => $chapterOne->id,
        ]);
        $this->assertDatabaseHas(Library::class, [
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'last_read_chapter_id' => $chapterOne->id,
        ]);

        $this->actingAs($user)
            ->get(route('user.library'))
            ->assertOk()
            ->assertSee('Library Journey Comic')
            ->assertSee('Đọc tiếp Ch.2')
            ->assertSee(route('chapters.show', [$comic->slug, $chapterTwo->slug]), false);
    }

    public function test_search_remains_usable_for_partial_and_unaccented_queries(): void
    {
        $comic = Comic::factory()->create([
            'title' => 'Võ Luyện Đỉnh Phong Ragnarok',
            'slug' => 'vo-luyen-dinh-phong-ragnarok',
        ]);

        $this->getJson('/api/search/live?q=ragnarok')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.id', $comic->id);

        $this->getJson('/api/search/live?q=' . urlencode('vo luyen'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.id', $comic->id);
    }

    public function test_core_public_and_authenticated_pages_do_not_crash(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('genres'))->assertOk();
        $this->get(route('schedule'))->assertOk();
        $this->get(route('originals'))->assertOk();
        $this->get(route('pages.about'))->assertOk();
        $this->get(route('pages.contact'))->assertOk();

        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get(route('user.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('user.library'))->assertOk();
        $this->actingAs($user)->get(route('user.history'))->assertOk();
        $this->actingAs($user)->get(route('user.notifications.index'))->assertOk();
    }
}
