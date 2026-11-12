<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderConvenienceTest extends TestCase
{
    use RefreshDatabase;

    private function readerResponse(): \Illuminate\Testing\TestResponse
    {
        $comic = Comic::factory()->create(['slug' => 'reader-convenience']);
        $pages = ['https://cdn.example.com/p1.jpg', 'https://cdn.example.com/p2.jpg'];

        foreach ([1, 2] as $number) {
            Chapter::factory()->create([
                'comic_id'        => $comic->id,
                'chapter_number'  => $number,
                'slug'            => 'chapter-' . $number,
                'published_at'    => now()->subDays(2 - $number),
                'pages'           => $pages,
                'page_dimensions' => [
                    ['width' => 800, 'height' => 1200],
                    ['width' => 800, 'height' => 1200],
                ],
            ]);
        }

        return $this->get(route('chapters.show', [$comic->slug, 'chapter-1']));
    }

    public function test_reader_hint_bar_matches_real_shortcuts_and_can_be_dismissed(): void
    {
        $response = $this->readerResponse();
        $response->assertOk();

        $response->assertSee('id="reader-hint-bar"', false);
        $response->assertSee('id="reader-hint-dismiss"', false);
        // Phím H từng được quảng cáo nhưng chưa hề được gán.
        $response->assertSee("toggle_ui: ['h', 'H']", false);
        $response->assertSee('function dismissReaderHint');
        $response->assertSee('reader_hint_hidden');
    }

    public function test_reader_stops_at_last_page_when_auto_advance_is_off(): void
    {
        $response = $this->readerResponse();
        $response->assertOk();

        $response->assertSee('autoAdvanceChapter: false');
        $response->assertSee('historyMode: \'replace\'', false);
        $response->assertSee('function handleChapterBoundary');
        $response->assertSee('function showChapterEndToast');
        $response->assertSee('id="reader-chapter-end-toast"', false);
        $response->assertSee('Chương tiếp theo');
    }

    public function test_guest_reader_debounces_local_history_writes(): void
    {
        $response = $this->readerResponse();
        $response->assertOk();

        $response->assertSee('function queueGuestHistorySave');
        $response->assertSee('guestHistoryTimer');
        $response->assertSee('function flushGuestHistorySave');
        $response->assertSee('function scheduleProgressUpdate');
        $response->assertSee('requestAnimationFrame');
    }

    public function test_reader_has_a_single_tab_switcher_that_activates_panes(): void
    {
        $response = $this->readerResponse();
        $response->assertOk();

        $body = $response->getContent();
        $this->assertSame(1, substr_count($body, 'function switchReaderTab'));
        $this->assertStringContainsString("pane?.classList.toggle('active', isActive);", $body);
    }
}
