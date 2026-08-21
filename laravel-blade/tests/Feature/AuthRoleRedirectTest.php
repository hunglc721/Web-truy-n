<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRoleRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_uses_shared_login_and_is_redirected_to_library(): void
    {
        $member = User::factory()->create([
            'email' => 'member-login@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $this->post('/login', [
            'email' => $member->email,
            'password' => 'password',
        ])->assertRedirect(route('user.library'));

        $this->assertAuthenticatedAs($member);
    }

    public function test_admin_uses_same_login_and_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-login@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_page_does_not_ask_user_to_choose_a_role(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Không cần chọn role')
            ->assertDontSee('Moderator')
            ->assertDontSee('Editor');
    }
}
