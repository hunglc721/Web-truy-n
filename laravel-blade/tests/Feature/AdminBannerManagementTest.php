<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBannerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        Storage::fake('public');
    }

    public function test_admin_can_view_banners_index_and_create_banner(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.banners.index'));
        $response->assertOk();
        $response->assertSee('Quản lý Banner Hero Slider Trang Chủ');

        // Tạo banner mới bằng URL
        $storeRes = $this->actingAs($this->admin)->post(route('admin.banners.store'), [
            'title'     => 'Solo Leveling Season 2 Promo',
            'image_url' => 'https://cdn.example.com/banners/solo-s2.jpg',
            'link_url'  => 'https://example.com/truyen/solo-leveling',
            'order'     => 1,
            'is_active' => '1',
        ]);
        $storeRes->assertRedirect(route('admin.banners.index'));

        $this->assertDatabaseHas('banners', [
            'title'     => 'Solo Leveling Season 2 Promo',
            'order'     => 1,
            'is_active' => 1,
        ]);
    }

    public function test_admin_can_upload_banner_image_file(): void
    {
        $file = UploadedFile::fake()->image('banner-hero.jpg', 1200, 400);

        $response = $this->actingAs($this->admin)->post(route('admin.banners.store'), [
            'title'     => 'Tower of God New Arc',
            'image'     => $file,
            'link_url'  => 'https://example.com/truyen/tower-of-god',
            'order'     => 2,
        ]);
        $response->assertRedirect(route('admin.banners.index'));

        $banner = Banner::where('title', 'Tower of God New Arc')->first();
        $this->assertNotNull($banner);
        $this->assertStringStartsWith('banners/', $banner->image_url);
        Storage::disk('public')->assertExists($banner->image_url);
    }

    public function test_admin_can_toggle_active_and_delete_banner(): void
    {
        $banner = Banner::create([
            'title'     => 'Banner Cũ',
            'image_url' => 'https://cdn.example.com/b1.jpg',
            'is_active' => true,
        ]);

        // 1. Toggle tắt
        $toggleRes = $this->actingAs($this->admin)->patch(route('admin.banners.toggleActive', $banner));
        $toggleRes->assertRedirect();
        $banner->refresh();
        $this->assertFalse($banner->is_active);

        // 2. Xóa
        $delRes = $this->actingAs($this->admin)->delete(route('admin.banners.destroy', $banner));
        $delRes->assertRedirect();
        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }

    public function test_homepage_shows_active_banner_and_auto_hides_expired_or_scheduled_banners(): void
    {
        // 1. Banner đang hoạt động hợp lệ
        $activeBanner = Banner::create([
            'title'     => 'Banner Đang Chạy',
            'image_url' => 'https://cdn.example.com/active.jpg',
            'is_active' => true,
            'start_at'  => now()->subDay(),
            'end_at'    => now()->addDays(5),
            'order'     => 1,
        ]);

        // 2. Banner đã hết hạn (tự ẩn)
        $expiredBanner = Banner::create([
            'title'     => 'Banner Đã Hết Hạn',
            'image_url' => 'https://cdn.example.com/expired.jpg',
            'is_active' => true,
            'start_at'  => now()->subDays(10),
            'end_at'    => now()->subDay(),
            'order'     => 2,
        ]);

        // 3. Banner hẹn giờ tương lai (chưa tới giờ)
        $futureBanner = Banner::create([
            'title'     => 'Banner Hẹn Tương Lai',
            'image_url' => 'https://cdn.example.com/future.jpg',
            'is_active' => true,
            'start_at'  => now()->addDays(2),
            'end_at'    => now()->addDays(10),
            'order'     => 3,
        ]);

        // 4. Kiểm tra trang chủ
        $response = $this->get(route('home'));
        $response->assertOk();

        // Banner hợp lệ xuất hiện
        $response->assertSee('Banner Đang Chạy');

        // Banner hết hạn hoặc chưa tới giờ tự động bị ẩn
        $response->assertDontSee('Banner Đã Hết Hạn');
        $response->assertDontSee('Banner Hẹn Tương Lai');
    }
}
