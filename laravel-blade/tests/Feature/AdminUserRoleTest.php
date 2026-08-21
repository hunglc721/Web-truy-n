<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.updateRole', $admin), ['role' => 'member'])
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
        $member = User::factory()->create([
            'is_admin' => false,
            'role_id' => Role::where('slug', 'member')->value('id'),
        ]);

        // Thực hiện bằng một admin thứ hai tạm thời rồi xóa để kiểm tra logic last-admin
        $actor = User::factory()->create([
            'is_admin' => true,
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
        $actor->delete();

        $this->actingAs($admin)
            ->patch(route('admin.users.updateRole', $member), ['role' => 'editor'])
            ->assertRedirect();

        $this->assertSame('editor', $member->fresh()->roleSlug());
    }

    public function test_admin_can_assign_editor_role_to_member(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create([
            'is_admin' => false,
            'role_id' => Role::where('slug', 'member')->value('id'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.updateRole', $member), ['role' => 'editor'])
            ->assertRedirect();

        $member->refresh();
        $this->assertSame('editor', $member->roleSlug());
        $this->assertFalse($member->is_admin);
    }

    public function test_admin_account_cannot_be_banned(): void
    {
        $actor = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create([
            'is_admin' => true,
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);

        $this->actingAs($actor)
            ->patch(route('admin.users.toggleBan', $target))
            ->assertSessionHas('error');

        $this->assertNull($target->fresh()->banned_at);
    }
}
