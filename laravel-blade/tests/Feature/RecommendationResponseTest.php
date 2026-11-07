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

    public function test_natural_ai_question_reply_replaces_the_template(): void
    {
        config(['ai.enabled' => true]);
        $intent = ['genres' => [], 'themes' => [], 'settings' => [], 'character_traits' => [],
            'tones' => [], 'relationships' => [], 'status' => null, 'exclude' => [],
            'needs_more_info' => true, 'follow_up_question' => 'Bạn thích thể loại nào?'];
        $client = $this->mock(AIClientInterface::class);
        $client->shouldReceive('complete')->once()->ordered()
            ->andReturn(['success' => true, 'content' => json_encode($intent), 'error' => null]);
        $client->shouldReceive('complete')->once()->ordered()->andReturnUsing(function ($instruction, $input) {
            $payload = json_decode($input, true);
            $this->assertSame('question', $payload['mode']);
            $this->assertTrue($payload['question']);
            $this->assertSame('Bạn thích thể loại nào?', $payload['follow_up_hint']);

            return ['success' => true, 'content' => json_encode([
                'message' => 'Chào bạn! Mình có thể gợi ý theo thể loại hoặc kiểu nhân vật. Bạn thích hành động, học đường hay main thông minh?',
                'mentioned_titles' => [],
            ]), 'error' => null];
        });

        $response = $this->postJson('/api/recommendation/chat', ['message' => 'alo'])->assertOk();
        $response->assertJsonPath('type', 'question');
        $this->assertStringContainsString('Chào bạn!', $response->json('message'));
        $this->assertNull($response->json('follow_up_message'));
    }

    public function test_natural_ai_empty_result_reply_is_used_and_grounded(): void
    {
        config(['ai.enabled' => true]);
        Comic::query()->delete();
        $intent = app(RuleBasedPreferenceParser::class)->parse('fantasy main mạnh không harem');
        $client = $this->mock(AIClientInterface::class);
        $client->shouldReceive('complete')->once()->ordered()
            ->andReturn(['success' => true, 'content' => json_encode($intent), 'error' => null]);
        $client->shouldReceive('complete')->once()->ordered()->andReturnUsing(function ($instruction, $input) {
            $payload = json_decode($input, true);
            $this->assertSame('empty', $payload['mode']);
            $this->assertSame([], $payload['candidates']);

            return ['success' => true, 'content' => json_encode([
                'message' => 'Hiện thư viện chưa có bộ nào khớp cả Fantasy lẫn main mạnh mà không có Harem. Bạn thử nới một tiêu chí nhé, ví dụ bỏ main mạnh hoặc cho phép Harem.',
                'mentioned_titles' => [],
            ]), 'error' => null];
        });

        $response = $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main mạnh không harem'])->assertOk();
        $this->assertStringContainsString('chưa có bộ nào', $response->json('message'));
        $this->assertNull($response->json('follow_up_message'));
    }

    public function test_ai_cannot_name_a_comic_outside_the_candidates(): void
    {
        config(['ai.enabled' => true]);
        $intent = app(RuleBasedPreferenceParser::class)->parse('fantasy main mạnh không harem');
        $client = $this->mock(AIClientInterface::class);
        $client->shouldReceive('complete')->once()->ordered()
            ->andReturn(['success' => true, 'content' => json_encode($intent), 'error' => null]);
        $client->shouldReceive('complete')->once()->ordered()->andReturn([
            'success' => true,
            'content' => json_encode([
                'message' => 'Mình nghĩ bạn nên đọc Comic Bịa Không Có, rất hợp gu.',
                'mentioned_titles' => ['Comic Bịa Không Có'],
            ]),
            'error' => null,
        ]);

        $response = $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main mạnh không harem'])->assertOk();
        $this->assertStringNotContainsString('Comic Bịa', $response->json('message'));
        $this->assertStringContainsString('Harem', $response->json('message'));
        $this->assertSame('Truyện thật', $response->json('recommendations.0.title'));
    }

    public function test_grounded_natural_ai_response_and_shared_budget(): void
    {
        config(['ai.enabled' => true, 'ai.max_calls' => 2]);
        $intent = app(RuleBasedPreferenceParser::class)->parse('fantasy main mạnh không harem');
        $client = $this->mock(AIClientInterface::class);
        $client->shouldReceive('complete')->once()->ordered()->andReturn(['success' => true, 'content' => json_encode($intent), 'error' => null]);
        $client->shouldReceive('complete')->once()->ordered()->andReturnUsing(function ($instruction, $input) {
            $payload = json_decode($input, true);
            $this->assertSame('Truyện thật', $payload['candidates'][0]['title']);
            $this->assertSame('recommendations', $payload['mode']);
            $this->assertContains('harem', array_map('mb_strtolower', $payload['preferences']['exclude']));

            return ['success' => true, 'content' => json_encode([
                'message' => 'Gu Fantasy + main mạnh của bạn khá rõ rồi. Mình thấy Truyện thật là lựa chọn sát nhất, bạn thử xem sao nhé. Nếu muốn, mình lọc thêm theo tông tối hoặc trạng thái hoàn thành.',
                'mentioned_titles' => ['Truyện thật'],
            ]), 'error' => null];
        });

        $response = $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main mạnh không harem'])->assertOk();
        $this->assertStringContainsString('Gu Fantasy', $response->json('message'));
        $this->assertStringContainsString('Truyện thật', $response->json('message'));
        $this->assertNull($response->json('follow_up_message'));

        // The two-call budget is already spent, so later turns use the deterministic fallback.
        $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main thông minh không harem', 'conversation_token' => $response->json('conversation_token')])->assertOk();
        $this->postJson('/api/recommendation/chat', ['quick_reply' => 'Dark', 'conversation_token' => $response->json('conversation_token')])->assertOk();
    }

    public function test_follow_up_turns_carry_preferences_and_history_into_the_model(): void
    {
        config(['ai.enabled' => true]);
        $intent = fn (array $changes) => array_replace([
            'genres' => [], 'themes' => [], 'settings' => [], 'character_traits' => [], 'tones' => [],
            'relationships' => [], 'status' => null, 'exclude' => [], 'needs_more_info' => false,
            'follow_up_question' => null,
        ], $changes);
        $reply = fn (string $message) => json_encode(['message' => $message, 'mentioned_titles' => []]);

        $client = $this->mock(AIClientInterface::class);
        $client->shouldReceive('complete')->once()->ordered()->andReturn(['success' => true, 'error' => null,
            'content' => json_encode($intent(['genres' => ['Fantasy'], 'settings' => ['School'],
                'character_traits' => ['Smart MC']]))]);
        $client->shouldReceive('complete')->once()->ordered()->andReturn(['success' => true, 'error' => null,
            'content' => $reply('Nghe hợp gu học đường đó nha. Mình tìm được Truyện thật cho bạn thử nè.')]);
        $client->shouldReceive('complete')->once()->ordered()->andReturnUsing(function ($instruction, $input) use ($intent) {
            $payload = json_decode($input, true);
            // The parser sees the trusted current state plus the earlier dialogue.
            $this->assertSame(['Fantasy'], $payload['previous_preferences']['genres']);
            $this->assertSame(['School'], $payload['previous_preferences']['settings']);
            $this->assertSame(['Smart MC'], $payload['previous_preferences']['character_traits']);
            $this->assertSame('tôi thích truyện học đường main thông minh', $payload['recent_history'][0]['content']);
            $this->assertSame('assistant', $payload['recent_history'][1]['role']);

            return ['success' => true, 'error' => null,
                'content' => json_encode($intent(['genres' => ['Fantasy'], 'tones' => ['Comedy']]))];
        });
        $client->shouldReceive('complete')->once()->ordered()->andReturnUsing(function ($instruction, $input) use ($reply) {
            $payload = json_decode($input, true);
            $this->assertSame('recommendations', $payload['mode']);
            $this->assertCount(2, $payload['recent_history']);
            $this->assertSame(['Fantasy'], $payload['taxonomy']['genres']);
            $this->assertContains('Comedy', $payload['taxonomy']['tones']);

            return ['success' => true, 'error' => null,
                'content' => $reply('Thêm hài nhẹ thì Truyện thật vẫn là lựa chọn đầu tiên đó nha.')];
        });

        $first = $this->postJson('/api/recommendation/chat',
            ['message' => 'tôi thích truyện học đường main thông minh'])->assertOk();
        $second = $this->postJson('/api/recommendation/chat', ['message' => 'thêm hài hước nhẹ nhàng nữa nhé',
            'conversation_token' => $first->json('conversation_token')])->assertOk();

        // Adding a tone must not drop the previously selected genre or traits.
        $second->assertJsonPath('preferences.genres', ['Fantasy'])
            ->assertJsonPath('preferences.settings', ['School'])
            ->assertJsonPath('preferences.character_traits', ['Smart MC'])
            ->assertJsonPath('preferences.tones', ['Comedy']);
        $this->assertStringContainsString('Truyện thật', $second->json('message'));
    }

    public function test_additive_wording_keeps_previous_criteria_without_ai(): void
    {
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
        $first = $this->postJson('/api/recommendation/chat',
            ['message' => 'tôi thích fantasy main thông minh'])->assertOk();
        $first->assertJsonPath('preferences.genres', ['Fantasy'])
            ->assertJsonPath('preferences.character_traits', ['Smart MC']);

        $second = $this->postJson('/api/recommendation/chat', ['message' => 'thêm hài hước nhẹ nhàng nữa nhé',
            'conversation_token' => $first->json('conversation_token')])->assertOk();
        $second->assertJsonPath('preferences.genres', ['Fantasy'])
            ->assertJsonPath('preferences.character_traits', ['Smart MC'])
            ->assertJsonPath('preferences.tones', ['Comedy']);
    }

    public function test_replacement_wording_drops_the_previous_dimension_without_ai(): void
    {
        Genre::create(['name' => 'Action']);
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
        $first = $this->postJson('/api/recommendation/chat',
            ['message' => 'tôi thích fantasy main thông minh'])->assertOk();
        $second = $this->postJson('/api/recommendation/chat', ['message' => 'đổi sang action thôi',
            'conversation_token' => $first->json('conversation_token')])->assertOk();
        $second->assertJsonPath('preferences.genres', ['Action']);
    }

    public function test_vague_follow_up_keeps_the_current_results_instead_of_reasking(): void
    {
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
        $first = $this->postJson('/api/recommendation/chat',
            ['message' => 'tôi thích fantasy main thông minh'])->assertOk();
        $second = $this->postJson('/api/recommendation/chat', ['message' => 'cảm ơn nhé',
            'conversation_token' => $first->json('conversation_token')])->assertOk();
        $second->assertJsonPath('type', 'recommendations')
            ->assertJsonPath('preferences.genres', ['Fantasy']);
    }

    public function test_transcript_does_not_leak_between_conversations(): void
    {
        config(['ai.enabled' => true]);
        $client = $this->mock(AIClientInterface::class);
        $client->shouldReceive('complete')->once()->ordered()->andReturn(['success' => true, 'error' => null,
            'content' => json_encode(['genres' => ['Fantasy'], 'themes' => [], 'settings' => [],
                'character_traits' => [], 'tones' => [], 'relationships' => [], 'status' => null,
                'exclude' => [], 'needs_more_info' => false, 'follow_up_question' => null])]);
        $client->shouldReceive('complete')->once()->ordered()->andReturn(['success' => true, 'error' => null,
            'content' => json_encode(['message' => 'Truyện thật hợp gu fantasy nè.', 'mentioned_titles' => ['Truyện thật']])]);
        $client->shouldReceive('complete')->once()->ordered()->andReturnUsing(function ($instruction, $input) {
            $payload = json_decode($input, true);
            $this->assertSame([], $payload['previous_preferences']['genres']);
            $this->assertSame([], $payload['recent_history']);

            return ['success' => true, 'error' => null, 'content' => json_encode([
                'genres' => [], 'themes' => [], 'settings' => [], 'character_traits' => [], 'tones' => [],
                'relationships' => [], 'status' => null, 'exclude' => [], 'needs_more_info' => true,
                'follow_up_question' => 'Bạn muốn đọc thể loại nào?'])];
        });
        $client->shouldReceive('complete')->once()->ordered()->andReturnUsing(function ($instruction, $input) {
            $this->assertSame([], json_decode($input, true)['recent_history']);

            return ['success' => true, 'error' => null,
                'content' => json_encode(['message' => 'Bạn muốn đọc thể loại nào?', 'mentioned_titles' => []])];
        });

        $first = $this->postJson('/api/recommendation/chat', ['message' => 'thích fantasy'])->assertOk();
        $second = $this->postJson('/api/recommendation/chat', ['message' => 'alo'])->assertOk();
        $this->assertNotSame($first->json('conversation_token'), $second->json('conversation_token'));
        $second->assertJsonPath('type', 'question');
    }
}
