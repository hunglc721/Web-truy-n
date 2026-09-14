<?php

namespace Tests\Feature;

use App\Data\RecommendationPreference;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Tag;
use App\Services\AI\AIClientInterface;
use App\Services\AI\PreferenceParser;
use App\Services\AI\RuleBasedPreferenceParser;
use App\Services\PreferenceRecommendationService;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RecommendationAuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32)), 'ai.enabled' => false,
            'ai.max_calls' => 2, 'ai.parse_cache_ttl' => 600]);
        Cache::flush();
        Http::preventStrayRequests();
        $this->seed(RecommendationTaxonomySeeder::class);
        foreach (['Action', 'Fantasy', 'School Life', 'Romance'] as $name) {
            Genre::create(['name' => $name]);
        }
    }

    private function expectTwoAiCalls(): void
    {
        config(['ai.enabled' => true]);
        $preferences = app(RuleBasedPreferenceParser::class)->parse('fantasy');
        $this->mock(AIClientInterface::class)->shouldReceive('complete')->twice()->andReturn([
            'success' => true, 'content' => json_encode($preferences), 'error' => null,
        ]);
    }

    public function test_omitting_guest_token_and_spoofing_quota_fields_cannot_reset_budget(): void
    {
        $this->expectTwoAiCalls();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/recommendation/chat', ['message' => 'fantasy main bá '.$i,
                'guestQuotaScopes' => ['session:spoof-'.$i], 'ip' => 'spoof-'.$i])
                ->assertOk()->assertJsonPath('preferences.genres', ['Fantasy']);
        }
    }

    #[DataProvider('stableScopes')]
    public function test_session_and_ip_each_enforce_budget_across_conversations(string $stable): void
    {
        $this->expectTwoAiCalls();
        for ($i = 0; $i < 3; $i++) {
            $scopes = $stable === 'ip' ? ['session:'.$i, 'ip:127.0.0.1'] : ['session:stable', 'ip:127.0.0.'.$i];
            $result = app(PreferenceParser::class)->parseNaturalLanguage('fantasy '.$i, 'conversation:'.$i, $scopes);
        }
        $this->assertSame('fallback', $result['source']);
        $this->assertSame('rate_limited', $result['fallback_reason']);
    }

    public static function stableScopes(): array
    {
        return [['ip'], ['session']];
    }

    #[DataProvider('phrases')]
    public function test_vietnamese_fallback_matches_requested_preferences(string $message, array $expected): void
    {
        $response = $this->postJson('/api/recommendation/chat', ['message' => $message])->assertOk();
        foreach ($expected as $field => $value) {
            $response->assertJsonPath('preferences.'.$field, $value);
        }
        Http::assertNothingSent();
    }

    public static function phrases(): array
    {
        return [
            ['T muốn action fantasy', ['genres' => ['Action', 'Fantasy']]],
            ['Main yếu rồi mạnh dần', ['character_traits' => ['Weak to Strong']]],
            ['Tìm truyện main thông minh, không harem', ['character_traits' => ['Smart MC'], 'exclude' => ['Harem']]],
            ['T muốn fantasy tối, main bị phản bội rồi trả thù', ['genres' => ['Fantasy'], 'themes' => ['Revenge'], 'tones' => ['Dark']]],
            ['Tìm truyện học đường hài', ['genres' => ['School Life'], 'settings' => ['School'], 'tones' => ['Comedy']]],
            ['Không romance', ['exclude' => ['Romance'], 'relationships' => [], 'genres' => []]],
            ['Không hệ thống', ['exclude' => ['System'], 'themes' => []]],
            ['Thôi đổi sang truyện hiện đại', ['settings' => ['Modern']]],
            ['Không cần dungeon nữa', ['exclude' => ['Dungeon'], 'themes' => []]],
            ['Cho t truyện hoàn thành rồi', ['status' => 'completed']],
            ['không học đường', ['exclude' => ['School Life', 'School'], 'genres' => [], 'settings' => []]],
        ];
    }

    public function test_multiturn_state_preserves_signals_and_removes_dungeon(): void
    {
        $token = null;
        foreach (['Fantasy', 'Main yếu lên mạnh', 'Có dungeon', 'Không harem'] as $message) {
            $response = $this->postJson('/api/recommendation/chat', ['message' => $message, 'conversation_token' => $token])->assertOk();
            $token = $response->json('conversation_token');
        }
        $response->assertJsonPath('preferences.genres', ['Fantasy'])
            ->assertJsonPath('preferences.character_traits', ['Weak to Strong'])
            ->assertJsonPath('preferences.themes', ['Dungeon'])->assertJsonPath('preferences.exclude', ['Harem']);
        $this->postJson('/api/recommendation/chat', ['message' => 'Thôi bỏ dungeon', 'conversation_token' => $token])
            ->assertOk()->assertJsonPath('preferences.themes', [])
            ->assertJsonPath('preferences.exclude', ['Harem', 'Dungeon'])
            ->assertJsonPath('preferences.genres', ['Fantasy'])->assertJsonPath('preferences.character_traits', ['Weak to Strong']);
    }

    public function test_sql_ranking_hydrates_only_five_comics_from_large_candidate_set(): void
    {
        $this->mock(AIClientInterface::class)->shouldNotReceive('complete');
        $comics = Comic::factory()->count(1200)->create(['status' => 'completed', 'published_at' => now()->subDay()]);
        $hydrated = 0;
        Event::listen('eloquent.retrieved: '.Comic::class, function () use (&$hydrated) {
            $hydrated++;
        });
        $results = app(PreferenceRecommendationService::class)->recommend(RecommendationPreference::fromArray(['status' => 'completed']), 5);
        $this->assertSame(5, $hydrated);
        $this->assertSame($comics->sortByDesc('id')->take(5)->pluck('id')->all(), $results->pluck('comic.id')->all());
        $this->assertSame([5, 5, 5, 5, 5], $results->pluck('score')->all());
    }

    public function test_harem_is_excluded_before_sql_limit_despite_highest_score(): void
    {
        $allowed = Comic::factory()->create(['status' => 'ongoing', 'published_at' => now()->subDay()]);
        $blocked = Comic::factory()->create(['status' => 'completed', 'published_at' => now()->subDay()]);
        foreach ([$allowed, $blocked] as $comic) {
            $comic->genres()->attach(Genre::where('name', 'Fantasy')->value('id'));
        }
        $blocked->tags()->attach(Tag::whereIn('name', ['System', 'Overpowered MC', 'Modern', 'Dark', 'Harem'])->pluck('id'));
        $results = app(PreferenceRecommendationService::class)->recommend(RecommendationPreference::fromArray([
            'genres' => ['Fantasy'], 'themes' => ['System'], 'character_traits' => ['Overpowered MC'],
            'settings' => ['Modern'], 'tones' => ['Dark'], 'status' => 'completed', 'exclude' => ['Harem'],
        ]), 1);
        $this->assertSame([$allowed->id], $results->pluck('comic.id')->all());
        $this->assertSame([30], $results->pluck('score')->all());
    }
}
