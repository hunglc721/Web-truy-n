<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Genre;
use App\Services\AI\AIClientInterface;
use App\Services\AI\RuleBasedPreferenceParser;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecommendationResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32)), 'ai.enabled' => false,
            'ai.conversational_response' => true, 'ai.max_calls' => 10]);
        Cache::flush();
        Http::preventStrayRequests();
        $this->seed(RecommendationTaxonomySeeder::class);
        $genre = Genre::create(['name' => 'Fantasy']);
        Comic::factory()->create(['title' => 'Truyện thật', 'published_at' => now()->subDay()])->genres()->attach($genre);
    }

    public function test_contextual_exclusion_removal_and_dynamic_replies_without_ai(): void
    {
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
        $first = $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main mạnh không harem'])->assertOk();
        $this->assertStringContainsString('Harem', $first->json('message'));
        $this->assertStringContainsString('Fantasy + main mạnh', $first->json('message'));
        $this->assertSame('Truyện thật', $first->json('recommendations.0.title'));
        $this->assertNotContains('Main OP', $first->json('quick_replies'));
        $token = $first->json('conversation_token');
        $this->postJson('/api/recommendation/chat', ['message' => 'có dungeon', 'conversation_token' => $token])->assertOk();
        $removed = $this->postJson('/api/recommendation/chat', ['message' => 'thôi bỏ dungeon', 'conversation_token' => $token])->assertOk();
        $this->assertStringContainsString('đã bỏ Dungeon', $removed->json('message'));
        $this->assertNotContains('Dungeon', $removed->json('preferences.themes'));
        $this->postJson('/api/recommendation/chat', ['quick_reply' => 'Completed', 'conversation_token' => $token])
            ->assertOk()->assertJsonPath('preferences.status', 'completed');
    }

    public function test_empty_results_offer_actions_that_only_change_state_when_clicked(): void
    {
        Comic::query()->delete();
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
        $response = $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main mạnh không harem'])->assertOk();
        $this->assertStringContainsString('nới một điều kiện', $response->json('message'));
        $this->assertContains('Bỏ Main OP', $response->json('quick_replies'));
        $this->assertSame(['Overpowered MC'], $response->json('preferences.character_traits'));
        $token = $response->json('conversation_token');
        $this->postJson('/api/recommendation/chat', ['quick_reply' => 'Bỏ Main OP', 'conversation_token' => $token])
            ->assertOk()->assertJsonPath('preferences.character_traits', []);
        $this->postJson('/api/recommendation/chat', ['quick_reply' => 'Cho phép Harem', 'conversation_token' => $token])
            ->assertOk()->assertJsonPath('preferences.exclude', []);
    }

    public function test_provider_timeout_hallucination_and_html_use_safe_fallback(): void
    {
        foreach ([null, 'Hãy đọc Comic Bịa Không Có', '<img src=x onerror=alert(1)>'] as $index => $unsafe) {
            Cache::flush();
            config(['ai.enabled' => true]);
            $intent = app(RuleBasedPreferenceParser::class)->parse('fantasy main mạnh không harem');
            $client = $this->mock(AIClientInterface::class);
            $client->shouldReceive('complete')->once()->ordered()->andReturn(['success' => true, 'content' => json_encode($intent), 'error' => null]);
            $client->shouldReceive('complete')->once()->ordered()->andReturn(['success' => $unsafe !== null,
                'content' => json_encode(['message' => $unsafe]), 'error' => $unsafe === null ? 'connection_error' : null]);
            $response = $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main mạnh không harem '.$index])->assertOk();
            $this->assertStringContainsString('Harem', $response->json('message'));
            $this->assertStringNotContainsString('Comic Bịa', $response->json('message'));
            $this->assertStringNotContainsString('<img', $response->json('message'));
            $this->assertSame('Truyện thật', $response->json('recommendations.0.title'));
        }
    }

    public function test_grounded_ai_response_and_shared_budget(): void
    {
        config(['ai.enabled' => true, 'ai.max_calls' => 2]);
        $intent = app(RuleBasedPreferenceParser::class)->parse('fantasy main mạnh không harem');
        $client = $this->mock(AIClientInterface::class);
        $client->shouldReceive('complete')->once()->ordered()->andReturn(['success' => true, 'content' => json_encode($intent), 'error' => null]);
        $client->shouldReceive('complete')->once()->ordered()->andReturnUsing(function ($instruction, $input) {
            $payload = json_decode($input, true);
            $this->assertSame('Truyện thật', $payload['candidates'][0]['title']);
            return ['success' => true, 'content' => json_encode(['message' => $payload['approved_messages'][0]]), 'error' => null];
        });
        $response = $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main mạnh không harem'])->assertOk();
        $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main thông minh không harem', 'conversation_token' => $response->json('conversation_token')])->assertOk();
        $this->postJson('/api/recommendation/chat', ['quick_reply' => 'Dark', 'conversation_token' => $response->json('conversation_token')])->assertOk();
    }
}
