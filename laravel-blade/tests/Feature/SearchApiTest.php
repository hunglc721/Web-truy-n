<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_search_endpoint_returns_json_results(): void
    {
        $comic = Comic::factory()->create([
            'title'      => 'Solo Leveling Ragnarok',
            'views'      => 10000,
            'avg_rating' => 4.9,
        ]);

        $response = $this->getJson('/api/search/live?q=ragnarok');

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'query'  => 'ragnarok',
            'count'  => 1,
            'data'   => [
                [
                    'id'    => $comic->id,
                    'title' => 'Solo Leveling Ragnarok',
                ]
            ],
        ]);
    }

    public function test_live_search_with_empty_or_short_query_returns_empty_data(): void
    {
        $response = $this->getJson('/api/search/live?q=a');

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'count'  => 0,
            'data'   => [],
        ]);
    }

    public function test_advanced_search_endpoint_returns_paginated_json(): void
    {
        $genre = Genre::create(['name' => 'Supernatural', 'slug' => 'supernatural']);
        $comic = Comic::factory()->create(['title' => 'Ghost Hunter']);
        $comic->genres()->attach($genre->id);

        $response = $this->getJson('/api/search/advanced?genres[]=supernatural&per_page=10');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'data' => [
                'current_page',
                'data' => [
                    '*' => ['id', 'title', 'slug', 'genres'],
                ],
                'total',
            ],
        ]);
        $response->assertJson([
            'status' => 'success',
            'data'   => [
                'total' => 1,
            ],
        ]);
    }

    public function test_live_search_endpoint_supports_vietnamese_accent_insensitive_query(): void
    {
        $comic = Comic::factory()->create([
            'title' => 'Võ Luyện Đỉnh Phong',
        ]);

        // User gõ không dấu: "vo luyen dinh phong"
        $response = $this->getJson('/api/search/live?q=' . urlencode('vo luyen'));

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'count'  => 1,
            'data'   => [
                [
                    'id'    => $comic->id,
                    'title' => 'Võ Luyện Đỉnh Phong',
                ]
            ],
        ]);
    }

    public function test_clearing_description_clears_normalized_text_and_stops_matching_search(): void
    {
        $comic = Comic::factory()->create([
            'title'       => 'Kiem Hiep Ky Duyen',
            'description' => 'Hành trình phiêu lưu của một kiếm khách.',
        ]);

        $this->assertSame('hanh trinh phieu luu cua mot kiem khach', $comic->fresh()->description_normalized);

        $comic->update(['description' => null]);

        $this->assertNull($comic->fresh()->description_normalized);

        $response = $this->getJson('/api/search/advanced?q='.urlencode('phieu luu').'&per_page=10');

        $response->assertOk();
        $response->assertJsonPath('data.total', 0);
    }

    public function test_clearing_alt_titles_clears_normalized_text(): void
    {
        $comic = Comic::factory()->create([
            'title'      => 'Kiem Hiep Ky Duyen',
            'alt_titles' => 'Thanh Kiếm Phiêu Lưu',
        ]);

        $this->assertSame('thanh kiem phieu luu', $comic->fresh()->alt_titles_normalized);

        $comic->update(['alt_titles' => null]);

        $this->assertNull($comic->fresh()->alt_titles_normalized);
    }

    public function test_live_search_endpoint_hides_unpublished_comics(): void
    {
        $published = Comic::factory()->create(['title' => 'Public Ragnarok']);
        Comic::factory()->create(['title' => 'Draft Ragnarok', 'published_at' => null]);
        Comic::factory()->create(['title' => 'Scheduled Ragnarok', 'published_at' => now()->addDay()]);

        $response = $this->getJson('/api/search/live?q=ragnarok');

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'count'  => 1,
            'data'   => [
                [
                    'id'    => $published->id,
                    'title' => 'Public Ragnarok',
                ],
            ],
        ]);
    }

    public function test_hot_search_keywords_endpoint_returns_trending_data(): void
    {
        Comic::factory()->create(['title' => 'Solo Leveling']);

        // Ghi nhận tìm kiếm
        $this->getJson('/api/search/live?q=Solo%20Leveling');

        $response = $this->getJson('/api/search/hot?limit=5');
        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => ['id', 'keyword', 'hits'],
            ],
        ]);
    }

    public function test_advanced_search_endpoint_filters_by_min_chapters(): void
    {
        $belowThreshold = Comic::factory()->create(['title' => 'Two Chapter Webcomic']);
        $this->createPublishedChapters($belowThreshold, 2);

        $eligible = Comic::factory()->create(['title' => 'Five Chapter Webcomic']);
        $this->createPublishedChapters($eligible, 5);

        $response = $this->getJson('/api/search/advanced?min_chapters=3&per_page=10');

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.total', 1);
        $response->assertJsonPath('data.data.0.id', $eligible->id);
        $response->assertJsonPath('data.data.0.chapters_count', 5);
    }

    public function test_advanced_search_endpoint_ignores_invalid_min_chapters(): void
    {
        $comic = Comic::factory()->create(['title' => 'Any Chapter Webcomic']);
        $this->createPublishedChapters($comic, 1);

        // SearchController has no min_chapters validation; the documented
        // contract is that invalid values behave like "no filter".
        $response = $this->getJson('/api/search/advanced?min_chapters=abc&per_page=10');

        $response->assertOk();
        $response->assertJsonPath('data.total', 1);
        $response->assertJsonPath('data.data.0.id', $comic->id);
    }

    private function createPublishedChapters(Comic $comic, int $count): void
    {
        foreach (range(1, $count) as $number) {
            Chapter::factory()->for($comic)->create([
                'chapter_number' => $number,
                'published_at'   => now()->subDays($count - $number)->subMinute(),
            ]);
        }
    }
}
