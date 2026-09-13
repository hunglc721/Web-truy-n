<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Tag;
use App\Services\AI\IntentParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IntentParserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['ai.provider' => 'openai_compatible', 'ai.api_key' => 'test-only',
            'ai.model' => 'test-model', 'ai.base_url' => 'https://ai.example/v1', 'ai.retry_times' => 0]);
        Genre::create(['name' => 'Fantasy']);
        Tag::create(['name' => 'Vampire']);
    }

    private function preferences(array $changes = []): array
    {
        return array_replace(['genres' => [], 'themes' => [], 'settings' => [],
            'character_traits' => [], 'tones' => [], 'relationships' => [],
            'status' => null, 'exclude' => [], 'needs_more_info' => false,
            'follow_up_question' => null], $changes);
    }

    private function fakeContent(string $content): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $content]]]])]);
    }

    public function test_vietnamese_preferences_flow_through_client_and_normalize(): void
    {
        $this->fakeContent(json_encode($this->preferences(['genres' => [' fantasy ', 'Fantasy'],
            'character_traits' => ['Overpowered MC'], 'relationships' => ['Harem'], 'exclude' => ['Harem']])));
        $result = app(IntentParser::class)->parse('tìm fantasy main mạnh không harem');
        $this->assertTrue($result['success']);
        $this->assertSame(['Fantasy'], $result['preferences']['genres']);
        $this->assertSame(['Overpowered MC'], $result['preferences']['character_traits']);
        $this->assertSame(['Harem'], $result['preferences']['exclude']);
        $this->assertSame([], $result['preferences']['relationships']);
        Http::assertSent(fn ($request) => $request['messages'][1]['content'] === 'tìm fantasy main mạnh không harem'
            && str_contains($request['messages'][0]['content'], 'JSON only')
            && str_contains($request['messages'][0]['content'], 'Fantasy')
            && str_contains($request['messages'][0]['content'], 'Vampire'));
    }

    public function test_vague_request_keeps_follow_up_question(): void
    {
        $this->fakeContent(json_encode($this->preferences(['needs_more_info' => true,
            'follow_up_question' => 'Bạn thích thể loại nào?'])));
        $result = app(IntentParser::class)->parse('tìm truyện hay');
        $this->assertTrue($result['success']);
        $this->assertTrue($result['preferences']['needs_more_info']);
        $this->assertSame('Bạn thích thể loại nào?', $result['preferences']['follow_up_question']);
    }

    public function test_unknown_taxonomy_is_removed_and_existing_db_values_are_allowed(): void
    {
        Genre::create(['name' => 'Mystery']);
        $this->fakeContent(json_encode($this->preferences(['genres' => ['Mystery', 'Nonexistent'],
            'themes' => ['Super Ultra God', ' system ', 'System'],
            'exclude' => [' vampire ', 'Harem', 'Fake'], 'status' => 'INVALID',
            'follow_up_question' => 'Unnecessary question'])));
        $result = app(IntentParser::class)->parse('tìm truyện bí ẩn');
        $this->assertTrue($result['success']);
        $this->assertSame(['Mystery'], $result['preferences']['genres']);
        $this->assertSame(['System'], $result['preferences']['themes']);
        $this->assertSame(['Vampire', 'Harem'], $result['preferences']['exclude']);
        $this->assertNull($result['preferences']['status']);
        $this->assertNull($result['preferences']['follow_up_question']);
    }

    #[DataProvider('badJson')]
    public function test_malformed_or_non_object_json_fails_safely(string $content): void
    {
        $this->fakeContent($content);
        $result = app(IntentParser::class)->parse('tìm fantasy');
        $this->assertFalse($result['success']);
        $this->assertNull($result['preferences']);
    }

    public static function badJson(): array
    {
        return [['{broken'], ['```json\n{}\n```'], ['[]'], ['null'], ['{}'], ['{"themes":["Super Ultra God"]}']];
    }

    #[DataProvider('badSchema')]
    public function test_wrong_types_and_unexpected_fields_fail_safely(array $changes): void
    {
        $this->fakeContent(json_encode($this->preferences($changes)));
        $this->assertSame('invalid_schema', app(IntentParser::class)->parse('tìm fantasy')['error']);
    }

    public static function badSchema(): array
    {
        return [[['genres' => 'Fantasy']], [['genres' => (object) []]], [['themes' => [123]]],
            [['themes' => [(object) ['name' => 'System']]]], [['follow_up_question' => (object) []]],
            [['needs_more_info' => 'false']], [['needs_more_info' => 1]], [['needs_more_info' => null]],
            [['needs_more_info' => '']],
            [['follow_up_question' => []]], [['status' => []]], [['invented_comics' => ['Fake']]],
            [['needs_more_info' => true, 'follow_up_question' => null]]];
    }

    public function test_status_is_normalized(): void
    {
        $this->fakeContent(json_encode($this->preferences(['status' => ' COMPLETED '])));
        $this->assertSame('completed', app(IntentParser::class)->parse('truyện đã hoàn thành')['preferences']['status']);
    }

    public function test_provider_failure_is_propagated_without_throwing(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $this->assertSame(['success' => false, 'preferences' => null, 'error' => 'http_error'],
            app(IntentParser::class)->parse('tìm fantasy'));
    }

    public function test_empty_and_oversized_input_do_not_call_provider(): void
    {
        Http::fake();
        foreach ([' ', str_repeat('a', 4001)] as $message) {
            $this->assertSame('invalid_input', app(IntentParser::class)->parse($message)['error']);
        }
        Http::assertNothingSent();
    }
}
