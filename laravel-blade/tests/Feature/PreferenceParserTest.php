<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Services\AI\AIClientInterface;
use App\Services\AI\PreferenceParser;
use App\Services\AI\RuleBasedPreferenceParser;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PreferenceParserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(RecommendationTaxonomySeeder::class);
        Http::preventStrayRequests();
        foreach (['Fantasy', 'Action', 'Romance', 'Horror', 'School Life'] as $name) {
            Genre::create(['name' => $name]);
        }
        config(['ai.max_calls' => 1, 'ai.decay_seconds' => 60, 'ai.parse_cache_ttl' => 600,
            'ai.provider' => 'openai_compatible', 'ai.model' => 'test-model',
            'ai.api_key' => 'test-secret', 'ai.base_url' => 'https://ai.example/v1', 'ai.retry_times' => 0]);
    }

    private function aiResponse(): array
    {
        return ['success' => true, 'content' => json_encode(app(RuleBasedPreferenceParser::class)->parse('fantasy main bá')), 'error' => null];
    }

    public function test_natural_language_calls_ai_and_normalized_repeat_uses_cache(): void
    {
        $this->mock(AIClientInterface::class)->shouldReceive('complete')->once()->andReturn($this->aiResponse());
        $parser = app(PreferenceParser::class);
        $first = $parser->parseNaturalLanguage('fantasy main bá', 'user:15');
        $second = $parser->parseNaturalLanguage('  FANTASY   main bá  ', 'user:15');
        $this->assertSame('ai', $first['source']);
        $this->assertTrue($second['cached']);
        $this->assertSame($first['preferences'], $second['preferences']);
    }

    public function test_quick_replies_never_call_ai_and_return_only_deltas(): void
    {
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
        foreach (['Main OP' => ['character_traits' => ['Overpowered MC']],
            'Weak → Strong' => ['character_traits' => ['Weak to Strong']],
            'Fantasy' => ['genres' => ['Fantasy']], 'No Romance' => ['relationships' => ['No Romance']],
            'Completed' => ['status' => 'completed']] as $label => $delta) {
            $this->assertSame($delta, app(PreferenceParser::class)->parseQuickReply($label)['preferences']);
        }
        $this->assertFalse(app(PreferenceParser::class)->parseQuickReply('Invented chip')['success']);
    }

    #[DataProvider('providerFailures')]
    public function test_provider_errors_fallback_and_cache_with_safe_logging(?int $status): void
    {
        Log::spy();
        Http::fake(['*' => $status === null ? Http::failedConnection() : Http::response([], $status)]);
        $parser = app(PreferenceParser::class);
        $result = $parser->parseNaturalLanguage('fantasy main bá không harem', 'conversation:secret-id');
        $this->assertSame('fallback', $result['source']);
        $this->assertSame(['Fantasy'], $result['preferences']['genres']);
        $this->assertSame(['Overpowered MC'], $result['preferences']['character_traits']);
        $this->assertSame(['Harem'], $result['preferences']['exclude']);
        $this->assertTrue($parser->parseNaturalLanguage('fantasy main bá không harem', 'conversation:secret-id')['cached']);
        Log::shouldHaveReceived('notice')->once()->with('AI preference fallback', [
            'scope_type' => 'conversation', 'provider' => 'openai_compatible', 'model' => 'test-model',
            'reason' => $status === null ? 'connection_error' : 'http_error', 'fallback_used' => true,
        ]);
    }

    public static function providerFailures(): array
    {
        return [[500], [503], [429], [null]];
    }

    public function test_rate_limit_skips_ai_and_different_scopes_have_separate_quota(): void
    {
        $this->mock(AIClientInterface::class)->shouldReceive('complete')->twice()->andReturn($this->aiResponse());
        $parser = app(PreferenceParser::class);
        $parser->parseNaturalLanguage('fantasy', 'user:15');
        $limited = $parser->parseNaturalLanguage('fantasy main bá không harem', 'user:15');
        $this->assertSame('rate_limited', $limited['fallback_reason']);
        $this->assertSame(['Harem'], $limited['preferences']['exclude']);
        $this->assertSame('ai', $parser->parseNaturalLanguage('fantasy', 'conversation:abc')['source']);
    }

    public function test_ttl_and_rate_window_expire(): void
    {
        config(['ai.parse_cache_ttl' => 60]);
        $this->mock(AIClientInterface::class)->shouldReceive('complete')->twice()->andReturn($this->aiResponse());
        $parser = app(PreferenceParser::class);
        $parser->parseNaturalLanguage('fantasy', 'session:abc');
        $this->travel(61)->seconds();
        $this->assertFalse($parser->parseNaturalLanguage('fantasy', 'session:abc')['cached']);
    }

    public function test_invalid_input_skips_ai(): void
    {
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
        $this->assertFalse(app(PreferenceParser::class)->parseNaturalLanguage('', 'user:15')['success']);
        $this->assertFalse(app(PreferenceParser::class)->parseNaturalLanguage('fantasy', '')['success']);
    }

    public function test_fallback_keywords_and_negation(): void
    {
        $result = app(RuleBasedPreferenceParser::class)->parse('học đường hệ thống dungeon trả thù chuyển sinh hồi quy tu tiên sinh tồn từ yếu thành mạnh main thông minh phản anh hùng tăm tối hài cảm động không romance hoàn thành');
        $this->assertSame(['School Life'], $result['genres']);
        $this->assertCount(7, $result['themes']);
        $this->assertEqualsCanonicalizing(['Weak to Strong', 'Smart MC', 'Anti Hero'], $result['character_traits']);
        $this->assertEqualsCanonicalizing(['Dark', 'Comedy', 'Emotional'], $result['tones']);
        $this->assertSame(['Romance'], $result['exclude']);
        $this->assertSame('completed', $result['status']);
        $this->assertSame('ongoing', app(RuleBasedPreferenceParser::class)->parse('đang ra')['status']);
        $this->assertTrue(app(RuleBasedPreferenceParser::class)->parse('tìm truyện hay')['needs_more_info']);
        $this->assertSame([], app(RuleBasedPreferenceParser::class)->parse('actionable')['genres']);
    }
}
