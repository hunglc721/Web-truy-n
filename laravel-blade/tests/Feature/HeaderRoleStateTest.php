<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderRoleStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_header_shows_login_and_register_only(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-auth-state="guest"', false)
            ->assertSee('Đăng Nhập')
            ->assertSee('Đăng Ký')
            ->assertDontSee('🛡️ Quản Trị');
    }

    public function test_member_header_shows_account_and_logout_without_admin_link(): void
    {
        $member = User::factory()->create([
            'name' => 'Reader One',
            'is_admin' => false,
        ]);

        $this->actingAs($member)
            ->get('/')
            ->assertOk()
            ->assertSee('data-auth-state="member"', false)
            ->assertSee('Reader One')
            ->assertSee('Đăng Xuất')
            ->assertDontSee('🛡️ Quản Trị');
    }

    public function test_admin_header_links_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin One',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSee('data-auth-state="admin"', false)
            ->assertSee('🛡️ Quản Trị')
            ->assertSee(route('admin.dashboard'), false);
    }

    public function test_public_layout_uses_persisted_site_name(): void
    {
        Setting::putValue('site_name', 'Truyen Cua Toi');
        Setting::putValue('tagline', 'Đọc truyện mỗi tối');

        $this->get('/')
            ->assertOk()
            ->assertSee('Truyen Cua Toi')
            ->assertSee('Đọc truyện mỗi tối');
    }
}
