<?php

namespace Tests\Feature;

use App\Jobs\ProcessChapterImages;
use App\Models\Comic;
use App\Models\Chapter;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImagePrefetchAndDimensionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_chapter_pages_with_dimensions_accessor(): void
    {
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'        => $comic->id,
            'pages'           => ['comics/1/chapters/1/p1.jpg', 'comics/1/chapters/1/p2.jpg'],
            'page_dimensions' => [
                ['width' => 900, 'height' => 1350],
                ['width' => 850, 'height' => 1280],
            ],
            'published_at'    => now()->subDay(),
        ]);

        $pagesWithDim = $chapter->pages_with_dimensions;

        $this->assertCount(2, $pagesWithDim);
        $this->assertEquals(900, $pagesWithDim[0]['width']);
        $this->assertEquals(1350, $pagesWithDim[0]['height']);
        $this->assertStringContainsString('comics/1/chapters/1/p1.jpg', $pagesWithDim[0]['url']);

        $this->assertEquals(850, $pagesWithDim[1]['width']);
        $this->assertEquals(1280, $pagesWithDim[1]['height']);
    }

    public function test_reader_renders_image_dimensions_and_lazy_loading_for_layout_stability(): void
    {
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'        => $comic->id,
            'chapter_number'  => 1,
            'slug'            => 'chapter-1',
            'pages'           => [
                'https://cdn.example.com/p1.jpg',
                'https://cdn.example.com/p2.jpg',
                'https://cdn.example.com/p3.jpg',
                'https://cdn.example.com/p4.jpg',
            ],
            'page_dimensions' => [
                ['width' => 800, 'height' => 1200],
                ['width' => 800, 'height' => 1200],
                ['width' => 800, 'height' => 1200],
                ['width' => 800, 'height' => 1200],
            ],
            'published_at'    => now()->subDay(),
        ]);

        $response = $this->get(route('chapters.show', [$comic->slug, $chapter->slug]));
        $response->assertOk();

        // 1. Kiểm tra <img> có width/height/aspect-ratio chống nhảy layout
        $response->assertSee('width="800"', false);
        $response->assertSee('height="1200"', false);
        $response->assertSee('aspect-ratio: 800 / 1200', false);
        $response->assertSee('loading="eager"', false); // Trang 1
        $response->assertSee('loading="lazy"', false);  // Các trang sau

        // 2. Kiểm tra prefetch engine script
        $response->assertSee('SMART IMAGE PREFETCH', false);
        $response->assertSee('link.rel = \'prefetch\'', false);
    }

    public function test_process_chapter_images_job_saves_page_dimensions(): void
    {
        Storage::fake('public');

        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'          => $comic->id,
            'processing_status' => 'pending',
        ]);

        // Tạo 1 fake image trong tmp
        $tmpPath = 'tmp/test_page.png';
        // 1x1 transparent PNG binary
        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        Storage::disk('public')->put($tmpPath, $pngContent);

        $job = new ProcessChapterImages(
            $comic,
            $chapter,
            [$tmpPath],
            ['https://cdn.example.com/remote.jpg']
        );

        $job->handle(app(ImageService::class));

        $chapter->refresh();

        $this->assertEquals('ready', $chapter->processing_status);
        $this->assertCount(2, $chapter->pages);
        $this->assertCount(2, $chapter->page_dimensions);
        $this->assertEquals(1, $chapter->page_dimensions[0]['width']);
        $this->assertEquals(1, $chapter->page_dimensions[0]['height']);
        $this->assertEquals(800, $chapter->page_dimensions[1]['width']);
        $this->assertEquals(1200, $chapter->page_dimensions[1]['height']);
    }
}
