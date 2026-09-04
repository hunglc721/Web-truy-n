<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RoadmapFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_chapter_catalog_only_returns_published_chapters_and_can_search_and_sort(): void
    {
        $comic = Comic::factory()->create(['title' => 'Roadmap Comic', 'slug' => 'roadmap-comic']);

        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'title' => 'Khởi đầu',
            'slug' => 'chapter-1',
            'published_at' => now()->subDays(2),
        ]);
        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 2,
            'title' => 'Trận chiến',
            'slug' => 'chapter-2',
            'published_at' => now()->subDay(),
        ]);
        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 3,
            'title' => 'Tương lai',
            'slug' => 'chapter-3',
            'published_at' => now()->addDay(),
        ]);

        $this->getJson(route('api.comics.chapters.index', ['comic' => $comic->slug, 'sort' => 'asc']))
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.chapter_number', 1)
            ->assertJsonPath('data.1.chapter_number', 2);

        $this->getJson(route('api.comics.chapters.index', ['comic' => $comic->slug, 'q' => '2']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.chapter_number', 2);
    }

    public function test_home_contains_new_discovery_sections_when_published_comics_exist(): void
    {
        $comic = Comic::factory()->create([
            'title' => 'Discovery Comic',
            'slug' => 'discovery-comic',
            'avg_rating' => 4.8,
            'views' => 1000,
        ]);
        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'slug' => 'chapter-1',
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Gợi Ý Hôm Nay')
            ->assertSee('Truyện Mới Lên Kệ')
            ->assertSee('Discovery Comic');
    }

    public function test_sitemap_contains_published_comic_and_public_pages_render(): void
    {
        $comic = Comic::factory()->create(['title' => 'SEO Comic', 'slug' => 'seo-comic']);
        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'slug' => 'chapter-1',
            'published_at' => now()->subHour(),
        ]);

        $response = $this->get(route('sitemap'));
        $response->assertOk()->assertSee(route('comics.show', $comic->slug), false);
        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));

        $this->get(route('pages.about'))->assertOk()->assertSee('Giới Thiệu WebComics');
        $this->get(route('pages.terms'))->assertOk()->assertSee('Điều Khoản Sử Dụng');
        $this->get(route('pages.privacy'))->assertOk()->assertSee('Chính Sách Riêng Tư');
        $this->get(route('pages.contact'))->assertOk()->assertSee('Liên Hệ WebComics');
    }

    public function test_contact_form_persists_a_message(): void
    {
        $this->post(route('pages.contact.submit'), [
            'name' => 'Nguyen Van A',
            'email' => 'reader@example.com',
            'subject' => 'Lỗi reader trên mobile',
            'message' => 'Trang reader bị tràn chiều ngang trên thiết bị nhỏ.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas(ContactMessage::class, [
            'email' => 'reader@example.com',
            'subject' => 'Lỗi reader trên mobile',
            'status' => 'new',
        ]);
    }
}
