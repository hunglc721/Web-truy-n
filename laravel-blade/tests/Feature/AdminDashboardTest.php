<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Boss']);
        $this->regularUser = User::factory()->create(['is_admin' => false, 'name' => 'John Reader']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_user_is_forbidden_and_redirected(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.dashboard'));
        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }

    public function test_admin_can_view_dashboard_with_realtime_statistics_and_logs(): void
    {
        // 1. Chuẩn bị dữ liệu mẫu
        $comic = Comic::factory()->create([
            'title' => 'Solo Leveling',
            'views' => 15000,
        ]);

        Chapter::factory()->create([
            'comic_id'       => $comic->id,
            'chapter_number' => 1,
            'slug'           => 'chapter-1',
            'published_at'   => now()->subDay(),
        ]);

        Comment::create([
            'comic_id' => $comic->id,
            'user_id'  => $this->regularUser->id,
            'content'  => 'Truyện hay quá!',
            'status'   => Comment::STATUS_PENDING,
        ]);

        Report::create([
            'comic_id'    => $comic->id,
            'report_type' => Report::TYPE_BROKEN_IMAGE,
            'description' => 'Trang 3 bị lỗi ảnh không tải được.',
            'status'      => Report::STATUS_PENDING,
        ]);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'action'  => 'admin.comic.created',
            'subject_type' => Comic::class,
            'subject_id'   => $comic->id,
        ]);

        // 2. Truy cập Dashboard
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertOk();

        // 3. Kiểm tra các thành phần giao diện & số liệu
        $response->assertSee('Bảng Điều Khiển Tổng Quan');
        $response->assertSee('Solo Leveling');
        $response->assertSee('15,000'); // Lượt xem
        $response->assertSee('admin.comic.created'); // Log
        $response->assertSee('CẦN XỬ LÝ'); // Badge chờ duyệt bình luận
    }
}
