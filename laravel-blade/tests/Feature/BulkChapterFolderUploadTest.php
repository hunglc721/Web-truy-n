<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkChapterFolderUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Comic $comic;
    private string $gifBytes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->comic = Comic::factory()->create();
        $this->gifBytes = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        File::deleteDirectory(storage_path('app/bulk-chapter-uploads'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/bulk-chapter-uploads'));
        parent::tearDown();
    }

    public function test_admin_can_upload_large_folder_flow_in_chunks_and_preserve_original_bytes(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\App\Jobs\GenerateChapterReaderVariants::class]);
        Storage::fake('public');

        $session = $this->startBulkUploadSession();
        $hash = hash('sha256', $this->gifBytes);

        $chunkResponse = $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.comics.chapters.store', $this->comic->id), [
                'bulk_action' => 'chunk',
                'session' => $session,
                'chapter_key' => 'chapter-0000',
                'files' => [
                    $this->uploadedGif('10.gif'),
                    $this->uploadedGif('01.gif'),
                ],
                'page_indexes' => [1, 0],
                'checksums' => [$hash, $hash],
            ]);

        $chunkResponse
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('accepted', 2)
            ->assertJsonPath('chapter_uploaded_pages', 2)
            ->assertJsonStructure(['server_processing_ms']);

        $chapterStageDir = storage_path('app/bulk-chapter-uploads/' . $session . '/chapters/chapter-0000');
        $this->assertDirectoryExists($chapterStageDir);

        $finalizeResponse = $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.comics.chapters.store', $this->comic->id), [
                'bulk_action' => 'finalize',
                'session' => $session,
                'chapter_key' => 'chapter-0000',
                'chapter_number' => 140,
                'title' => 'Opening the Decisive Battle',
                'page_count' => 2,
            ]);

        $finalizeResponse
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('chapter_number', 140)
            ->assertJsonPath('pages', 2)
            ->assertJsonStructure(['storage_mode', 'server_processing_ms']);

        // Finalize must remove private staging immediately after a verified transfer.
        $this->assertDirectoryDoesNotExist($chapterStageDir);

        $chapter = Chapter::query()
            ->where('comic_id', $this->comic->id)
            ->where('chapter_number', 140)
            ->firstOrFail();

        $this->assertSame('ready', $chapter->processing_status);
        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\GenerateChapterReaderVariants::class,
            fn ($job) => $job->chapterId === $chapter->id);
        $this->assertSame('Opening the Decisive Battle', $chapter->title);
        $this->assertCount(2, $chapter->pages);
        $this->assertCount(2, $chapter->page_dimensions);
        $this->assertStringEndsWith('/001.gif', $chapter->pages[0]);
        $this->assertStringEndsWith('/002.gif', $chapter->pages[1]);

        Storage::disk('public')->assertExists($chapter->pages[0]);
        Storage::disk('public')->assertExists($chapter->pages[1]);
        $this->assertSame($this->gifBytes, Storage::disk('public')->get($chapter->pages[0]));
        $this->assertSame($hash, hash('sha256', Storage::disk('public')->get($chapter->pages[0])));

        $completeResponse = $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.comics.chapters.store', $this->comic->id), [
                'bulk_action' => 'complete',
                'session' => $session,
            ]);

        $completeResponse
            ->assertOk()
            ->assertJsonPath('chapters_created', 1);

        $this->assertDirectoryDoesNotExist(storage_path('app/bulk-chapter-uploads/' . $session));
    }

    public function test_bulk_upload_can_replace_a_retried_page_without_corrupting_staging(): void
    {
        Storage::fake('public');

        $session = $this->startBulkUploadSession();
        $hash = hash('sha256', $this->gifBytes);

        foreach (['first.gif', 'retry.gif'] as $name) {
            $response = $this->actingAs($this->admin)
                ->withHeader('Accept', 'application/json')
                ->post(route('admin.comics.chapters.store', $this->comic->id), [
                    'bulk_action' => 'chunk',
                    'session' => $session,
                    'chapter_key' => 'chapter-0000',
                    'files' => [$this->uploadedGif($name)],
                    'page_indexes' => [0],
                    'checksums' => [$hash],
                ]);

            $response
                ->assertOk()
                ->assertJsonPath('chapter_uploaded_pages', 1);
        }

        $manifestPath = storage_path('app/bulk-chapter-uploads/' . $session . '/chapters/chapter-0000/manifest.json');
        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        $this->assertIsArray($manifest);
        $this->assertSame('retry.gif', $manifest['pages']['0']['original_name']);
        $this->assertSame($hash, hash_file('sha256', $manifest['pages']['0']['path']));
    }

    public function test_bulk_upload_rejects_checksum_mismatch_before_storing_file(): void
    {
        Storage::fake('public');
        $session = $this->startBulkUploadSession();

        $response = $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.comics.chapters.store', $this->comic->id), [
                'bulk_action' => 'chunk',
                'session' => $session,
                'chapter_key' => 'chapter-0000',
                'files' => [$this->uploadedGif('001.gif')],
                'page_indexes' => [0],
                'checksums' => [str_repeat('0', 64)],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['checksums']);

        $this->assertDatabaseCount('chapters', 0);
        $chapterDir = storage_path('app/bulk-chapter-uploads/' . $session . '/chapters/chapter-0000');
        $this->assertDirectoryDoesNotExist($chapterDir);

        $incomingDir = storage_path('app/bulk-chapter-uploads/' . $session . '/incoming');
        $this->assertDirectoryDoesNotExist($incomingDir);
    }

    public function test_bulk_upload_session_cannot_be_reused_by_another_admin(): void
    {
        $session = $this->startBulkUploadSession();
        $otherAdmin = User::factory()->create(['is_admin' => true]);
        $hash = hash('sha256', $this->gifBytes);

        $response = $this->actingAs($otherAdmin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.comics.chapters.store', $this->comic->id), [
                'bulk_action' => 'chunk',
                'session' => $session,
                'chapter_key' => 'chapter-0000',
                'files' => [$this->uploadedGif('001.gif')],
                'page_indexes' => [0],
                'checksums' => [$hash],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['session']);
    }

    private function startBulkUploadSession(): string
    {
        $response = $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.comics.chapters.store', $this->comic->id), [
                'bulk_action' => 'start',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $session = (string) $response->json('session');
        $this->assertNotSame('', $session);

        return $session;
    }

    private function uploadedGif(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'comicx_bulk_');
        file_put_contents($path, $this->gifBytes);

        return new UploadedFile($path, $name, 'image/gif', null, true);
    }
}
