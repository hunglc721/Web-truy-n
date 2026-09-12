<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\User;
use App\Services\BulkChapterUploadService;
use App\Services\ChapterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DecimalChapterNumberTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Comic $comicA;
    private Comic $comicB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->comicA = Comic::factory()->create(['title' => 'Manga Series Alpha', 'slug' => 'manga-series-alpha']);
        $this->comicB = Comic::factory()->create(['title' => 'Webtoon Series Beta', 'slug' => 'webtoon-series-beta']);

        File::deleteDirectory(storage_path('app/bulk-chapter-uploads'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/bulk-chapter-uploads'));
        parent::tearDown();
    }

    public function test_can_create_integer_and_decimal_chapters_in_same_comic(): void
    {
        $ch187 = Chapter::factory()->create([
            'comic_id' => $this->comicA->id,
            'chapter_number' => 187,
            'title' => 'Main Chapter',
            'published_at' => now(),
        ]);

        $ch187_5 = Chapter::factory()->create([
            'comic_id' => $this->comicA->id,
            'chapter_number' => 187.5,
            'title' => 'Bonus Chapter',
            'published_at' => now(),
        ]);

        $ch187_25 = Chapter::factory()->create([
            'comic_id' => $this->comicA->id,
            'chapter_number' => 187.25,
            'title' => 'Extra Bonus Chapter',
            'published_at' => now(),
        ]);

        $this->assertEquals(187, $ch187->fresh()->chapter_number);
        $this->assertEquals(187.5, $ch187_5->fresh()->chapter_number);
        $this->assertEquals(187.25, $ch187_25->fresh()->chapter_number);

        $this->assertSame('chapter-187', $ch187->slug);
        $this->assertSame('chapter-187.5', $ch187_5->slug);
        $this->assertSame('chapter-187.25', $ch187_25->slug);

        $this->assertSame('Ch.187', $ch187->label);
        $this->assertSame('Ch.187.5', $ch187_5->label);
        $this->assertSame('Ch.187.25', $ch187_25->label);
    }

    public function test_formatting_normalizes_trailing_zeros_and_prevents_precision_drift(): void
    {
        $this->assertSame('187', Chapter::formatNumber('187.000'));
        $this->assertSame('187.5', Chapter::formatNumber('187.500'));
        $this->assertSame('187.25', Chapter::formatNumber('187.250'));
        $this->assertSame('0.5', Chapter::formatNumber('0.500'));
        $this->assertSame('0', Chapter::formatNumber('0.000'));
        $this->assertSame('187.5', Chapter::formatNumber('187.499999'));
    }

    public function test_duplicate_detection_treats_187_5_and_187_500_as_duplicate(): void
    {
        Chapter::factory()->create([
            'comic_id' => $this->comicA->id,
            'chapter_number' => 187.5,
        ]);

        $bulkService = app(BulkChapterUploadService::class);

        $this->expectException(ValidationException::class);
        $bulkService->finalizeChapter(
            $this->comicA,
            $this->admin,
            'test-session',
            'chapter-0000',
            187.500,
            'Duplicate Check',
            1
        );
    }

    public function test_chapters_are_sorted_mathematically_not_alphabetically(): void
    {
        Chapter::factory()->create(['comic_id' => $this->comicA->id, 'chapter_number' => 188, 'published_at' => now()]);
        Chapter::factory()->create(['comic_id' => $this->comicA->id, 'chapter_number' => 187.5, 'published_at' => now()]);
        Chapter::factory()->create(['comic_id' => $this->comicA->id, 'chapter_number' => 186, 'published_at' => now()]);
        Chapter::factory()->create(['comic_id' => $this->comicA->id, 'chapter_number' => 187.25, 'published_at' => now()]);
        Chapter::factory()->create(['comic_id' => $this->comicA->id, 'chapter_number' => 187, 'published_at' => now()]);

        $ordered = $this->comicA->chapters()->orderBy('chapter_number', 'asc')->pluck('chapter_number')->all();

        $this->assertEquals([186, 187, 187.25, 187.5, 188], $ordered);
    }

    public function test_reader_next_and_previous_navigate_decimal_chapters_in_correct_order(): void
    {
        $ch187 = Chapter::factory()->create(['comic_id' => $this->comicB->id, 'chapter_number' => 187, 'published_at' => now()]);
        $ch187_25 = Chapter::factory()->create(['comic_id' => $this->comicB->id, 'chapter_number' => 187.25, 'published_at' => now()]);
        $ch187_5 = Chapter::factory()->create(['comic_id' => $this->comicB->id, 'chapter_number' => 187.5, 'published_at' => now()]);
        $ch188 = Chapter::factory()->create(['comic_id' => $this->comicB->id, 'chapter_number' => 188, 'published_at' => now()]);

        // Viewing 187.25: previous must be 187, next must be 187.5
        $response = $this->get(route('chapters.show', [$this->comicB->slug, $ch187_25->slug]));
        $response->assertOk();
        $response->assertViewHas('prevChapter', fn($ch) => (float) $ch->chapter_number === 187.0);
        $response->assertViewHas('nextChapter', fn($ch) => (float) $ch->chapter_number === 187.5);

        // Viewing 187.5: previous must be 187.25, next must be 188
        $response2 = $this->get(route('chapters.show', [$this->comicB->slug, $ch187_5->slug]));
        $response2->assertOk();
        $response2->assertViewHas('prevChapter', fn($ch) => (float) $ch->chapter_number === 187.25);
        $response2->assertViewHas('nextChapter', fn($ch) => (float) $ch->chapter_number === 188.0);
    }

    public function test_decimal_chapter_route_and_numeric_slug_redirect_work_properly(): void
    {
        $ch = Chapter::factory()->create([
            'comic_id' => $this->comicB->id,
            'chapter_number' => 187.5,
            'slug' => 'chapter-187.5',
            'published_at' => now(),
        ]);

        // Direct canonical slug
        $this->get('/truyen/' . $this->comicB->slug . '/chapter-187.5')
            ->assertOk();

        // Numeric slug redirect: /truyen/slug/187.5 -> 301 redirect to /truyen/slug/chapter-187.5
        $this->get('/truyen/' . $this->comicB->slug . '/187.5')
            ->assertRedirect('/truyen/' . $this->comicB->slug . '/chapter-187.5');
    }

    public function test_admin_can_create_decimal_chapter_via_store_endpoint(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.comics.chapters.store', $this->comicA->id), [
                'chapter_number' => '187.5',
                'title' => 'Bonus Story',
                'is_free' => true,
                'pages_raw' => "https://cdn.example.com/page1.jpg\nhttps://cdn.example.com/page2.jpg",
            ]);

        $response->assertRedirect(route('admin.comics.chapters.index', $this->comicA->id));

        $chapter = Chapter::where('comic_id', $this->comicA->id)
            ->where('chapter_number', 187.5)
            ->first();

        $this->assertNotNull($chapter);
        $this->assertEquals(187.5, $chapter->chapter_number);
        $this->assertSame('Bonus Story', $chapter->title);
        $this->assertSame('chapter-187.5', $chapter->slug);
    }

    public function test_bulk_folder_upload_finalize_accepts_decimal_chapter(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\App\Jobs\GenerateChapterReaderVariants::class]);
        Storage::fake('public');

        $gifBytes = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $hash = hash('sha256', $gifBytes);

        // Start session
        $startRes = $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.comics.chapters.store', $this->comicA->id), [
                'bulk_action' => 'start',
            ]);
        $session = $startRes->json('session');

        // Chunk upload
        $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.comics.chapters.store', $this->comicA->id), [
                'bulk_action' => 'chunk',
                'session' => $session,
                'chapter_key' => 'chapter-0000',
                'files' => [
                    UploadedFile::fake()->createWithContent('01.gif', $gifBytes),
                ],
                'page_indexes' => [0],
                'checksums' => [$hash],
            ])
            ->assertOk();

        // Finalize with decimal chapter number
        $finalizeRes = $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.comics.chapters.store', $this->comicA->id), [
                'bulk_action' => 'finalize',
                'session' => $session,
                'chapter_key' => 'chapter-0000',
                'chapter_number' => '187.5',
                'title' => 'Bonus Chapter',
                'page_count' => 1,
            ]);

        $finalizeRes->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('chapter_number', 187.5);

        $chapter = Chapter::where('comic_id', $this->comicA->id)
            ->where('chapter_number', 187.5)
            ->first();

        $this->assertNotNull($chapter);
        $this->assertEquals(187.5, $chapter->chapter_number);
        $this->assertSame('chapter-187.5', $chapter->slug);
    }

    public function test_validation_rejects_invalid_chapter_numbers(): void
    {
        // Letters
        $this->actingAs($this->admin)
            ->post(route('admin.comics.chapters.store', $this->comicA->id), [
                'chapter_number' => 'abc',
                'title' => 'Invalid',
            ])
            ->assertSessionHasErrors(['chapter_number']);

        // Multiple dots
        $this->actingAs($this->admin)
            ->post(route('admin.comics.chapters.store', $this->comicA->id), [
                'chapter_number' => '187..5',
                'title' => 'Invalid',
            ])
            ->assertSessionHasErrors(['chapter_number']);

        // Negative numbers
        $this->actingAs($this->admin)
            ->post(route('admin.comics.chapters.store', $this->comicA->id), [
                'chapter_number' => -5,
                'title' => 'Invalid',
            ])
            ->assertSessionHasErrors(['chapter_number']);

        // Exceeding precision (> 3 decimal places)
        $this->actingAs($this->admin)
            ->post(route('admin.comics.chapters.store', $this->comicA->id), [
                'chapter_number' => '187.1234',
                'title' => 'Invalid',
            ])
            ->assertSessionHasErrors(['chapter_number']);
    }
}
