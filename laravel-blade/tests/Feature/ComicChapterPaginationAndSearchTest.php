<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComicChapterPaginationAndSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_comic_detail_view_contains_chapter_search_sort_and_load_more(): void
    {
        $comic = Comic::factory()->create(['title' => 'Võ Luyện Đỉnh Phong', 'slug' => 'vo-luyen-dinh-phong']);
        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'slug' => 'chapter-1',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('comics.show', $comic->slug));
        $response->assertOk();
        $response->assertSee('id="chapter-search-input"', false);
        $response->assertSee('id="chap-sort-desc"', false);
        $response->assertSee('id="chap-sort-asc"', false);
        $response->assertSee('id="btn-load-more-chapters"', false);
        $response->assertSee('id="btn-load-all-chapters"', false);
    }

    public function test_api_chapters_returns_paginated_list(): void
    {
        $comic = Comic::factory()->create(['slug' => 'test-pagination-comic']);
        for ($i = 1; $i <= 60; $i++) {
            Chapter::factory()->create([
                'comic_id' => $comic->id,
                'chapter_number' => $i,
                'slug' => "chapter-{$i}",
                'published_at' => now()->subDays(65 - $i),
            ]);
        }

        $response = $this->getJson("/api/comics/{$comic->id}/chapters?per_page=50&page=1");
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'chapter_number', 'title', 'slug', 'time_ago', 'url']
            ],
            'current_page',
            'last_page',
            'total',
            'has_more',
        ]);

        $this->assertEquals(60, $response->json('total'));
        $this->assertEquals(50, count($response->json('data')));
        $this->assertTrue($response->json('has_more'));
        $this->assertEquals(2, $response->json('last_page'));
    }

    public function test_api_chapters_search_by_number_and_name(): void
    {
        $comic = Comic::factory()->create(['slug' => 'test-search-comic']);
        for ($i = 1; $i <= 100; $i++) {
            Chapter::factory()->create([
                'comic_id' => $comic->id,
                'chapter_number' => $i,
                'title' => $i === 100 ? 'Đại kết cục' : "Nội dung tập {$i}",
                'slug' => "chapter-{$i}",
                'published_at' => now()->subDays(105 - $i),
            ]);
        }

        // Tìm số 100
        $response = $this->getJson("/api/comics/{$comic->id}/chapters?search=100");
        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals(100, $data[0]['chapter_number']);
        $this->assertEquals('Đại kết cục', $data[0]['title']);

        // Tìm dạng "Ch.100"
        $responseCh = $this->getJson("/api/comics/{$comic->id}/chapters?search=Ch.100");
        $responseCh->assertOk();
        $this->assertCount(1, $responseCh->json('data'));
        $this->assertEquals(100, $responseCh->json('data')[0]['chapter_number']);

        // Tìm theo tên
        $responseTitle = $this->getJson("/api/comics/{$comic->id}/chapters?search=kết cục");
        $responseTitle->assertOk();
        $this->assertCount(1, $responseTitle->json('data'));
        $this->assertEquals(100, $responseTitle->json('data')[0]['chapter_number']);
    }

    public function test_api_chapters_sort_asc_and_desc(): void
    {
        $comic = Comic::factory()->create(['slug' => 'test-sort-comic']);
        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'slug' => 'chapter-1',
            'published_at' => now()->subDays(10),
        ]);
        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 200,
            'slug' => 'chapter-200',
            'published_at' => now()->subDay(),
        ]);

        // Mới nhất (desc) -> Ch.200 trước
        $resDesc = $this->getJson("/api/comics/{$comic->id}/chapters?sort=desc");
        $resDesc->assertOk();
        $this->assertEquals(200, $resDesc->json('data')[0]['chapter_number']);

        // Cũ nhất (asc) -> Ch.1 trước
        $resAsc = $this->getJson("/api/comics/{$comic->id}/chapters?sort=asc");
        $resAsc->assertOk();
        $this->assertEquals(1, $resAsc->json('data')[0]['chapter_number']);
    }

    public function test_guest_cannot_see_unpublished_chapters_via_api(): void
    {
        $comic = Comic::factory()->create(['slug' => 'test-publish-gate']);
        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'slug' => 'chapter-1',
            'published_at' => now()->subDay(),
        ]);
        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 2,
            'slug' => 'chapter-2',
            'published_at' => now()->addDays(5), // Future
        ]);

        $resGuest = $this->getJson("/api/comics/{$comic->id}/chapters");
        $resGuest->assertOk();
        $this->assertEquals(1, $resGuest->json('total'));
        $this->assertEquals(1, $resGuest->json('data')[0]['chapter_number']);

        $admin = User::factory()->create(['is_admin' => true]);
        $resAdmin = $this->actingAs($admin)->getJson("/api/comics/{$comic->id}/chapters");
        $resAdmin->assertOk();
        $this->assertEquals(2, $resAdmin->json('total'));
    }
}
