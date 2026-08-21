<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Xác nhận khu quản trị hỗ trợ Admin legacy (is_admin) và các staff role có dashboard.view.
 * Member/guest/banned vẫn bị chặn.
 */
class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_is_admin_true_grants_access_to_admin_area(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_editor_role_can_access_admin_dashboard(): void
    {
        $editor = User::factory()->create([
            'is_admin' => false,
            'role_id' => Role::where('slug', 'editor')->value('id'),
        ]);

        $this->actingAs($editor)->get('/admin')->assertOk();
    }

    public function test_member_redirects_to_home(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'role_id' => Role::where('slug', 'member')->value('id'),
        ]);

        $this->actingAs($user)->get('/admin')->assertRedirect('/');
    }

    public function test_unauthenticated_user_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_banned_member_cannot_access_admin(): void
    {
        $banned = User::factory()->create([
            'is_admin' => false,
            'role_id' => Role::where('slug', 'member')->value('id'),
            'banned_at' => now(),
        ]);

        $this->actingAs($banned)->get('/admin')->assertRedirect(route('login'));
    }

    public function test_banned_admin_is_intercepted_by_banned_middleware(): void
    {
        $bannedAdmin = User::factory()->create([
            'is_admin' => true,
            'banned_at' => now(),
        ]);

        $this->actingAs($bannedAdmin)->get('/admin')->assertRedirect(route('login'));
    }

    public function test_is_admin_method_remains_backward_compatible(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create([
            'is_admin' => false,
            'role_id' => Role::where('slug', 'member')->value('id'),
        ]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($user->isAdmin());
    }
}
