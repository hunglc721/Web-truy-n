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

    public function test_can_create_required_decimal_chapters_without_false_duplicates(): void
    {
        // Must support: 90, 90.1, 90.01, 90.001, 90.12345, 91
        $numbers = ['90', '90.1', '90.01', '90.001', '90.12345', '91'];
        $created = [];

        foreach ($numbers as $num) {
            $chapter = Chapter::factory()->create([
                'comic_id' => $this->comicA->id,
                'chapter_number' => $num,
                'title' => "Chapter {$num}",
                'published_at' => now(),
            ]);
            $created[$num] = $chapter;
        }

        $this->assertEquals(90, $created['90']->fresh()->chapter_number);
        $this->assertEquals(90.1, $created['90.1']->fresh()->chapter_number);
        $this->assertEquals(90.01, $created['90.01']->fresh()->chapter_number);
        $this->assertEquals(90.001, $created['90.001']->fresh()->chapter_number);
        $this->assertEquals(90.12345, $created['90.12345']->fresh()->chapter_number);
        $this->assertEquals(91, $created['91']->fresh()->chapter_number);

        // Labels
        $this->assertSame('Ch.90', $created['90']->label);
        $this->assertSame('Ch.90.1', $created['90.1']->label);
        $this->assertSame('Ch.90.01', $created['90.01']->label);
        $this->assertSame('Ch.90.001', $created['90.001']->label);
        $this->assertSame('Ch.90.12345', $created['90.12345']->label);
        $this->assertSame('Ch.91', $created['91']->label);

        // Inequality: 90.01 != 90.1
        $this->assertNotEquals($created['90.01']->chapter_number, $created['90.1']->chapter_number);
    }

    public function test_formatting_normalizes_leading_and_trailing_zeros(): void
    {
        // Leading zeros in integer part removed
        $this->assertSame('90.1', Chapter::formatNumber('090.1'));
        $this->assertSame('1.25', Chapter::formatNumber('001.25'));

        // Trailing zeros in decimal part removed
        $this->assertSame('90.1', Chapter::formatNumber('90.100'));
        $this->assertSame('90.01', Chapter::formatNumber('90.0100'));
        $this->assertSame('90', Chapter::formatNumber('90.000'));

        // Normalization equivalence: 90.1 == 90.10 == 90.100
        $this->assertSame(Chapter::formatNumber('90.1'), Chapter::formatNumber('90.10'));
        $this->assertSame(Chapter::formatNumber('90.10'), Chapter::formatNumber('90.100'));

        // Generic precision preserved without rounding
        $this->assertSame('90.12345', Chapter::formatNumber('90.12345'));
        $this->assertSame('999.999999', Chapter::formatNumber('999.999999'));
        $this->assertSame('187.5', Chapter::formatNumber('187.5'));
        $this->assertSame('187.25', Chapter::formatNumber('187.25'));
        $this->assertSame('0', Chapter::formatNumber('0'));
        $this->assertSame('0', Chapter::formatNumber('0.000'));
    }

    public function test_duplicate_detection_treats_90_1_and_90_10_as_duplicate_but_keeps_90_01_separate(): void
    {
        Chapter::factory()->create([
            'comic_id' => $this->comicA->id,
            'chapter_number' => '90.1',
        ]);

        $bulkService = app(BulkChapterUploadService::class);

        // 90.10 must be recognized as duplicate of 90.1
        $this->expectException(ValidationException::class);
        $bulkService->finalizeChapter(
            $this->comicA,
            $this->admin,
            'test-session',
            'chapter-0000',
            '90.10',
            'Duplicate Check',
            1
        );
    }

    public function test_duplicate_detection_allows_90_01_when_90_1_exists(): void
    {
        Storage::fake('public');

        Chapter::factory()->create([
            'comic_id' => $this->comicA->id,
            'chapter_number' => '90.1',
        ]);

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
                'chapter_key' => 'chapter-0001',
                'files' => [
                    UploadedFile::fake()->createWithContent('01.gif', $gifBytes),
                ],
                'page_indexes' => [0],
                'checksums' => [$hash],
            ])
            ->assertOk();

        // 90.01 is distinct from 90.1 and must be accepted
        $finalizeRes = $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.comics.chapters.store', $this->comicA->id), [
                'bulk_action' => 'finalize',
                'session' => $session,
                'chapter_key' => 'chapter-0001',
                'chapter_number' => '90.01',
                'title' => 'Different Chapter',
                'page_count' => 1,
            ]);

        $finalizeRes->assertOk();

        $ch90_01 = Chapter::where('comic_id', $this->comicA->id)
            ->where('chapter_number', '90.01')
            ->first();
        $this->assertNotNull($ch90_01);
    }

    public function test_chapters_are_sorted_mathematically_not_alphabetically(): void
    {
        // 90 < 90.001 < 90.01 < 90.1 < 90.11 < 90.5 < 91
        $unsorted = ['91', '90.5', '90.01', '90', '90.11', '90.1', '90.001'];
        foreach ($unsorted as $num) {
            Chapter::factory()->create([
                'comic_id' => $this->comicA->id,
                'chapter_number' => $num,
                'published_at' => now(),
            ]);
        }

        $ordered = $this->comicA->chapters()
            ->orderBy('chapter_number', 'asc')
            ->pluck('chapter_number')
            ->all();

        $this->assertEquals([90, 90.001, 90.01, 90.1, 90.11, 90.5, 91], $ordered);
    }

    public function test_reader_next_and_previous_navigate_decimal_chapters_in_correct_order(): void
    {
        $ch90 = Chapter::factory()->create(['comic_id' => $this->comicB->id, 'chapter_number' => 90, 'published_at' => now()]);
        $ch90_01 = Chapter::factory()->create(['comic_id' => $this->comicB->id, 'chapter_number' => '90.01', 'published_at' => now()]);
        $ch90_1 = Chapter::factory()->create(['comic_id' => $this->comicB->id, 'chapter_number' => '90.1', 'published_at' => now()]);
        $ch91 = Chapter::factory()->create(['comic_id' => $this->comicB->id, 'chapter_number' => 91, 'published_at' => now()]);

        // Viewing 90.01: previous must be 90, next must be 90.1
        $response = $this->get(route('chapters.show', [$this->comicB->slug, $ch90_01->slug]));
        $response->assertOk();
        $response->assertViewHas('prevChapter', fn($ch) => (float) $ch->chapter_number === 90.0);
        $response->assertViewHas('nextChapter', fn($ch) => (float) $ch->chapter_number === 90.1);

        // Viewing 90.1: previous must be 90.01, next must be 91
        $response2 = $this->get(route('chapters.show', [$this->comicB->slug, $ch90_1->slug]));
        $response2->assertOk();
        $response2->assertViewHas('prevChapter', fn($ch) => (float) $ch->chapter_number === 90.01);
        $response2->assertViewHas('nextChapter', fn($ch) => (float) $ch->chapter_number === 91.0);
    }

    public function test_decimal_chapter_route_and_numeric_slug_redirect_work_properly(): void
    {
        $ch = Chapter::factory()->create([
            'comic_id' => $this->comicB->id,
            'chapter_number' => '90.12345',
            'slug' => 'chapter-90.12345',
            'published_at' => now(),
        ]);

        // Direct canonical slug
        $this->get('/truyen/' . $this->comicB->slug . '/chapter-90.12345')
            ->assertOk();

        // Numeric slug redirect: /truyen/slug/90.12345 -> 301 redirect to /truyen/slug/chapter-90.12345
        $this->get('/truyen/' . $this->comicB->slug . '/90.12345')
            ->assertRedirect('/truyen/' . $this->comicB->slug . '/chapter-90.12345');
    }

    public function test_admin_can_create_generic_decimal_chapter_via_store_endpoint(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.comics.chapters.store', $this->comicA->id), [
                'chapter_number' => '90.12345',
                'title' => 'High Precision Chapter',
                'is_free' => true,
                'pages_raw' => "https://cdn.example.com/page1.jpg\nhttps://cdn.example.com/page2.jpg",
            ]);

        $response->assertRedirect(route('admin.comics.chapters.index', $this->comicA->id));

        $chapter = Chapter::where('comic_id', $this->comicA->id)
            ->where('chapter_number', '90.12345')
            ->first();

        $this->assertNotNull($chapter);
        $this->assertEquals(90.12345, $chapter->chapter_number);
        $this->assertSame('High Precision Chapter', $chapter->title);
        $this->assertSame('chapter-90.12345', $chapter->slug);
    }

    public function test_validation_rejects_invalid_chapter_formats(): void
    {
        $invalidFormats = [
            '90.',
            '.5',
            '90..1',
            '90.1.2',
            'abc',
            '90a.1',
            '-90.1',
            '-5',
        ];

        foreach ($invalidFormats as $inv) {
            $this->actingAs($this->admin)
                ->post(route('admin.comics.chapters.store', $this->comicA->id), [
                    'chapter_number' => $inv,
                    'title' => 'Invalid Test',
                ])
                ->assertSessionHasErrors(['chapter_number']);
        }
    }

    public function test_validation_accepts_valid_generic_decimal_chapter_numbers(): void
    {
        $validFormats = [
            '1',
            '1.1',
            '1.01',
            '1.001',
            '1.5',
            '1.25',
            '1.125',
            '10.10',
            '10.99',
            '90.1',
            '90.01',
            '90.001',
            '90.12345',
            '187.5',
            '187.25',
            '999.999999',
        ];

        foreach ($validFormats as $valid) {
            $validator = \Illuminate\Support\Facades\Validator::make(
                ['chapter_number' => $valid],
                (new \App\Http\Requests\Admin\StoreChapterRequest())->rules()
            );

            $this->assertFalse(
                $validator->fails(),
                "Failed to validate {$valid}: " . json_encode($validator->errors()->all())
            );
        }
    }
}
