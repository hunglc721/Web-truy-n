<?php

namespace Tests\Feature;

use App\Jobs\ProcessZipChapterUploadJob;
use App\Models\Chapter;
use App\Models\Comic;
use App\Services\ChapterNotificationService;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ZipChapterUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_zip_chapter_upload_job_extracts_and_sorts_pages_naturally(): void
    {
        Storage::fake('public');

        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'          => $comic->id,
            'chapter_number'    => 1,
            'processing_status' => 'pending',
        ]);

        // Tạo 1 file zip giả lập với các file ảnh 02.jpg, 01.jpg, 10.jpg
        $tmpZipPath = storage_path('app/tmp_test_' . time() . '.zip');
        $zip = new ZipArchive();
        $zip->open($tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 1x1 gif pixel base64
        $fakeImg = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $zip->addFromString('02.jpg', $fakeImg);
        $zip->addFromString('01.jpg', $fakeImg);
        $zip->addFromString('10.jpg', $fakeImg);
        $zip->addFromString('.DS_Store', 'junk'); // file rác cần bỏ qua
        $zip->close();

        $imageService = app(ImageService::class);
        $notificationService = app(ChapterNotificationService::class);

        $job = new ProcessZipChapterUploadJob($comic, $chapter, $tmpZipPath);
        $job->handle($imageService, $notificationService);

        $chapter->refresh();
        $this->assertEquals('ready', $chapter->processing_status);
        $this->assertCount(3, $chapter->pages);
        $this->assertCount(3, $chapter->page_dimensions);

        // Trang đầu tiên phải là 001 (từ 01.jpg)
        $this->assertStringContainsString('001.jpg', $chapter->pages[0]);
        // Trang thứ 2 phải là 002 (từ 02.jpg)
        $this->assertStringContainsString('002.jpg', $chapter->pages[1]);
        // Trang thứ 3 phải là 003 (từ 10.jpg)
        $this->assertStringContainsString('003.jpg', $chapter->pages[2]);
    }
}
