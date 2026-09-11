<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'is_admin' => true,
            'name' => 'Admin Boss',
        ]);
    }

    public function test_sidebar_displays_all_grouped_dropdowns(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertOk();

        // Kiểm tra các nhóm dropdown cha
        $response->assertSee('📚 Quản lý Truyện');
        $response->assertSee('🏷️ Phân loại & Tác giả', false);
        $response->assertSee('💬 Tương tác & Báo cáo', false);
        $response->assertSee('📅 Vận hành');
        $response->assertSee('👥 Người dùng');
        $response->assertSee('⚙️ Hệ thống');

        // Kiểm tra các mục con bên trong
        $response->assertSee(route('admin.comics.index'));
        $response->assertSee(route('admin.chapters.index'));
        $response->assertSee(route('admin.storyRequests.index'));
        $response->assertSee(route('admin.genres.index'));
        $response->assertSee(route('admin.tags.index'));
        $response->assertSee(route('admin.authors.index'));
        $response->assertSee(route('admin.comments.index'));
        $response->assertSee(route('admin.reports.index'));
        $response->assertSee(route('admin.schedules.index'));
        $response->assertSee(route('admin.banners.index'));
        $response->assertSee(route('admin.users.index'));
        $response->assertSee(route('admin.permissions.index'));
        $response->assertSee(route('admin.notifications.index'));
        $response->assertSee(route('admin.logs.index'));
        $response->assertSee(route('admin.settings.index'));
    }

    public function test_comic_dropdown_auto_expands_when_on_chapters_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.chapters.index'));
        $response->assertOk();

        $content = $response->getContent();
        // Dropdown Quản lý Truyện phải có class open và has-active
        $this->assertStringContainsString('sidebar-dropdown open has-active', $content);
        $this->assertStringContainsString('📚 Quản lý Truyện', $content);
    }

    public function test_taxonomy_dropdown_auto_expands_when_on_genres_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.genres.index'));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('sidebar-dropdown open has-active', $content);
        $this->assertStringContainsString('🏷️ Phân loại & Tác giả', $content);
    }

    public function test_system_dropdown_auto_expands_when_on_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('sidebar-dropdown open has-active', $content);
        $this->assertStringContainsString('⚙️ Hệ thống', $content);
    }
}
