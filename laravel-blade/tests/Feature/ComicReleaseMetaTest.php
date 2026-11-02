<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComicReleaseMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_meta_returns_latest_published_chapter(): void
    {
        $comic = Comic::factory()->create([
            'slug' => 'release-meta-comic',
            'published_at' => now()->subDay(),
        ]);

        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
            'published_at' => now()->subHours(2),
        ]);

        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 2,
            'published_at' => now()->subHour(),
        ]);

        Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 3,
            'published_at' => now()->addDay(),
        ]);

        $this->getJson(route('api.comics.releaseMeta', $comic->slug))
            ->assertOk()
            ->assertJsonPath('data.latest_chapter.number', 2);
    }

    public function test_latest_chapter_eager_load_returns_highest_published_chapter_per_comic(): void
    {
        $first = Comic::factory()->create(['published_at' => now()->subDay()]);
        $second = Comic::factory()->create(['published_at' => now()->subDay()]);

        foreach ([1, 3, 5] as $number) {
            Chapter::factory()->create([
                'comic_id' => $first->id,
                'chapter_number' => $number,
                'published_at' => now()->subHour(),
            ]);
        }

        // A future chapter must not replace the highest published one.
        Chapter::factory()->create([
            'comic_id' => $first->id,
            'chapter_number' => 6,
            'published_at' => now()->addDay(),
        ]);

        foreach ([2, 4] as $number) {
            Chapter::factory()->create([
                'comic_id' => $second->id,
                'chapter_number' => $number,
                'published_at' => now()->subHour(),
            ]);
        }

        $comics = Comic::with('latestChapter')
            ->whereIn('id', [$first->id, $second->id])
            ->get()
            ->keyBy('id');

        $this->assertSame(5, (int) $comics[$first->id]->latestChapter->chapter_number);
        $this->assertSame(4, (int) $comics[$second->id]->latestChapter->chapter_number);
    }
}
