<?php

namespace Tests\Feature;

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
}
