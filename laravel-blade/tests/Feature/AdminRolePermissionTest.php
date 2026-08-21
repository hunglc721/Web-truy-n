<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $slug): User
    {
        return User::factory()->create([
            'is_admin' => $slug === 'admin',
            'role_id' => Role::where('slug', $slug)->value('id'),
        ]);
    }

    public function test_editor_can_manage_content_but_cannot_open_users(): void
    {
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)->get(route('admin.comics.index'))->assertOk();
        $this->actingAs($editor)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_viewer_is_read_only(): void
    {
        $viewer = $this->userWithRole('viewer');

        $this->actingAs($viewer)->get(route('admin.comics.index'))->assertOk();
        $this->actingAs($viewer)->post(route('admin.comics.store'), [])->assertForbidden();
    }

    public function test_moderator_can_moderate_comments_but_cannot_manage_comics(): void
    {
        $moderator = $this->userWithRole('moderator');

        $this->actingAs($moderator)->get(route('admin.comments.index'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.comics.index'))->assertForbidden();
    }

    public function test_admin_has_full_access_even_with_legacy_is_admin_flag(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'role_id' => null]);

        $this->actingAs($admin)->get(route('admin.permissions.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk();
    }

    public function test_permission_matrix_update_changes_backend_access(): void
    {
        $admin = $this->userWithRole('admin');
        $moderatorRole = Role::where('slug', 'moderator')->firstOrFail();
        $dashboardPermission = Permission::where('slug', 'dashboard.view')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.permissions.update', $moderatorRole), [
                'permissions' => [$dashboardPermission->id],
            ])
            ->assertRedirect();

        $moderator = $this->userWithRole('moderator');
        $this->actingAs($moderator)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.comments.index'))->assertForbidden();
    }
}
