<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_settings_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Cài Đặt Website');
    }

    public function test_member_cannot_open_settings_page(): void
    {
        $member = User::factory()->create(['is_admin' => false]);

        $this->actingAs($member)
            ->get('/admin/settings')
            ->assertRedirect('/');
    }

    public function test_admin_can_persist_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'WebComics VN',
                'tagline' => 'Đọc truyện mỗi ngày',
                'meta_description' => 'Website đọc truyện',
                'seo_keywords' => 'manga,manhwa',
                'facebook_url' => 'https://facebook.com/webcomics',
                'twitter_url' => '',
                'discord_url' => '',
                'google_analytics_id' => 'G-TEST123',
                'maintenance_mode' => '1',
                'maintenance_message' => 'Đang nâng cấp',
                'maintenance_ips' => '127.0.0.2',
            ])
            ->assertRedirect();

        $this->assertSame('WebComics VN', Setting::valueOf('site_name'));
        $this->assertTrue(Setting::valueOf('maintenance_mode'));
        $this->assertSame('Đang nâng cấp', Setting::valueOf('maintenance_message'));
    }

    public function test_maintenance_mode_blocks_guest_but_keeps_login_reachable(): void
    {
        Setting::putValue('maintenance_mode', true, 'bool');
        Setting::putValue('maintenance_message', 'Đang bảo trì');

        $this->get('/')
            ->assertStatus(503)
            ->assertSee('Đang bảo trì');

        $this->get('/login')->assertOk();
    }

    public function test_admin_bypasses_maintenance_mode(): void
    {
        Setting::putValue('maintenance_mode', true, 'bool');
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/genres')
            ->assertOk();
    }
}
