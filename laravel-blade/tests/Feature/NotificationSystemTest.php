<?php

namespace Tests\Feature;

use App\Jobs\SendAdminBroadcastNotifications;
use App\Jobs\SendChapterFollowerNotifications;
use App\Models\Announcement;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Library;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_receive_emergency_broadcast(): void
    {
        Announcement::create([
            'title' => 'Cảnh báo khẩn',
            'message' => 'Hệ thống sẽ bảo trì.',
            'severity' => 'emergency',
            'audience' => 'all',
            'show_banner' => true,
            'is_dismissible' => false,
            'is_active' => true,
            'starts_at' => now()->subMinute(),
        ]);

        $this->getJson('/api/announcements/active')
            ->assertOk()
            ->assertJsonPath('announcements.0.title', 'Cảnh báo khẩn')
            ->assertJsonPath('announcements.0.severity', 'emergency');
    }

    public function test_guest_does_not_receive_authenticated_only_broadcast(): void
    {
        Announcement::create([
            'title' => 'Nội bộ thành viên',
            'message' => 'Chỉ user đăng nhập thấy.',
            'severity' => 'info',
            'audience' => 'authenticated',
            'show_banner' => true,
            'is_active' => true,
            'starts_at' => now()->subMinute(),
        ]);

        $this->getJson('/api/announcements/active')
            ->assertOk()
            ->assertJsonCount(0, 'announcements');
    }

    public function test_dismissible_announcement_stays_hidden_in_session(): void
    {
        $announcement = Announcement::create([
            'title' => 'Thông báo thường',
            'message' => 'Có thể đóng.',
            'severity' => 'info',
            'audience' => 'all',
            'show_banner' => true,
            'is_dismissible' => true,
            'is_active' => true,
            'starts_at' => now()->subMinute(),
        ]);

        $this->postJson(route('announcements.dismiss', $announcement))->assertOk();
        $this->getJson('/api/announcements/active')->assertJsonCount(0, 'announcements');
    }

    public function test_followed_comic_new_chapter_notification_is_idempotent(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        Library::create([
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'status' => 'reading',
            'added_at' => now(),
        ]);

        $chapter = Chapter::create([
            'comic_id' => $comic->id,
            'chapter_number' => 99,
            'title' => 'Chapter 99',
            'slug' => 'chapter-99',
            'pages' => ['https://example.com/1.jpg'],
            'published_at' => now()->subSecond(),
            'is_free' => true,
            'processing_status' => 'ready',
        ]);

        (new SendChapterFollowerNotifications($chapter))->handle();
        (new SendChapterFollowerNotifications($chapter))->handle();

        $this->assertSame(1, $user->fresh()->notifications()->count());
        $this->assertDatabaseCount('chapter_notification_receipts', 1);
        $this->assertSame('new_chapter', $user->fresh()->notifications()->first()->data['type']);
    }

    public function test_admin_can_create_emergency_broadcast_and_queue_inbox_delivery(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.notifications.store'), [
                'title' => 'Khẩn cấp toàn hệ thống',
                'message' => 'Vui lòng lưu công việc.',
                'severity' => 'emergency',
                'audience' => 'all',
                'show_banner' => '1',
                'send_to_inbox' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('announcements', [
            'title' => 'Khẩn cấp toàn hệ thống',
            'severity' => 'emergency',
            'audience' => 'all',
            'show_banner' => 1,
            'send_to_inbox' => 1,
        ]);

        Queue::assertPushed(SendAdminBroadcastNotifications::class);
    }
}
