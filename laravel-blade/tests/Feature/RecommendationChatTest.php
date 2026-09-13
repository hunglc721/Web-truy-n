<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Genre;
use App\Models\RecommendationConversation;
use App\Models\Tag;
use App\Models\User;
use App\Services\AI\AIClientInterface;
use App\Services\AI\RuleBasedPreferenceParser;
use App\Services\RecommendationChatService;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecommendationChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        Cache::flush();
        Http::preventStrayRequests();
        $this->seed(RecommendationTaxonomySeeder::class);
        Genre::create(['name' => 'Fantasy']);
    }

    private function fakeIntent(string $message): void
    {
        $preferences = app(RuleBasedPreferenceParser::class)->parse($message);
        $this->mock(AIClientInterface::class)->shouldReceive('complete')->once()->andReturn([
            'success' => true, 'content' => json_encode($preferences), 'error' => null,
        ]);
    }

    public function test_natural_language_returns_real_comics_and_excludes_harem(): void
    {
        $this->fakeIntent('fantasy main bá không harem');
        $comics = Comic::factory()->count(7)->create(['published_at' => now()->subDay()]);
        foreach ($comics as $comic) {
            $comic->genres()->attach(Genre::first()->id);
        }
        $blocked = $comics->last();
        $blocked->tags()->attach(Tag::where('name', 'Harem')->value('id'));
        $response = $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main bá không harem'])
            ->assertOk()->assertJsonPath('type', 'recommendations')->assertJsonCount(5, 'recommendations');
        $ids = array_column($response->json('recommendations'), 'id');
        $this->assertNotContains($blocked->id, $ids);
        foreach ($response->json('recommendations') as $result) {
            $this->assertDatabaseHas('comics', ['id' => $result['id']]);
            $this->assertSame(['id', 'title', 'slug', 'cover', 'score', 'matched_reasons', 'url'], array_keys($result));
            $this->assertSame(['Fantasy'], $result['matched_reasons']);
        }
        $this->assertSame(['Harem'], RecommendationConversation::first()->preferences_json['exclude']);
        $this->assertNotNull($response->json('conversation_token'));
    }

    public function test_vague_message_returns_question(): void
    {
        $this->fakeIntent('tìm truyện hay');
        $this->postJson('/api/recommendation/chat', ['message' => 'tìm truyện hay'])
            ->assertOk()->assertJsonPath('type', 'question')->assertJsonStructure(['message', 'quick_replies', 'conversation_token']);
    }

    public function test_guest_quick_replies_reuse_state_without_ai(): void
    {
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
        $token = $this->postJson('/api/recommendation/chat', ['quick_reply' => 'Fantasy'])->assertOk()->json('conversation_token');
        $this->postJson('/api/recommendation/chat', ['quick_reply' => 'Main OP', 'conversation_token' => $token])
            ->assertOk()->assertJsonPath('preferences.genres', ['Fantasy'])
            ->assertJsonPath('preferences.character_traits', ['Overpowered MC'])
            ->assertJsonPath('recommendations', []);
        $this->assertDatabaseCount('recommendation_conversations', 1);
    }

    public function test_member_identity_comes_from_auth_and_cannot_take_guest_state(): void
    {
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
        $token = $this->postJson('/api/recommendation/chat', ['quick_reply' => 'Fantasy'])->json('conversation_token');
        $member = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($member)->postJson('/api/recommendation/chat', ['quick_reply' => 'Main OP',
            'user_id' => $other->id, 'conversation_token' => $token])
            ->assertOk()->assertJsonPath('conversation_token', null)->assertJsonPath('preferences.genres', []);
        $this->assertDatabaseHas('recommendation_conversations', ['user_id' => $member->id]);
        $this->assertDatabaseMissing('recommendation_conversations', ['user_id' => $other->id]);
    }

    public function test_provider_errors_use_fallback(): void
    {
        config(['ai.provider' => 'openai_compatible', 'ai.api_key' => 'test-only', 'ai.model' => 'test',
            'ai.base_url' => 'https://ai.example/v1', 'ai.retry_times' => 0]);
        foreach ([Http::response([], 500), Http::failedConnection()] as $failure) {
            Http::fake(['*' => $failure]);
            $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main bá không harem'])
                ->assertOk()->assertJsonPath('type', 'recommendations')->assertJsonPath('preferences.exclude', ['Harem']);
        }
    }

    public function test_invalid_inputs_and_unknown_tokens_are_controlled(): void
    {
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
        foreach ([[], ['message' => ' '], ['message' => []], ['message' => str_repeat('a', 4001)],
            ['message' => 'hello', 'quick_reply' => 'Main OP'], ['quick_reply' => 'Unknown'], ['quick_reply' => '0'],
            ['quick_reply' => 'Main OP', 'conversation_token' => 'wrong']] as $input) {
            $this->postJson('/api/recommendation/chat', $input)->assertUnprocessable();
        }
        $this->postJson('/api/recommendation/chat', ['quick_reply' => 'Main OP', 'conversation_token' => str_repeat('a', 64)])
            ->assertNotFound();
    }

    public function test_application_errors_do_not_expose_internal_details_even_in_debug(): void
    {
        config(['app.debug' => true]);
        $this->mock(RecommendationChatService::class)->shouldReceive('reply')->andThrow(new \RuntimeException('SQL private detail'));
        $response = $this->postJson('/api/recommendation/chat', ['message' => 'fantasy'])->assertStatus(500);
        $this->assertStringNotContainsString('SQL private detail', $response->getContent());
        $response->assertJsonMissingPath('trace');
    }
}
