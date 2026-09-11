<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreRelationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_genre_can_load_comics_without_nonexistent_pivot_timestamps(): void
    {
        $genre = Genre::create([
            'name' => 'Action',
            'slug' => 'action',
        ]);
        $comic = Comic::factory()->create([
            'title' => 'Schema Safe Comic',
            'slug' => 'schema-safe-comic',
        ]);

        $genre->comics()->attach($comic->id, ['is_primary' => true]);

        $loaded = $genre->comics()->firstOrFail();

        $this->assertSame($comic->id, $loaded->id);
        $this->assertTrue((bool) $loaded->pivot->is_primary);
    }
}
