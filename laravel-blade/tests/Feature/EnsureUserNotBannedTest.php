<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnsureUserNotBannedTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_access_protected_pages(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'banned_at' => null]);

        $response = $this->actingAs($user)->get(route('user.library'));

        $response->assertOk();
    }

    public function test_banned_user_is_logged_out_and_redirected_to_login(): void
    {
        $user = User::factory()->create([
            'is_admin'  => false,
            'banned_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('user.library'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để biết thêm chi tiết.');
        $this->assertGuest();
    }

    public function test_banned_user_making_json_request_receives_403_json(): void
    {
        $user = User::factory()->create([
            'is_admin'  => false,
            'banned_at' => now(),
        ]);

        $response = $this->actingAs($user)->json('POST', '/api/reading-history', [
            'comic_id'   => 1,
            'chapter_id' => 1,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để biết thêm chi tiết.',
            'code'    => 403,
        ]);
        $this->assertGuest();
    }

    public function test_admin_toggle_ban_invalidates_sessions_and_sets_banned_at(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false, 'banned_at' => null]);

        // Giả lập session tồn tại trong DB cho user
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->insert([
                'id'             => 'test_session_id_123',
                'user_id'        => $user->id,
                'ip_address'     => '127.0.0.1',
                'user_agent'     => 'PHPUnit',
                'payload'        => base64_encode('dummy_payload'),
                'last_activity'  => time(),
            ]);
            $this->assertDatabaseHas('sessions', ['user_id' => $user->id]);
        }

        // Admin ban user
        $response = $this->actingAs($admin)->patch(route('admin.users.toggleBan', $user));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        // User đã bị set banned_at
        $this->assertNotNull($user->fresh()->banned_at);
        $this->assertTrue($user->fresh()->isBanned());

        // Session trong DB đã bị xóa
        if (Schema::hasTable('sessions')) {
            $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        }

        // Request tiếp theo từ user đó bị đẩy ra trang login
        $userResponse = $this->actingAs($user->fresh())->get(route('user.library'));
        $userResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
