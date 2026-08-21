<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests cho AdminMiddleware – Task 9 normalization.
 * Xác nhận chỉ is_admin (boolean) quyết định quyền truy cập;
 * cột role không tồn tại và không được kiểm tra nữa.
 */
class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_admin_true_grants_access_to_admin_area(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
             ->get('/admin')
             ->assertOk(); // renders Admin Dashboard
    }

    public function test_is_admin_false_redirects_to_home(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
             ->get('/admin')
             ->assertRedirect('/');
    }

    public function test_unauthenticated_user_redirected_from_admin(): void
    {
        $this->get('/admin')
             ->assertRedirect(route('login'));
    }

    public function test_banned_user_without_admin_cannot_access_admin(): void
    {
        $banned = User::factory()->create([
            'is_admin'  => false,
            'banned_at' => now(),
        ]);

        $this->actingAs($banned)
             ->get('/admin')
             ->assertRedirect(route('login'));
    }

    public function test_banned_admin_is_intercepted_by_banned_middleware(): void
    {
        $bannedAdmin = User::factory()->create([
            'is_admin'  => true,
            'banned_at' => now(),
        ]);

        $this->actingAs($bannedAdmin)
             ->get('/admin')
             ->assertRedirect(route('login'));
    }

    public function test_is_admin_method_returns_correct_value(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user  = User::factory()->create(['is_admin' => false]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($user->isAdmin());
    }
}
