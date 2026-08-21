<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\Library;
use App\Models\ReadingHistory;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SafeUrlGenerationAndNullSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_comic_and_chapter_slug_accessor_never_returns_null_or_empty(): void
    {
        $comic = Comic::factory()->create(['title' => 'Test Comic', 'slug' => '']);
        $chapter = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 15,
            'slug'           => '',
        ]);

        $this->assertNotEmpty($comic->slug);
        $this->assertNotEmpty($chapter->slug);
        $this->assertEquals('chapter-15', $chapter->slug);
    }

    public function test_comic_detail_page_renders_safely_even_with_empty_slugs_or_missing_chapters(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create(['slug' => 'comic-test-slug']);

        $chap1 = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 1,
            'slug'           => null,
        ]);

        ReadingHistory::create([
            'user_id'        => $user->id,
            'comic_id'       => $comic->id,
            'chapter_id'     => $chap1->id,
            'last_read_at'   => now(),
            'scroll_percent' => 50,
        ]);

        $response = $this->actingAs($user)->get(route('comics.show', $comic->slug));
        $response->assertOk();
    }

    public function test_user_library_page_renders_safely_when_chapter_has_null_slug(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create(['slug' => 'comic-library-test']);
        $chapter = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 5,
            'slug'           => null,
        ]);

        Library::create([
            'user_id'              => $user->id,
            'comic_id'             => $comic->id,
            'last_read_chapter_id' => $chapter->id,
            'status'               => 'reading',
        ]);

        ReadingHistory::create([
            'user_id'        => $user->id,
            'comic_id'       => $comic->id,
            'chapter_id'     => $chapter->id,
            'last_read_at'   => now(),
        ]);

        $response = $this->actingAs($user)->get(route('user.library'));
        $response->assertOk();
    }

    public function test_chapter_reader_page_renders_safely_with_numeric_url_and_redirects(): void
    {
        $comic = Comic::factory()->create(['slug' => 'comic-reader-test']);
        $chapter = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
            'published_at'   => now()->subMinute(),
        ]);

        // Truy cập qua số: /truyen/{comicSlug}/1
        $response = $this->get('/truyen/' . $comic->slug . '/1');
        $response->assertRedirect(route('chapters.show', [$comic->slug, 'chapter-1']));

        // Truy cập qua canonical slug: /truyen/{comicSlug}/chapter-1
        $responseCanonical = $this->get(route('chapters.show', [$comic->slug, 'chapter-1']));
        $responseCanonical->assertOk();
    }

    public function test_admin_reports_and_comments_render_safely_with_null_chapter_slugs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 2,
            'slug'           => null,
        ]);

        Report::create([
            'user_id'     => $admin->id,
            'comic_id'    => $comic->id,
            'chapter_id'  => $chapter->id,
            'reason'      => 'broken_image',
            'description' => 'Ảnh bị lỗi',
            'status'      => 'pending',
        ]);

        Comment::create([
            'user_id'    => $admin->id,
            'comic_id'   => $comic->id,
            'chapter_id' => $chapter->id,
            'content'    => 'Comment test',
        ]);

        $responseReports = $this->actingAs($admin)->get(route('admin.reports.index'));
        $responseReports->assertOk();

        $responseComments = $this->actingAs($admin)->get(route('admin.comments.index'));
        $responseComments->assertOk();
    }
}
