<?php

namespace Tests\Feature;

use App\Models\{Comic, UploadTask, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Bus, File, Storage};
use Illuminate\Support\Str;
use Tests\TestCase;

class BackgroundUploadTaskTest extends TestCase
{
    use RefreshDatabase;
    private User $admin;
    private Comic $comic;
    private string $worker;
    private string $gif;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->comic = Comic::factory()->create();
        $this->worker = (string) Str::uuid();
        $this->gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        Storage::fake('public');
        Bus::fake();
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        foreach (UploadTask::all() as $task) File::deleteDirectory(storage_path('app/bulk-chapter-uploads/' . $task->upload_session_id));
        parent::tearDown();
    }

    private function createTask(): array
    {
        return $this->postJson('/admin/upload-tasks', ['comic_id' => $this->comic->id, 'conflict_mode' => 'skip', 'chapters' => [
            ['key' => 'chapter-0', 'number' => '90.25', 'title' => 'Decimal', 'files' => [['name' => '1.gif', 'size' => strlen($this->gif)]]],
        ]])->assertOk()->json('task');
    }

    private function control(array $task, string $action, array $extra = [])
    {
        return $this->postJson('/admin/upload-tasks/' . $task['id'] . '/control', ['action' => $action, 'worker_id' => $this->worker] + $extra);
    }

    private function upload(array $task, string $action, array $extra = [])
    {
        return $this->post('/admin/upload-tasks/' . $task['id'] . '/upload', [
            'bulk_action' => $action, 'worker_id' => $this->worker, 'session' => $task['upload_session_id'],
            'chapter_key' => 'chapter-0', 'chapter_number' => '90.25', 'page_count' => 1,
            'current_batch' => 1, 'total_batches' => 1,
        ] + $extra, ['Accept' => 'application/json']);
    }

    private function chunk(array $task)
    {
        return $this->upload($task, 'chunk', ['files' => [UploadedFile::fake()->createWithContent('1.gif', $this->gif)], 'page_indexes' => [0], 'checksums' => [hash('sha256', $this->gif)]]);
    }

    public function test_creation_and_active_snapshot_are_shared_across_tabs_without_duplicate_task(): void
    {
        $task = $this->createTask();
        $this->assertSame($task['id'], $this->createTask()['id']);
        $this->getJson('/admin/upload-tasks/active?comic_id=' . $this->comic->id)->assertJsonPath('task.id', $task['id']);
        $this->assertDatabaseCount('upload_tasks', 1);
        $this->get('/admin/upload-worker')->assertOk();
    }

    public function test_authorization_blocks_guest_member_and_another_admin(): void
    {
        $task = $this->createTask();
        foreach ([User::factory()->create(['is_admin' => true]), User::factory()->create(['is_admin' => false])] as $other) {
            $this->actingAs($other);
            $this->getJson('/admin/upload-tasks/' . $task['id'])->assertForbidden();
            foreach (['claim', 'heartbeat', 'cancel', 'release', 'error'] as $action) $this->control($task, $action)->assertForbidden();
            $this->chunk($task)->assertForbidden();
        }
        $this->getJson('/admin/upload-tasks/active')->assertForbidden();
        $this->getJson('/admin/upload-worker')->assertForbidden();
        $this->postJson('/admin/upload-tasks', [])->assertForbidden();
        auth()->forgetGuards();
        $this->getJson('/admin/upload-tasks/active')->assertUnauthorized();
    }

    public function test_lease_heartbeat_staleness_and_resume_receipts(): void
    {
        $task = $this->createTask();
        $this->control($task, 'claim')->assertOk();
        $this->chunk($task)->assertOk();
        $original = $this->worker;
        $this->worker = (string) Str::uuid();
        $this->control($task, 'claim')->assertStatus(409);
        $this->chunk($task)->assertStatus(409);
        $this->travel(31)->seconds();
        $this->getJson('/admin/upload-tasks/' . $task['id'])->assertJsonPath('task.status', 'waiting_for_client')->assertJsonPath('task.uploaded_bytes', strlen($this->gif));
        $this->control($task, 'claim')->assertOk()->assertJsonPath('task.session_state.pages.chapter-0.0.sha256', hash('sha256', $this->gif));
        $this->control($task, 'heartbeat')->assertOk()->assertJsonPath('task.worker_alive', true);
        $this->worker = $original;
        $this->chunk($task)->assertStatus(409);
    }

    public function test_progress_is_server_authoritative_retries_are_idempotent_and_completion_notifies_once(): void
    {
        $task = $this->createTask();
        $this->control($task, 'claim')->assertOk();
        $this->chunk($task)->assertOk()->assertJsonPath('task.uploaded_bytes', strlen($this->gif))->assertJsonPath('task.current_chapter_number', '90.25');
        $this->chunk($task)->assertOk()->assertJsonPath('task.uploaded_files', 1)->assertJsonPath('task.uploaded_bytes', strlen($this->gif));
        $this->upload($task, 'complete')->assertStatus(422);
        $this->upload($task, 'finalize')->assertOk()->assertJsonPath('task.completed_chapters', 1);
        $this->upload($task, 'finalize')->assertOk()->assertJsonPath('task.completed_chapters', 1);
        $this->upload($task, 'complete')->assertOk()->assertJsonPath('task.status', 'completed');
        $this->upload($task, 'complete')->assertOk();
        $this->getJson('/admin/upload-tasks/' . $task['id'])->assertJsonPath('task.status', 'completed');
        $this->assertSame(1, $this->admin->notifications()->where('id', $task['id'])->count());
        $this->getJson('/user/notifications/header')->assertJsonPath('notifications.0.data.title', 'Upload hoàn tất')->assertJsonPath('notifications.0.data.chapter_number', '90.25');
        $chapter = $this->comic->chapters()->firstOrFail();
        $this->assertSame('90.25', (string) $chapter->chapter_number);
        $this->assertSame($this->gif, Storage::disk('public')->get($chapter->pages[0]));
    }

    public function test_error_release_and_cancel_persist_and_prevent_more_uploads(): void
    {
        $task = $this->createTask();
        $this->control($task, 'claim')->assertOk();
        $this->chunk($task)->assertOk();
        $this->control($task, 'error', ['error' => ['message' => 'Broken batch', 'httpStatus' => 422, 'chapterNumber' => '90.25']])->assertOk();
        $this->getJson('/admin/upload-tasks/' . $task['id'])->assertJsonPath('task.error_context.httpStatus', 422);
        $this->control($task, 'release')->assertJsonPath('task.status', 'waiting_for_client');
        $this->control($task, 'claim')->assertOk();
        $this->upload($task, 'finalize')->assertOk();
        $this->control($task, 'cancel')->assertJsonPath('task.status', 'cancelled');
        $this->chunk($task)->assertStatus(409);
        $this->upload($task, 'finalize')->assertStatus(409);
        $this->assertSame(1, $this->comic->chapters()->count());
        $this->postJson(route('admin.comics.chapters.store', $this->comic), ['bulk_action' => 'complete', 'session' => $task['upload_session_id']])->assertStatus(409);
    }

    public function test_guest_and_member_layouts_do_not_subscribe_to_tasks(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get('/')->assertOk()->assertDontSee('admin-upload-progress.js');
        auth()->forgetGuards();
        $this->get('/')->assertOk()->assertDontSee('admin-upload-progress.js');
    }

    public function test_existing_chapters_are_skipped_without_image_validation_or_upload_bytes(): void
    {
        \App\Models\Chapter::factory()->create(['comic_id' => $this->comic->id, 'chapter_number' => '90.25']);
        $task = $this->postJson('/admin/upload-tasks', ['comic_id' => $this->comic->id, 'conflict_mode' => 'skip', 'chapters' => [
            ['key' => 'chapter-0', 'number' => '90.25', 'files' => [['name' => 'invalid.gif', 'size' => 0]]],
        ]])->assertOk()->assertJsonPath('task.skipped_chapters', 1)->assertJsonPath('task.total_bytes', 0)->assertJsonPath('task.total_files', 0)->json('task');
        $this->control($task, 'claim')->assertOk();
        $this->chunk($task)->assertStatus(422);
        $this->upload($task, 'complete')->assertOk()->assertJsonPath('task.completed_chapters', 0);
    }

    public function test_heartbeat_cannot_forge_progress_and_expired_task_cannot_claim(): void
    {
        $task = $this->createTask();
        $this->control($task, 'claim')->assertOk();
        $this->control($task, 'heartbeat', ['uploaded_bytes' => 999999, 'completed_chapters' => 99])->assertJsonPath('task.uploaded_bytes', 0)->assertJsonPath('task.completed_chapters', 0);
        $this->travel(25)->hours();
        $this->control($task, 'claim')->assertStatus(409);
    }
}
