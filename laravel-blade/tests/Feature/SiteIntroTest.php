<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteIntroTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_accessible_intro_and_its_assets(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="site-intro"', false)
            ->assertSee('data-state="idle"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('aria-label="Bỏ qua intro"', false)
            ->assertSee(asset('css/site-intro.css').'?v=1', false)
            ->assertSee(asset('js/site-intro.js').'?v=1', false)
            ->assertSee('Mở trang.')
            ->assertSee('Bật mood.');
    }

    public function test_intro_uses_current_site_name_and_uploaded_logo(): void
    {
        Setting::putValue('site_name', 'Comicx Universe');
        Setting::putValue('site_logo', 'branding/logo/comicx.png');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<span class="site-intro__title-brand">Comicx Universe</span>', false)
            ->assertSee('<img src="'.Storage::disk('public')->url('branding/logo/comicx.png').'" alt="" width="84" height="84"', false);
    }

    public function test_intro_is_not_loaded_on_other_public_pages(): void
    {
        $this->get(route('pages.about'))
            ->assertOk()
            ->assertDontSee('id="site-intro"', false)
            ->assertDontSee('css/site-intro.css', false)
            ->assertDontSee('js/site-intro.js', false);
    }
}
