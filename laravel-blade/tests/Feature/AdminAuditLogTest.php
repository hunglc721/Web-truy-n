<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Comic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Super Admin']);
        $this->regularUser = User::factory()->create(['is_admin' => false, 'name' => 'Reader Bob']);
    }

    public function test_guest_and_non_admin_cannot_access_audit_logs(): void
    {
        $this->get(route('admin.logs.index'))->assertRedirect(route('login'));
        $this->actingAs($this->regularUser)->get(route('admin.logs.index'))->assertRedirect('/');
    }

    public function test_admin_can_view_audit_logs_and_filter(): void
    {
        $comic = Comic::factory()->create(['title' => 'Naruto Shippuden']);

        ActivityLog::create([
            'user_id'      => $this->admin->id,
            'action'       => 'admin.comic.created',
            'subject_type' => Comic::class,
            'subject_id'   => $comic->id,
            'ip_address'   => '192.168.1.100',
            'payload'      => ['title' => 'Naruto Shippuden'],
        ]);

        ActivityLog::create([
            'user_id'      => $this->regularUser->id,
            'action'       => 'auth.login',
            'ip_address'   => '10.0.0.1',
        ]);

        // 1. Xem toàn bộ log
        $response = $this->actingAs($this->admin)->get(route('admin.logs.index'));
        $response->assertOk();
        $response->assertSee('Nhật ký Hoạt động Hệ Thống');
        $response->assertSee('admin.comic.created');
        $response->assertSee('auth.login');

        // 2. Lọc theo action_group = admin.comic
        $filterRes = $this->actingAs($this->admin)->get(route('admin.logs.index', ['action_group' => 'admin.comic']));
        $filterRes->assertOk();
        $filterRes->assertSee('admin.comic.created');
        $filterRes->assertDontSee('auth.login');

        // 3. Tìm kiếm từ khóa IP
        $searchRes = $this->actingAs($this->admin)->get(route('admin.logs.index', ['q' => '192.168.1.100']));
        $searchRes->assertOk();
        $searchRes->assertSee('192.168.1.100');
    }

    public function test_admin_can_clear_audit_logs(): void
    {
        // Tạo 2 log: 1 log cũ (40 ngày trước), 1 log mới
        \Illuminate\Support\Facades\DB::table('activity_logs')->insert([
            'user_id'    => $this->admin->id,
            'action'     => 'admin.old.action',
            'created_at' => now()->subDays(40),
        ]);

        \Illuminate\Support\Facades\DB::table('activity_logs')->insert([
            'user_id'    => $this->admin->id,
            'action'     => 'admin.recent.action',
            'created_at' => now()->subDay(),
        ]);

        // Dọn dẹp log cũ hơn 30 ngày
        $clearRes = $this->actingAs($this->admin)->delete(route('admin.logs.clear'), ['days' => 30]);
        $clearRes->assertRedirect(route('admin.logs.index'));

        $this->assertDatabaseMissing('activity_logs', ['action' => 'admin.old.action']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'admin.recent.action']);
    }
}
