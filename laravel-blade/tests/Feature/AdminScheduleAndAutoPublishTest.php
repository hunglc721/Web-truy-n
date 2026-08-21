<?php

namespace Tests\Feature;

use App\Jobs\PublishScheduledChapters;
use App\Models\Comic;
use App\Models\Chapter;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminScheduleAndAutoPublishTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Comic $comic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->comic = Comic::factory()->create(['title' => 'Solo Leveling']);
    }

    public function test_admin_can_view_schedule_dashboard_and_assign_comic_to_day(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.schedules.index'));
        $response->assertOk();
        $response->assertSee('Quản lý Lịch Phát Sóng Tuần');
        $response->assertSee('Thứ Hai');

        // Gán truyện vào Thứ Tư (day_of_week = 3) lúc 20:00
        $storeRes = $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
            'comic_id'     => $this->comic->id,
            'day_of_week'  => 3,
            'release_time' => '20:00',
        ]);
        $storeRes->assertRedirect();

        $this->assertDatabaseHas('schedules', [
            'comic_id'     => $this->comic->id,
            'day_of_week'  => 3,
            'release_time' => '20:00',
            'is_active'    => 1,
        ]);
    }

    public function test_admin_can_delete_schedule(): void
    {
        $schedule = Schedule::create([
            'comic_id'     => $this->comic->id,
            'day_of_week'  => 1,
            'release_time' => '21:00',
            'is_active'    => true,
        ]);

        $delRes = $this->actingAs($this->admin)->delete(route('admin.schedules.destroy', $schedule));
        $delRes->assertRedirect();

        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_public_schedule_reflects_assigned_comic(): void
    {
        // Gán truyện vào Thứ Sáu (day_of_week = 5)
        Schedule::create([
            'comic_id'     => $this->comic->id,
            'day_of_week'  => 5,
            'release_time' => '19:30',
            'is_active'    => true,
        ]);

        $response = $this->get(route('schedule', ['day' => 5]));
        $response->assertOk();
        $response->assertSee('Solo Leveling');
    }

    public function test_auto_publish_scheduled_chapter_appears_automatically_without_manual_intervention(): void
    {
        // 1. Tạo Chapter 1 đã xuất bản trong quá khứ
        $chap1 = Chapter::factory()->create([
            'comic_id'        => $this->comic->id,
            'chapter_number'  => 1,
            'slug'            => 'chapter-1',
            'published_at'    => now()->subDays(2),
        ]);

        // 2. Tạo Chapter 2 hẹn giờ phát hành 5 phút sau
        $chap2 = Chapter::factory()->create([
            'comic_id'        => $this->comic->id,
            'chapter_number'  => 2,
            'slug'            => 'chapter-2',
            'published_at'    => now()->addMinutes(5),
        ]);

        // 3. Trước giờ hẹn: latestChapter phải là Chapter 1
        $this->comic = $this->comic->fresh();
        $this->assertEquals(1, $this->comic->latestChapter->chapter_number);

        // Home page cached
        $homeRes = $this->get(route('home'));
        $homeRes->assertOk();

        // 4. Giả lập thời gian trôi qua 5 phút sau (tới giờ phát hành)
        $chap2->update(['published_at' => now()->subMinute()]);

        // 5. Chạy command tự động publish
        $exitCode = Artisan::call('chapters:publish-scheduled');
        $this->assertEquals(0, $exitCode);

        // 6. Sau khi tới giờ: latestChapter tự động thành Chapter 2 mà không cần thao tác tay
        $this->comic = $this->comic->fresh();
        $this->assertEquals(2, $this->comic->latestChapter->chapter_number);

        // Cache home được làm mới
        $this->assertNull(Cache::get('home.latest'));
    }
}
