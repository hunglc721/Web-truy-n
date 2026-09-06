<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeReadingPositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_history_stores_scroll_percentage(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 1,
            'published_at'   => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->postJson(route('history.save'), [
            'comic_id'       => $comic->id,
            'chapter_id'     => $chapter->id,
            'scroll_percent' => 62.50,
        ]);

        $response->assertOk();

        $history = ReadingHistory::where('user_id', $user->id)
            ->where('comic_id', $comic->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals(62.50, $history->scroll_percent);
        $this->assertEquals($chapter->id, $history->chapter_id);
    }

    public function test_comic_detail_shows_continue_reading_button_with_percentage(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chap1 = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
            'published_at'   => now()->subDay(),
        ]);
        $chap2 = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 2,
            'slug'           => 'chapter-2',
            'published_at'   => now()->subDay(),
        ]);

        ReadingHistory::create([
            'user_id'        => $user->id,
            'comic_id'       => $comic->id,
            'chapter_id'     => $chap2->id,
            'scroll_percent' => 62.00,
            'last_read_at'   => now(),
        ]);

        $response = $this->actingAs($user)->get(route('comics.show', $comic->slug));
        $response->assertOk();
        $response->assertSee('Đọc Tiếp (Ch.2 - 62%)');
        $response->assertSee(route('chapters.show', [$comic->slug, $chap2->slug]));
    }

    public function test_reader_view_passes_last_scroll_percent_and_renders_resume_toast(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 5,
            'slug'           => 'chapter-5',
            'published_at'   => now()->subDay(),
        ]);

        ReadingHistory::create([
            'user_id'        => $user->id,
            'comic_id'       => $comic->id,
            'chapter_id'     => $chapter->id,
            'scroll_percent' => 75.00,
            'last_read_at'   => now(),
        ]);

        $response = $this->actingAs($user)->get(route('chapters.show', [$comic->slug, $chapter->slug]));
        $response->assertOk();
        $response->assertViewHas('lastScrollPercent', 75.00);
        $response->assertSee('id="resume-scroll-toast"', false);
        $response->assertSee('Về đầu chương');
    }
}
