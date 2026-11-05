<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Tag;
use App\Services\AI\AIClientInterface;
use App\Services\AI\IntentParser;
use App\Services\AI\RuleBasedPreferenceParser;
use App\Services\RecommendationTaxonomyService;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VietnamesePreferenceNlpTest extends TestCase
{
    use RefreshDatabase;

    private const FIELDS = ['genres', 'themes', 'settings', 'character_traits', 'tones', 'relationships'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RecommendationTaxonomySeeder::class);
        foreach (['Fantasy', 'Action', 'Romance', 'Horror', 'School Life', 'Comedy', 'Adventure',
            'Mystery', 'Supernatural', 'Drama', 'Tragedy', 'Slice of Life', 'Sports', 'Historical',
            'Martial Arts', 'Psychological', 'Sci-Fi', 'Detective', 'Shounen', 'Shoujo', 'Seinen'] as $name) {
            Genre::create(['name' => $name]);
        }
    }

    private function parse(string $message): array
    {
        return app(RuleBasedPreferenceParser::class)->parse($message);
    }

    private function assertMatches(array $expected, array $preferences, string $message): void
    {
        foreach ($expected as $field => $value) {
            if ($field === 'status') {
                $this->assertSame($value, $preferences['status'], $message);

                continue;
            }
            $this->assertEqualsCanonicalizing($value, $preferences[$field], $message.': '.$field);
        }
        $this->assertNoPositiveExclusionOverlap($preferences, $message);
    }

    private function assertNoPositiveExclusionOverlap(array $preferences, string $message = ''): void
    {
        foreach (self::FIELDS as $field) {
            $this->assertSame([], array_values(array_intersect($preferences[$field], $preferences['exclude'])),
                $message.': '.$field.' contains an excluded value');
        }
    }

    #[DataProvider('accentedExamples')]
    public function test_user_examples_in_accented_vietnamese(string $message, array $expected): void
    {
        $preferences = $this->parse($message);
        $this->assertMatches($expected, $preferences, $message);
        $this->assertFalse($preferences['needs_more_info'], $message);
    }

    public static function accentedExamples(): array
    {
        return [
            'overpowered but pretending weak' => ['Tìm truyện main bá nhưng giả yếu',
                ['character_traits' => ['Overpowered MC'], 'exclude' => []]],
            'system without harem' => ['Có hệ thống, không harem',
                ['themes' => ['System'], 'exclude' => ['Harem']]],
            'weak to strong without romance' => ['Truyện nhân vật chính từ yếu thành mạnh, đừng có romance',
                ['character_traits' => ['Weak to Strong'], 'exclude' => ['Romance']]],
            'completed without dungeon' => ['Cho truyện hoàn thành rồi, bỏ dungeon',
                ['status' => 'completed', 'exclude' => ['Dungeon']]],
            'revenge but not too dark' => ['Tìm truyện kiểu trả thù nhưng không quá dark',
                ['themes' => ['Revenge'], 'exclude' => ['Dark']]],
        ];
    }

    #[DataProvider('accentPairs')]
    public function test_unaccented_input_matches_accented_input(string $accented, string $unaccented): void
    {
        $this->assertSame($this->parse($accented), $this->parse($unaccented));
        $this->assertFalse($this->parse($unaccented)['needs_more_info']);
    }

    public static function accentPairs(): array
    {
        return [
            ['Tìm truyện main bá nhưng giả yếu', 'tim truyen main ba nhung gia yeu'],
            ['Có hệ thống, không harem', 'co he thong khong harem'],
            ['Truyện nhân vật chính từ yếu thành mạnh, đừng có romance', 'truyen nhan vat chinh tu yeu thanh manh dung co romance'],
            ['Cho truyện hoàn thành rồi, bỏ dungeon', 'cho truyen hoan thanh roi bo dungeon'],
            ['Tìm truyện kiểu trả thù nhưng không quá dark', 'tim truyen kieu tra thu nhung khong qua dark'],
        ];
    }

    #[DataProvider('slangExamples')]
    public function test_slang_maps_to_existing_taxonomy_values(string $message, array $expected): void
    {
        $preferences = $this->parse($message);
        $this->assertMatches($expected, $preferences, $message);
        $this->assertFalse($preferences['needs_more_info'], $message);
    }

    public static function slangExamples(): array
    {
        return [
            'sang van main ngau' => ['truyện sảng văn main ngầu',
                ['character_traits' => ['Overpowered MC'], 'exclude' => []]],
            'ngon tinh without tragedy' => ['truyện ngôn tình nhẹ nhàng không bi kịch',
                ['genres' => ['Romance'], 'relationships' => ['Romance'], 'exclude' => ['Tragedy']]],
            'hac am without harem' => ['truyện hắc ám không harem',
                ['tones' => ['Dark'], 'exclude' => ['Harem']]],
            'trinh tham' => ['truyện trinh thám', ['genres' => ['Detective'], 'exclude' => []]],
            'tu tien xuyen khong' => ['truyện tu tiên xuyên không',
                ['themes' => ['Cultivation', 'Isekai'], 'exclude' => []]],
            'hau tan the sinh ton' => ['truyện hậu tận thế sinh tồn',
                ['settings' => ['Post Apocalypse'], 'themes' => ['Survival'], 'exclude' => []]],
            'lanh lung muu ke' => ['truyện lạnh lùng mưu kế',
                ['character_traits' => ['Cold MC', 'Smart MC'], 'exclude' => []]],
        ];
    }

    public function test_multi_condition_message_extracts_every_dimension(): void
    {
        $preferences = $this->parse('Tìm truyện kinh dị học đường hệ thống trả thù main bá hoàn thành không harem');
        $this->assertMatches([
            'genres' => ['Horror', 'School Life'],
            'themes' => ['System', 'Revenge'],
            'settings' => ['School'],
            'character_traits' => ['Overpowered MC'],
            'status' => 'completed',
            'exclude' => ['Harem'],
        ], $preferences, 'multi condition');
    }

    #[DataProvider('negationPhrases')]
    public function test_negation_variants_become_exclusions(string $message, string $excluded): void
    {
        $preferences = $this->parse($message);
        $this->assertContains($excluded, $preferences['exclude'], $message);
        $this->assertNoPositiveExclusionOverlap($preferences, $message);
    }

    public static function negationPhrases(): array
    {
        return [
            ['không harem', 'Harem'], ['khg harem', 'Harem'], ['ko thích harem', 'Harem'],
            ['k cần harem', 'Harem'], ['chẳng muốn dungeon', 'Dungeon'], ['chả thích romance', 'Romance'],
            ['đừng có romance', 'Romance'], ['đừng đọc harem', 'Harem'], ['loại bỏ romance', 'Romance'],
            ['bỏ dungeon', 'Dungeon'], ['bỏ qua harem', 'Harem'], ['miễn harem', 'Harem'],
            ['tránh xa dark', 'Dark'], ['ngoài trừ harem', 'Harem'], ['trừ harem', 'Harem'],
            ['cấm harem', 'Harem'], ['harem thì không', 'Harem'], ['không quá dark', 'Dark'],
            ['khong harem', 'Harem'], ['dung co romance', 'Romance'], ['bo dungeon', 'Dungeon'],
            ['tranh xa dark', 'Dark'], ['mien harem', 'Harem'], ['loai bo romance', 'Romance'],
        ];
    }

    #[DataProvider('priorityPhrases')]
    public function test_exclusion_wins_when_a_value_is_mentioned_both_ways(string $message, string $excluded): void
    {
        $preferences = $this->parse($message);
        $this->assertContains($excluded, $preferences['exclude'], $message);
        $this->assertNoPositiveExclusionOverlap($preferences, $message);
    }

    public static function priorityPhrases(): array
    {
        return [
            ['main bá nhưng không thích main bá', 'Overpowered MC'],
            ['không thích main bá nhưng thích hệ thống', 'Overpowered MC'],
            ['harem thì không', 'Harem'],
            ['romance thì không cần harem', 'Harem'],
        ];
    }

    public function test_ambiguous_common_words_are_not_misread(): void
    {
        $system = $this->parse('truyện dùng hệ thống');
        $this->assertSame(['System'], $system['themes']);
        $this->assertSame([], $system['exclude']);

        foreach (['bộ truyện harem', 'thể loại harem', 'truyện tranh harem'] as $message) {
            $preferences = $this->parse($message);
            $this->assertSame(['Harem'], $preferences['relationships'], $message);
            $this->assertSame([], $preferences['exclude'], $message);
        }

        $this->assertSame(['Comedy'], $this->parse('truyện hài')['tones']);
        foreach (['truyện hai main', 'truyện hại não', 'truyện hải tặc'] as $message) {
            $preferences = $this->parse($message);
            $this->assertNotContains('Comedy', $preferences['tones'], $message);
            $this->assertTrue($preferences['needs_more_info'], $message);
        }
    }

    public function test_status_phrases_prefer_ongoing_over_completed(): void
    {
        foreach (['chưa hoàn thành', 'chưa full', 'đang ra', 'còn tiếp'] as $message) {
            $this->assertSame('ongoing', $this->parse($message)['status'], $message);
        }
        $this->assertSame('completed', $this->parse('truyện hoàn thành')['status']);
        $this->assertSame('hiatus', $this->parse('tạm ngưng')['status']);
        $this->assertSame('cancelled', $this->parse('bị hủy')['status']);
    }

    public function test_empty_and_vague_input_ask_for_more_information(): void
    {
        foreach (['', '   ', 'tìm truyện hay', 'truyện'] as $message) {
            $preferences = $this->parse($message);
            foreach (self::FIELDS as $field) {
                $this->assertSame([], $preferences[$field], $message.': '.$field);
            }
            $this->assertNull($preferences['status'], $message);
            $this->assertSame([], $preferences['exclude'], $message);
            $this->assertTrue($preferences['needs_more_info'], $message);
            $this->assertNotEmpty($preferences['follow_up_question'], $message);
        }

        $excludedOnly = $this->parse('không harem');
        $this->assertFalse($excludedOnly['needs_more_info']);
        $this->assertSame(['Harem'], $excludedOnly['exclude']);
    }

    public function test_rule_parser_never_invents_taxonomy_values(): void
    {
        Genre::where('name', 'Comedy')->delete();
        Tag::where('name', 'Comedy')->where('category', 'tone')->delete();
        foreach (['truyện hài', 'truyện super ultra god'] as $message) {
            $preferences = $this->parse($message);
            $this->assertNotContains('Comedy', $preferences['genres'], $message);
            $this->assertNotContains('Comedy', $preferences['tones'], $message);
            $this->assertTrue($preferences['needs_more_info'], $message);
        }

        $taxonomy = app(RecommendationTaxonomyService::class)->all();
        $allowedExclude = array_values(array_unique(array_merge(...array_values($taxonomy))));
        foreach (['truyện kinh dị học đường tu tiên main bá hắc ám', 'truyện sảng văn không harem'] as $message) {
            $preferences = $this->parse($message);
            foreach (self::FIELDS as $field) {
                $this->assertSame([], array_values(array_diff($preferences[$field], $taxonomy[$field])),
                    $message.': '.$field.' invented a value');
            }
            $this->assertSame([], array_values(array_diff($preferences['exclude'], $allowedExclude)),
                $message.': exclude invented a value');
        }
    }

    public function test_ai_output_outside_taxonomy_is_discarded(): void
    {
        $content = [
            'genres' => ['Fantasy', 'Isekai Webtoon'],
            'themes' => ['Super Ultra God', 'system'],
            'settings' => [], 'character_traits' => [], 'tones' => [], 'relationships' => [],
            'status' => 'completed', 'exclude' => ['Fake Tag', 'Harem'],
            'needs_more_info' => false, 'follow_up_question' => null,
        ];
        $this->mock(AIClientInterface::class)->shouldReceive('complete')->once()
            ->andReturn(['success' => true, 'content' => json_encode($content), 'error' => null]);

        $result = app(IntentParser::class)->parse('tìm truyện dị giới không harem');

        $this->assertTrue($result['success']);
        $this->assertSame(['Fantasy'], $result['preferences']['genres']);
        $this->assertSame(['System'], $result['preferences']['themes']);
        $this->assertSame(['Harem'], $result['preferences']['exclude']);
        $this->assertSame('completed', $result['preferences']['status']);
    }
}
