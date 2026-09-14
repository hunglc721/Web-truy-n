<?php

namespace Tests\Feature;

use App\Services\AI\AIClientInterface;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AIClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['ai.provider' => 'openai_compatible', 'ai.api_key' => 'test-only',
            'ai.model' => 'test-model', 'ai.base_url' => 'https://ai.example/v1',
            'ai.timeout' => 3, 'ai.retry_times' => 1]);
    }

    public function test_container_client_sends_configured_request(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '{}']]]])]);
        $this->assertSame(['success' => true, 'content' => '{}', 'error' => null],
            app(AIClientInterface::class)->complete('JSON only', 'Xin chào'));
        Http::assertSent(fn ($request) => $request->url() === 'https://ai.example/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-only')
            && $request['model'] === 'test-model'
            && $request['messages'][1]['content'] === 'Xin chào'
            && $request['response_format']['type'] === 'json_object');
    }

    #[DataProvider('httpErrors')]
    public function test_http_errors_are_controlled_with_bounded_retries(int $status, int $attempts): void
    {
        Http::fake(['*' => Http::response(['error' => 'private provider detail'], $status)]);
        $this->assertSame('http_error', app(AIClientInterface::class)->complete('json', 'hi')['error']);
        Http::assertSentCount($attempts);
    }

    public static function httpErrors(): array
    {
        return [[401, 1], [422, 1], [429, 2], [500, 2], [503, 2], [302, 1]];
    }

    public function test_transient_failure_recovers(): void
    {
        Http::fake(['*' => Http::sequence()->push([], 503)->push(['choices' => [['message' => ['content' => '{}']]]])]);
        $this->assertTrue(app(AIClientInterface::class)->complete('json', 'hi')['success']);
        Http::assertSentCount(2);
    }

    public function test_connection_and_timeout_failures_are_controlled(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $this->assertSame(['success' => false, 'content' => null, 'error' => 'connection_error'],
            app(AIClientInterface::class)->complete('json', 'hi'));
    }

    public function test_invalid_response_is_controlled(): void
    {
        Http::fake(['*' => Http::response('<html>unavailable</html>')]);
        $this->assertSame('invalid_response', app(AIClientInterface::class)->complete('json', 'hi')['error']);
    }

    public function test_missing_configuration_and_unsupported_provider_do_not_send_http(): void
    {
        Http::fake();
        config(['ai.api_key' => '']);
        $this->assertSame('invalid_configuration', app(AIClientInterface::class)->complete('json', 'hi')['error']);
        config(['ai.provider' => 'unknown']);
        $this->assertSame('unsupported_provider', app(AIClientInterface::class)->complete('json', 'hi')['error']);
        Http::assertNothingSent();
    }
}
