<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_layout_cleans_up_pwa_without_registering_worker(): void
    {
        $this->app->instance('env', 'local');
        $this->get('/')->assertOk()
            ->assertSee('navigator.serviceWorker.getRegistrations()', false)
            ->assertSee("name.startsWith('webcomics-')", false)
            ->assertDontSee("navigator.serviceWorker.register('/sw.js')", false);
    }

    public function test_production_keeps_registration_and_install_flow_without_cleanup(): void
    {
        $this->app->instance('env', 'production');
        $this->get('/')->assertOk()
            ->assertSee("navigator.serviceWorker.register('/sw.js')", false)
            ->assertSee("window.addEventListener('beforeinstallprompt'", false)
            ->assertDontSee('navigator.serviceWorker.getRegistrations()', false)
            ->assertDontSee('window.caches.delete', false);
    }
}
