<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\BrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class SiteBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function saveSettings(array $data = [])
    {
        return $this->post(route('admin.settings.update'), array_merge([
            '_method' => 'PUT', 'site_name' => 'Comicx Test',
        ], $data));
    }

    public function test_logo_upload_stores_random_relative_path_and_file(): void
    {
        $this->saveSettings(['site_logo' => UploadedFile::fake()->image('my-logo.png')])->assertSessionHasNoErrors();
        $path = Setting::valueOf('site_logo');
        $this->assertMatchesRegularExpression('#^branding/logo/[a-zA-Z0-9]+\.png$#', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_favicon_upload_stores_relative_path_and_file(): void
    {
        $this->saveSettings(['site_favicon' => UploadedFile::fake()->image('favicon.png')])->assertSessionHasNoErrors();
        $path = Setting::valueOf('site_favicon');
        $this->assertMatchesRegularExpression('#^branding/favicon/[a-zA-Z0-9]+\.png$#', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_settings_page_previews_current_logo(): void
    {
        Setting::putValue('site_logo', 'branding/logo/current.png');
        $this->get(route('admin.settings.index'))->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('id="site_logo-preview" src="'.Storage::disk('public')->url('branding/logo/current.png').'"', false);
    }

    public function test_admin_sidebar_renders_uploaded_logo(): void
    {
        $this->saveSettings(['site_logo' => UploadedFile::fake()->image('logo.png')]);
        $this->get(route('admin.settings.index'))->assertOk()->assertSee(
            '<img src="'.Storage::disk('public')->url(Setting::valueOf('site_logo')).'" alt="Comicx Test" width="36" height="36"', false
        );
    }

    public function test_public_header_renders_uploaded_logo(): void
    {
        $this->saveSettings(['site_logo' => UploadedFile::fake()->image('logo.png')]);
        $this->get('/')->assertOk()->assertSee(
            '<img src="'.Storage::disk('public')->url(Setting::valueOf('site_logo')).'" alt="Comicx Test" width="40" height="40"', false
        );
    }

    public function test_site_name_updates_public_and_admin_sidebar_and_title_without_cache_clear(): void
    {
        $this->get('/')->assertOk();
        $this->get(route('admin.settings.index'))->assertOk();
        $this->saveSettings(['site_name' => 'My Comicx']);
        $this->get('/')->assertOk()->assertSee('<span class="logo-text">My Comicx</span>', false);
        $this->get(route('admin.settings.index'))->assertOk()
            ->assertSee('<div class="sidebar-brand-text">My Comicx</div>', false)
            ->assertSee('<title>Cài Đặt Website — My Comicx</title>', false);
    }

    public function test_both_layouts_prefer_favicon_over_logo(): void
    {
        Setting::putValue('site_logo', 'branding/logo/logo.png');
        Setting::putValue('site_favicon', 'branding/favicon/icon.png');
        foreach (['/', route('admin.settings.index')] as $route) {
            $this->get($route)->assertOk()->assertSee('<link rel="icon" href="'.Storage::disk('public')->url('branding/favicon/icon.png').'"', false);
        }
    }

    public function test_favicon_falls_back_to_logo_in_both_layouts(): void
    {
        Setting::putValue('site_logo', 'branding/logo/logo.png');
        foreach (['/', route('admin.settings.index')] as $route) {
            $this->get($route)->assertOk()->assertSee('<link rel="icon" href="'.Storage::disk('public')->url('branding/logo/logo.png').'"', false);
        }
    }

    public function test_fresh_install_keeps_default_wc_branding(): void
    {
        $this->get('/')->assertOk()->assertSee('id="logo-grad"', false)->assertSee('>WC</text>', false)
            ->assertSee('<link rel="icon" href="'.asset('images/default-brand.svg').'"', false);
        $this->get(route('admin.settings.index'))->assertOk()->assertSee('>WC</div>', false);
        $this->assertFileExists(public_path('images/default-brand.svg'));
    }

    public function test_replacement_removes_old_owned_files_after_success(): void
    {
        foreach (['site_logo' => 'logo', 'site_favicon' => 'favicon'] as $key => $directory) {
            $oldPath = "branding/{$directory}/old.png";
            Storage::disk('public')->put($oldPath, 'old');
            Setting::putValue($key, $oldPath);
            Setting::valueOf($key); // Warm the same cache used by the layouts.
            $this->saveSettings([$key => UploadedFile::fake()->image('new.png')])->assertSessionHasNoErrors();
            $newPath = Setting::valueOf($key);
            $this->assertNotSame($oldPath, $newPath);
            Storage::disk('public')->assertExists($newPath);
            Storage::disk('public')->assertMissing($oldPath);
        }
    }

    public function test_text_only_update_preserves_branding_and_existing_settings(): void
    {
        Setting::putValue('site_logo', 'branding/logo/keep.png');
        Setting::putValue('site_favicon', 'branding/favicon/keep.png');
        $this->saveSettings(['tagline' => 'Đọc vui', 'facebook_url' => 'https://example.com', 'maintenance_mode' => true,
            'maintenance_message' => 'Bảo trì', 'maintenance_ips' => '127.0.0.2'])->assertSessionHasNoErrors();
        $this->assertSame('branding/logo/keep.png', Setting::valueOf('site_logo'));
        $this->assertSame('branding/favicon/keep.png', Setting::valueOf('site_favicon'));
        $this->assertSame('Đọc vui', Setting::valueOf('tagline'));
        $this->assertSame('https://example.com', Setting::valueOf('facebook_url'));
        $this->assertTrue(Setting::valueOf('maintenance_mode'));
        $this->assertSame('Bảo trì', Setting::valueOf('maintenance_message'));
    }

    public function test_fake_jpeg_and_svg_are_rejected_without_files_or_settings_changes(): void
    {
        foreach (['fake.jpg' => '<?php echo "unsafe";', 'fake.svg' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'] as $name => $content) {
            $this->saveSettings(['site_logo' => UploadedFile::fake()->createWithContent($name, $content)])
                ->assertSessionHasErrors('site_logo');
        }
        $this->assertNull(Setting::valueOf('site_logo'));
        $this->assertSame([], Storage::disk('public')->allFiles('branding'));
    }

    public function test_oversized_uploads_are_rejected(): void
    {
        $this->saveSettings([
            'site_logo' => UploadedFile::fake()->image('logo.png')->size(2049),
            'site_favicon' => UploadedFile::fake()->image('icon.png')->size(513),
        ])->assertSessionHasErrors(['site_logo', 'site_favicon']);
        $this->assertSame([], Storage::disk('public')->allFiles('branding'));
    }

    public function test_removal_restores_fallback_and_only_deletes_owned_branding(): void
    {
        Storage::disk('public')->put('branding/logo/old.png', 'old');
        Storage::disk('public')->put('covers/keep.png', 'keep');
        Setting::putValue('site_logo', '/storage/branding/logo/old.png');
        Setting::putValue('site_favicon', 'covers/keep.png');
        $this->saveSettings(['remove_site_logo' => '1', 'remove_site_favicon' => '1'])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing('branding/logo/old.png');
        Storage::disk('public')->assertExists('covers/keep.png');
        $this->assertNull(Setting::valueOf('site_logo'));
        $this->assertNull(Setting::valueOf('site_favicon'));
        $this->get('/')->assertSee('id="logo-grad"', false);
    }

    public function test_failed_settings_write_rolls_back_and_cleans_new_uploads_only(): void
    {
        Setting::putValue('site_name', 'Before');
        Setting::putValue('site_logo', 'branding/logo/old.png');
        Storage::disk('public')->put('branding/logo/old.png', 'old');
        $event = 'eloquent.saving: '.Setting::class;
        Event::listen($event, function (Setting $setting) {
            if ($setting->key === 'site_favicon') {
                throw new RuntimeException('Simulated settings write failure');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->saveSettings(['site_logo' => UploadedFile::fake()->image('new.png'), 'site_favicon' => UploadedFile::fake()->image('new.png')]);
            $this->fail('Expected settings write failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated settings write failure', $exception->getMessage());
        } finally {
            Event::forget($event);
        }
        $this->assertSame('Before', Setting::valueOf('site_name'));
        $this->assertSame('branding/logo/old.png', Setting::valueOf('site_logo'));
        $this->assertSame(['branding/logo/old.png'], Storage::disk('public')->allFiles('branding'));
    }

    public function test_cleanup_preserves_a_file_still_used_by_the_other_branding_setting(): void
    {
        Storage::disk('public')->put('branding/logo/shared.png', 'shared');
        Setting::putValue('site_logo', '/storage/branding/logo/shared.png');
        Setting::putValue('site_favicon', 'branding/logo/shared.png');
        $this->saveSettings(['remove_site_logo' => '1'])->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists('branding/logo/shared.png');
        $this->assertSame('branding/logo/shared.png', Setting::valueOf('site_favicon'));
    }

    public function test_url_resolver_supports_legacy_external_and_relative_paths_safely(): void
    {
        $branding = app(BrandingService::class);
        foreach (['branding/logo/a.png', '/storage/branding/logo/a.png'] as $path) {
            $this->assertSame(Storage::disk('public')->url('branding/logo/a.png'), $branding->url($path));
        }
        foreach (['http://example.com/logo.png', 'https://example.com/logo.png'] as $url) {
            $this->assertSame($url, $branding->url($url));
        }
        foreach ([null, '', '../secret', 'branding/logo/../../secret', 'javascript:alert(1)', 'F:\\logo.png', '//example.com/logo.png'] as $invalid) {
            $this->assertNull($branding->url($invalid));
        }
    }

    public function test_member_cannot_upload_branding(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->saveSettings(['site_logo' => UploadedFile::fake()->image('logo.png')])->assertRedirect('/');
        $this->assertNull(Setting::valueOf('site_logo'));
        $this->assertSame([], Storage::disk('public')->allFiles('branding'));
    }

    public function test_png_based_ico_upload_and_corrupt_icon_validation(): void
    {
        $image = UploadedFile::fake()->image('icon.png', 32, 32);
        $png = file_get_contents($image->getRealPath());
        $ico = pack('vvv', 0, 1, 1).pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($png), 22).$png;
        $fixture = UploadedFile::fake()->createWithContent('icon.ico', $ico);
        $upload = new UploadedFile($fixture->getRealPath(), 'icon.ico', null, null, true);
        $this->saveSettings(['site_favicon' => $upload])->assertSessionHasNoErrors();
        $path = Setting::valueOf('site_favicon');
        $this->assertStringEndsWith('.ico', $path);
        Storage::disk('public')->assertExists($path);
        $corrupt = UploadedFile::fake()->createWithContent('bad.ico', substr($ico, 0, 25));
        $this->saveSettings(['site_favicon' => new UploadedFile($corrupt->getRealPath(), 'bad.ico', null, null, true)])->assertSessionHasErrors('site_favicon');
        $this->assertSame($path, Setting::valueOf('site_favicon'));
    }
}
