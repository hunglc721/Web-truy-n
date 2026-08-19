<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\User;
use App\Services\ChapterService;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests cho AdminChapterController + ChapterService.
 */
class AdminChapterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Comic $comic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->comic = Comic::factory()->create();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACCESS CONTROL
    // ─────────────────────────────────────────────────────────────────────────

    public function test_non_admin_cannot_access_chapter_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
             ->get(route('admin.comics.chapters.index', $this->comic->id))
             ->assertRedirect('/');
    }

    public function test_unauthenticated_user_redirected_from_admin(): void
    {
        $this->get(route('admin.comics.chapters.index', $this->comic->id))
             ->assertRedirect(route('login'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ChapterService – generateSlug() unique suffix
    // ─────────────────────────────────────────────────────────────────────────

    public function test_chapter_slug_is_unique_within_comic(): void
    {
        $service = app(ChapterService::class);

        // Tạo chapter với slug 'chapter-1' trước
        Chapter::factory()->create([
            'comic_id'       => $this->comic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
        ]);

        // generateSlug phải trả về 'chapter-1-v2' vì 'chapter-1' đã tồn tại
        $slug = $service->generateSlug($this->comic->id, 1);
        $this->assertSame('chapter-1-v2', $slug);
    }

    public function test_chapter_slug_increments_suffix_if_v2_also_exists(): void
    {
        $service = app(ChapterService::class);

        Chapter::factory()->create([
            'comic_id' => $this->comic->id,
            'chapter_number' => 1,
            'slug' => 'chapter-1',
        ]);
        Chapter::factory()->create([
            'comic_id' => $this->comic->id,
            'chapter_number' => 99,
            'slug' => 'chapter-1-v2',
        ]);

        $slug = $service->generateSlug($this->comic->id, 1);
        $this->assertSame('chapter-1-v3', $slug);
    }

    public function test_slug_is_unique_across_different_comics(): void
    {
        $service    = app(ChapterService::class);
        $otherComic = Comic::factory()->create();

        // Slug 'chapter-1' trong otherComic không ảnh hưởng đến this->comic
        Chapter::factory()->create([
            'comic_id'       => $otherComic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
        ]);

        $slug = $service->generateSlug($this->comic->id, 1);
        $this->assertSame('chapter-1', $slug, 'Slug từ comic khác không được tính là duplicate');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STORE – URL list input
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_can_create_chapter_with_url_list(): void
    {
        $this->actingAs($this->admin)
             ->post(route('admin.comics.chapters.store', $this->comic->id), [
                 'chapter_number' => 1,
                 'title'          => 'Chapter 1',
                 'is_free'        => true,
                 'pages_raw'      => "https://cdn.example.com/p1.jpg\nhttps://cdn.example.com/p2.jpg",
             ])
             ->assertRedirect(route('admin.comics.chapters.index', $this->comic->id));

        $chapter = Chapter::where('comic_id', $this->comic->id)
                          ->where('chapter_number', 1)
                          ->first();

        $this->assertNotNull($chapter, 'Chapter phải được tạo');
        $this->assertCount(2, $chapter->pages, 'Chapter phải có 2 trang từ URL list');
    }

    public function test_store_fails_if_no_images_and_no_urls(): void
    {
        $this->actingAs($this->admin)
             ->post(route('admin.comics.chapters.store', $this->comic->id), [
                 'chapter_number' => 2,
             ])
             ->assertRedirect()
             ->assertSessionHasErrors(['images']);
    }
}
