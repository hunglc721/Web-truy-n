<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_is_in_public_layout_and_not_admin(): void
    {
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $this->get('/about')->assertOk()->assertSee('id="recommendation-chat-toggle"', false)
            ->assertSee('aria-controls="recommendation-chat-panel"', false);
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.comics.create'))
            ->assertOk()->assertDontSee('id="recommendation-chat-widget"', false);
    }
}
