<?php

namespace Tests\Feature;

use App\Jobs\ProcessZipChapterUploadJob;
use App\Models\Chapter;
use App\Models\Comic;
use App\Services\ChapterNotificationService;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ZipChapterUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_zip_chapter_upload_job_extracts_and_sorts_pages_naturally(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\App\Jobs\GenerateChapterReaderVariants::class]);
        Storage::fake('public');

        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'pages' => [],
            'processing_status' => 'pending',
        ]);

        $tmpZipPath = storage_path('app/tmp_test_' . uniqid() . '.zip');
        $zip = new ZipArchive();
        $zip->open($tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $fakeImg = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $zip->addFromString('chapter/10.jpg', $fakeImg);
        $zip->addFromString('chapter/02.jpg', $fakeImg);
        $zip->addFromString('chapter/01.jpg', $fakeImg);
        $zip->addFromString('__MACOSX/._01.jpg', 'junk');
        $zip->addFromString('.DS_Store', 'junk');
        $zip->close();

        $job = new ProcessZipChapterUploadJob($comic, $chapter, $tmpZipPath);
        $job->handle(app(ImageService::class), app(ChapterNotificationService::class));

        $chapter->refresh();

        $this->assertSame('ready', $chapter->processing_status);
        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\GenerateChapterReaderVariants::class,
            fn ($job) => $job->chapterId === $chapter->id);
        $this->assertSame(hash('sha256', $fakeImg), hash('sha256', Storage::disk('public')->get("chapters/{$comic->id}/{$chapter->id}/001.jpg")));
        $this->assertCount(3, $chapter->pages);
        $this->assertCount(3, $chapter->page_dimensions);
        $this->assertStringContainsString('001.jpg', $chapter->pages[0]);
        $this->assertStringContainsString('002.jpg', $chapter->pages[1]);
        $this->assertStringContainsString('003.jpg', $chapter->pages[2]);
        $this->assertFalse(is_file($tmpZipPath), 'ZIP tạm phải được dọn sau khi xử lý.');

        Storage::disk('public')->assertExists("chapters/{$comic->id}/{$chapter->id}/001.jpg");
        Storage::disk('public')->assertExists("chapters/{$comic->id}/{$chapter->id}/002.jpg");
        Storage::disk('public')->assertExists("chapters/{$comic->id}/{$chapter->id}/003.jpg");
    }

    public function test_process_zip_chapter_upload_rejects_path_traversal_entries(): void
    {
        Storage::fake('public');

        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 2,
            'pages' => [],
            'processing_status' => 'pending',
        ]);

        $tmpZipPath = storage_path('app/tmp_unsafe_' . uniqid() . '.zip');
        $zip = new ZipArchive();
        $zip->open($tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $fakeImg = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $zip->addFromString('../escape.jpg', $fakeImg);
        $zip->addFromString('01.jpg', $fakeImg);
        $zip->close();

        $job = new ProcessZipChapterUploadJob($comic, $chapter, $tmpZipPath);
        $job->handle(app(ImageService::class), app(ChapterNotificationService::class));

        $chapter->refresh();

        $this->assertSame('failed', $chapter->processing_status);
        $this->assertSame([], $chapter->pages ?? []);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertFalse(is_file($tmpZipPath), 'ZIP không an toàn cũng phải được dọn.');
    }
}
