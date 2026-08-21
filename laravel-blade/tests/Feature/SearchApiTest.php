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
}
