<?php

namespace Tests\Feature;

use App\Jobs\ProcessZipChapterUploadJob;
use App\Models\Comic;
use App\Models\Chapter;
use App\Models\User;
use App\Services\ChapterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use ZipArchive;

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

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/tmp/zip_uploads'));
        parent::tearDown();
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

        Chapter::factory()->create([
            'comic_id'       => $this->comic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
        ]);

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

        Chapter::factory()->create([
            'comic_id'       => $otherComic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
        ]);

        $slug = $service->generateSlug($this->comic->id, 1);
        $this->assertSame('chapter-1', $slug, 'Slug từ comic khác không được tính là duplicate');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STORE
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

    public function test_admin_zip_upload_moves_request_temp_file_into_staging_and_queues_processing(): void
    {
        Queue::fake();

        $zipPath = tempnam(sys_get_temp_dir(), 'comicx_zip_');
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('001.gif', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
        $zip->close();

        $upload = new UploadedFile($zipPath, 'chapter-38mb-style.zip', 'application/zip', null, true);

        $this->actingAs($this->admin)
            ->post(route('admin.comics.chapters.store', $this->comic->id), [
                'chapter_number' => 2,
                'title' => 'ZIP Chapter',
                'is_free' => true,
                'zip_file' => $upload,
            ])
            ->assertRedirect(route('admin.comics.chapters.index', $this->comic->id));

        $chapter = Chapter::query()
            ->where('comic_id', $this->comic->id)
            ->where('chapter_number', 2)
            ->firstOrFail();

        $this->assertSame('pending', $chapter->processing_status);

        Queue::assertPushed(ProcessZipChapterUploadJob::class, function (ProcessZipChapterUploadJob $job): bool {
            $expectedRoot = str_replace('\\', '/', storage_path('app/tmp/zip_uploads'));
            $actualPath = str_replace('\\', '/', $job->zipAbsolutePath);

            return is_file($job->zipAbsolutePath)
                && str_starts_with($actualPath, $expectedRoot . '/');
        });
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
