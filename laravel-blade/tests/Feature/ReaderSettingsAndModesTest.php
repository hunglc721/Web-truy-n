<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderSettingsAndModesTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_renders_complete_settings_panel_and_modes(): void
    {
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'        => $comic->id,
            'chapter_number'  => 1,
            'slug'            => 'chapter-1',
            'published_at'    => now()->subDay(),
            'pages'           => ['https://cdn.example.com/p1.jpg', 'https://cdn.example.com/p2.jpg'],
            'page_dimensions' => [
                ['width' => 800, 'height' => 1200],
                ['width' => 800, 'height' => 1200],
            ],
        ]);

        $response = $this->get(route('chapters.show', [$comic->slug, $chapter->slug]));
        $response->assertOk();

        // 1. Kiểm tra nút mở Cài đặt & Bảng điều khiển Settings
        $response->assertSee('id="btn-open-settings"', false);
        $response->assertSee('id="reader-settings-panel"', false);
        $response->assertSee('Tùy Chỉnh Chế Độ Đọc');

        // 2. Kiểm tra các chế độ đọc (Layout: Cuộn dọc / Từng trang)
        $response->assertSee('id="btn-mode-vertical"', false);
        $response->assertSee('id="btn-mode-single"', false);
        $response->assertSee('Cuộn dọc');
        $response->assertSee('Từng trang');

        // 3. Kiểm tra hướng đọc (LTR / RTL Manga)
        $response->assertSee('id="btn-dir-ltr"', false);
        $response->assertSee('id="btn-dir-rtl"', false);
        $response->assertSee('Phải qua Trái');

        // 4. Kiểm tra căn chỉnh khung ảnh (Fit-Width / Fit-Height)
        $response->assertSee('id="btn-fit-width"', false);
        $response->assertSee('id="btn-fit-height"', false);

        // 5. Kiểm tra thanh chỉnh độ sáng (Brightness Slider)
        $response->assertSee('id="brightness-slider"', false);
        $response->assertSee('id="brightness-val"', false);

        // 6. Kiểm tra Single-Page Floating Navigator
        $response->assertSee('id="single-page-nav"', false);
        $response->assertSee('id="single-page-counter"', false);

        // 7. Kiểm tra JavaScript logic: Hotkeys (J/K, H, F, Arrow keys), tap to hide UI, localStorage persistence
        $response->assertSee('function setReadingLayout');
        $response->assertSee('function setReadingDirection');
        $response->assertSee('function setFitMode');
        $response->assertSee('function setBrightness');
        $response->assertSee('function toggleUI');
        $response->assertSee('webcomics_reader_settings');
        $response->assertSee('loadReaderSettings');
        $response->assertSee('saveReaderSettings');
    }
}
