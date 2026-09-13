<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Services\AI\AIClientInterface;
use App\Services\AI\PreferenceParser;
use App\Services\AI\RuleBasedPreferenceParser;
use App\Services\RecommendationChatService;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RecommendationOperationsTest extends TestCase
{
    use RefreshDatabase;

    private TestHandler $logs;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32)), 'app.debug' => false,
            'ai.enabled' => true, 'ai.provider' => 'openai_compatible', 'ai.model' => 'test-model',
            'ai.api_key' => 'SECRET_TEST_API_KEY', 'ai.base_url' => 'https://ai.example/v1',
            'ai.retry_times' => 0, 'ai.max_calls' => 10, 'ai.parse_cache_ttl' => 600]);
        Cache::flush();
        Http::preventStrayRequests();
        $this->seed(RecommendationTaxonomySeeder::class);
        Genre::create(['name' => 'Fantasy']);
        $this->logs = new TestHandler;
        Log::swap(new Logger(new Monolog('ops-test', [$this->logs]), app('events')));
    }

    private function assertPrivateLogs(string ...$markers): void
    {
        $records = json_encode($this->logs->getRecords(), JSON_UNESCAPED_UNICODE);
        foreach (['SECRET_TEST_API_KEY', 'Authorization', 'Bearer', ...$markers] as $marker) {
            $this->assertStringNotContainsString($marker, $records);
        }
    }

    #[DataProvider('failures')]
    public function test_provider_errors_log_only_safe_metadata_and_return_fallback(?int $status): void
    {
        $message = 'fantasy main bá không harem PRIVATE_USER_MESSAGE';
        Http::fake(['*' => $status === null ? Http::failedConnection('SECRET_TEST_API_KEY')
            : Http::response(['error' => 'Bearer SECRET_TEST_API_KEY RAW_PROVIDER_RESPONSE'], $status)]);
        $response = $this->postJson('/api/recommendation/chat', ['message' => $message])
            ->assertOk()->assertJsonPath('preferences.exclude', ['Harem']);
        foreach (['SECRET_TEST_API_KEY', 'RAW_PROVIDER_RESPONSE', 'PRIVATE_USER_MESSAGE', 'trace', 'exception'] as $marker) {
            $this->assertStringNotContainsString($marker, $response->getContent());
        }
        $warnings = array_values(array_filter($this->logs->getRecords(), fn ($record) => $record->message === 'AI provider failure'));
        $this->assertCount(1, $warnings);
        $this->assertSame($status, $warnings[0]->context['http_status']);
        $this->assertGreaterThanOrEqual(0, $warnings[0]->context['duration_ms']);
        $this->assertTrue($this->logs->hasNoticeThatContains('AI preference fallback'));
        $this->assertPrivateLogs($message, 'RAW_PROVIDER_RESPONSE', $response->json('conversation_token'));
    }

    public static function failures(): array
    {
        return [[500], [429], [null]];
    }

    public function test_disabled_ai_skips_client_and_returns_fallback(): void
    {
        config(['ai.enabled' => false]);
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
        $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main bá không harem'])
            ->assertOk()->assertJsonPath('preferences.genres', ['Fantasy'])
            ->assertJsonPath('preferences.exclude', ['Harem']);
        $this->assertSame('ai_disabled', $this->logs->getRecords()[0]->context['reason']);
        Http::assertNothingSent();
    }

    public function test_disabled_adapter_cannot_send_http_even_when_called_directly(): void
    {
        config(['ai.enabled' => false]);
        $this->assertSame('ai_disabled', app(AIClientInterface::class)->complete('private prompt', 'private message')['error']);
        Http::assertNothingSent();
    }

    public function test_disable_ignores_cached_ai_result_and_cache_keys_hash_private_inputs(): void
    {
        $keys = [];
        Event::listen(KeyWritten::class, function ($event) use (&$keys) {
            $keys[] = $event->key;
        });
        $message = 'fantasy PRIVATE_USER_MESSAGE';
        $scope = 'session:'.str_repeat('a', 64);
        $this->mock(AIClientInterface::class)->shouldReceive('complete')->once()->andReturn([
            'success' => true, 'content' => json_encode(app(RuleBasedPreferenceParser::class)->parse('main bá')), 'error' => null,
        ]);
        $parser = app(PreferenceParser::class);
        $this->assertSame('ai', $parser->parseNaturalLanguage($message, $scope)['source']);
        config(['ai.enabled' => false]);
        $fallback = $parser->parseNaturalLanguage($message, $scope);
        $this->assertSame('fallback', $fallback['source']);
        $this->assertSame(['Fantasy'], $fallback['preferences']['genres']);
        $this->assertSame([], $fallback['preferences']['character_traits']);
        $this->assertTrue($parser->parseNaturalLanguage($message, $scope)['cached']);
        $parseKeys = array_values(array_filter($keys, fn ($key) => str_starts_with($key, 'ai:parse:')));
        $this->assertCount(2, $parseKeys);
        foreach ($parseKeys as $key) {
            $this->assertMatchesRegularExpression('/^ai:parse:(enabled|disabled):[a-f0-9]{64}:[a-f0-9]{64}$/', $key);
        }
        foreach ([$message, mb_strtolower($message), $scope, str_repeat('a', 64), 'SECRET_TEST_API_KEY'] as $marker) {
            $this->assertStringNotContainsString($marker, implode('|', $keys));
        }
        $this->assertPrivateLogs($message, $scope, str_repeat('a', 64));
    }

    public function test_malformed_json_is_logged_without_provider_content(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '{SECRET_TEST_API_KEY']]]])]);
        $this->postJson('/api/recommendation/chat', ['message' => 'fantasy'])->assertOk();
        $this->assertSame('invalid_json', $this->logs->getRecords()[0]->context['reason']);
        $this->assertPrivateLogs();
    }

    public function test_unexpected_chat_exception_does_not_leak_to_response_or_default_logger(): void
    {
        $this->mock(RecommendationChatService::class)->shouldReceive('reply')->andThrow(
            new \RuntimeException('SECRET_TEST_API_KEY SQL RAW_PROVIDER_RESPONSE /private/path'));
        $response = $this->postJson('/api/recommendation/chat?token=PRIVATE_TOKEN', ['message' => 'PRIVATE_USER_MESSAGE'])
            ->assertStatus(500)->assertExactJson(['status' => 'error', 'code' => 500,
                'message' => 'Hệ thống gặp sự cố. Vui lòng thử lại sau.']);
        $this->assertCount(1, $this->logs->getRecords());
        $this->assertTrue($this->logs->hasErrorThatContains('Recommendation chat failure'));
        $this->assertPrivateLogs('SQL', 'RAW_PROVIDER_RESPONSE', '/private/path', 'PRIVATE_TOKEN', 'PRIVATE_USER_MESSAGE');
    }
}
