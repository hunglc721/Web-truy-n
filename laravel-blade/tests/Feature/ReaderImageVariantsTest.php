<?php

namespace Tests\Feature;

use App\Jobs\GenerateChapterReaderVariants;
use App\Jobs\ProcessChapterImages;
use App\Models\Chapter;
use App\Models\Comic;
use App\Services\ImageService;
use App\Services\ReaderImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReaderImageVariantsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function chapter(int $width = 1600, int $height = 2400): Chapter
    {
        $chapter = Chapter::factory()->create(['pages' => [], 'processing_status' => 'ready']);
        $path = "comics/{$chapter->comic_id}/chapters/{$chapter->id}/001.png";
        $file = UploadedFile::fake()->image('page.png', $width, $height);
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));
        $chapter->update(['pages' => [$path], 'page_dimensions' => [['width' => $width, 'height' => $height]]]);
        return $chapter;
    }

    public function test_legacy_and_missing_originals_remain_readable_without_variants(): void
    {
        $chapter = $this->chapter();
        $this->assertSame('', $chapter->reader_pages[0]['srcset']);
        $this->assertStringEndsWith('/001.png', $chapter->reader_pages[0]['url']);
        Storage::disk('public')->delete($chapter->pages[0]);
        $this->assertSame('', $chapter->reader_pages[0]['srcset']);
        $this->get(route('chapters.show', [$chapter->comic->slug, $chapter->slug]))->assertOk();
    }

    public function test_generation_preserves_bytes_dimensions_and_is_idempotent(): void
    {
        $chapter = $this->chapter();
        $original = Storage::disk('public')->path($chapter->pages[0]);
        $hash = hash_file('sha256', $original);
        $job = new GenerateChapterReaderVariants($chapter->id);
        $job->handle(app(ReaderImageService::class));
        $pages = $chapter->reader_pages;
        $this->assertSame([480, 800, 1200], array_column($pages[0]['variants'], 'width'));
        foreach ($pages[0]['variants'] as $variant) {
            $file = Storage::disk('public')->path($variant['path']);
            $size = getimagesize($file);
            $this->assertSame($variant['width'], $size[0]);
            $this->assertSame((int) ($variant['width'] * 1.5), $size[1]);
            $this->assertSame('image/webp', $size['mime']);
            touch($file, 1000000000);
        }
        $job->handle(app(ReaderImageService::class));
        foreach ($pages[0]['variants'] as $variant) {
            clearstatcache();
            $this->assertSame(1000000000, filemtime(Storage::disk('public')->path($variant['path'])));
        }
        $this->assertSame($hash, hash_file('sha256', $original));
        $this->assertSame('ready', $chapter->fresh()->processing_status);
        $this->assertStringNotContainsString('001.png 1600w', $pages[0]['srcset']);
        $this->assertStringContainsString('480w', $pages[0]['srcset']);
        $this->get(route('chapters.show', [$chapter->comic->slug, $chapter->slug]))
            ->assertOk()->assertSee('srcset=', false)->assertSee('fetchpriority="high"', false);
    }

    public function test_small_images_are_not_upscaled(): void
    {
        foreach ([320 => [], 600 => [480], 1000 => [480, 800]] as $width => $expected) {
            $chapter = $this->chapter($width, $width * 2);
            app(ReaderImageService::class)->generate($chapter);
            $this->assertSame($expected, array_column($chapter->reader_pages[0]['variants'], 'width'));
        }
    }

    public function test_external_urls_are_never_mapped_to_local_images(): void
    {
        $chapter = $this->chapter();
        $local = $chapter->pages[0];
        $external = 'https://external.example/storage/' . $local;
        $chapter->update(['pages' => [$external, '//external.example/page.jpg', '../private.png']]);
        $service = app(ReaderImageService::class);
        $service->generate($chapter);
        $this->assertSame($external, $chapter->reader_pages[0]['url']);
        $this->assertSame('', $chapter->reader_pages[0]['srcset']);
        $this->assertNull($service->localPath($external));
        $this->assertNull($service->localPath('../private.png'));
        $this->assertNull($service->localPath('%2e%2e/private.png'));
    }

    public function test_zip_storage_url_and_relative_storage_prefix_are_supported(): void
    {
        $chapter = $this->chapter(800, 1200);
        $path = $chapter->pages[0];
        foreach ([Storage::disk('public')->url($path), '/storage/' . $path] as $url) {
            $chapter->update(['pages' => [$url]]);
            app(ReaderImageService::class)->generate($chapter);
            $this->assertCount(2, $chapter->reader_pages[0]['variants']);
            $this->assertStringNotContainsString('storage/storage', $chapter->reader_pages[0]['url']);
        }
    }

    public function test_missing_or_corrupt_variants_can_be_rebuilt_without_touching_original(): void
    {
        $chapter = $this->chapter(800, 1200);
        $service = app(ReaderImageService::class);
        $service->generate($chapter);
        $variants = $chapter->reader_pages[0]['variants'];
        Storage::disk('public')->delete($variants[0]['path']);
        $this->assertCount(1, $chapter->reader_pages[0]['variants']);
        Storage::disk('public')->put($variants[1]['path'], 'corrupt');
        $service->generate($chapter);
        $this->assertCount(2, $chapter->reader_pages[0]['variants']);
        $this->assertSame(800, getimagesize(Storage::disk('public')->path($variants[1]['path']))[0]);
    }

    public function test_unsupported_gd_and_retry_failure_leave_originals_usable(): void
    {
        $chapter = $this->chapter();
        config(['reader.enabled' => false]);
        $job = new GenerateChapterReaderVariants($chapter->id);
        $job->handle(app(ReaderImageService::class));
        $this->assertSame('', $chapter->reader_pages[0]['srcset']);
        config(['reader.enabled' => true, 'reader.max_pixels' => 1]);
        try {
            $job->handle(app(ReaderImageService::class));
            $this->fail('Expected a retryable optimization failure.');
        } catch (\RuntimeException $e) {
            $job->failed($e);
        }
        $this->assertSame(3, $job->tries);
        $this->assertSame('ready', $chapter->fresh()->processing_status);
        Storage::disk('public')->assertExists($chapter->pages[0]);
        $this->get(route('chapters.show', [$chapter->comic->slug, $chapter->slug]))->assertOk();
    }

    public function test_loose_upload_preserves_original_bytes_and_queues_after_processing(): void
    {
        Bus::fake([GenerateChapterReaderVariants::class]);
        $file = UploadedFile::fake()->image('page.jpg', 800, 1200);
        $bytes = file_get_contents($file->getRealPath());
        $images = app(ImageService::class);
        $tmp = $images->uploadSingle($file, 'tmp/test');
        $this->assertSame($bytes, Storage::disk('public')->get($tmp));
        $chapter = Chapter::factory()->create(['pages' => [], 'processing_status' => 'pending']);
        (new ProcessChapterImages($chapter->comic, $chapter, [$tmp]))->handle($images);
        $chapter->refresh();
        $this->assertSame($bytes, Storage::disk('public')->get($chapter->pages[0]));
        Bus::assertDispatched(GenerateChapterReaderVariants::class, fn ($job) => $job->chapterId === $chapter->id);
        $this->assertSame('', $chapter->reader_pages[0]['srcset']);
    }
}
