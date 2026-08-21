<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_database_backed_analytics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Comic::factory()->create(['title' => 'Analytics Comic', 'views' => 1234]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('Thống Kê & Báo Cáo')
            ->assertSee('Analytics Comic')
            ->assertSee('1,234');
    }

    public function test_member_cannot_open_admin_analytics(): void
    {
        $member = User::factory()->create(['is_admin' => false]);

        $this->actingAs($member)
            ->get('/admin/analytics')
            ->assertRedirect('/');
    }

    public function test_guest_is_redirected_to_login_from_admin_analytics(): void
    {
        $this->get('/admin/analytics')
            ->assertRedirect(route('login'));
    }
}
