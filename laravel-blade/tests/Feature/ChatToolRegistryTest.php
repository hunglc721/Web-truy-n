<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Library;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Services\AI\Agent\ChatToolRegistry;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatToolRegistryTest extends TestCase
{
    use RefreshDatabase;

    private ChatToolRegistry $tools;
    private Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RecommendationTaxonomySeeder::class);
        $this->genre = Genre::create(['name' => 'Fantasy']);
        $this->tools = app(ChatToolRegistry::class);
    }

    private function comic(string $title, mixed $publishedAt = 'default'): Comic
    {
        $comic = Comic::factory()->create([
            'title' => $title,
            'published_at' => $publishedAt === 'default' ? now()->subDay() : $publishedAt,
        ]);
        $comic->genres()->attach($this->genre->id);

        return $comic;
    }

    private function chapter(Comic $comic, int $number, mixed $publishedAt = 'default', ?string $content = null): Chapter
    {
        return $comic->chapters()->create([
            'chapter_number' => $number,
            'title' => 'Chương '.$number,
            'pages' => ['page-1.jpg', 'page-2.jpg'],
            'content' => $content,
            'published_at' => $publishedAt === 'default' ? now()->subDay() : $publishedAt,
        ]);
    }

    private function ids(array $result): array
    {
        return array_column($result['data'], 'id');
    }

    public function test_only_published_comics_are_visible(): void
    {
        $published = $this->comic('Đã phát hành');
        $future = $this->comic('Hẹn giờ', now()->addDay());
        $draft = $this->comic('Bản nháp', null);

        $result = $this->tools->execute('search_comics', ['genres' => ['Fantasy'], 'limit' => 8], null);
        $this->assertTrue($result['ok']);
        $this->assertSame([$published->id], $this->ids($result));

        foreach ([$future->id, $draft->id] as $id) {
            $hidden = $this->tools->execute('get_comic', ['comic_id' => $id], null);
            $this->assertFalse($hidden['ok']);
            $this->assertSame('not_found', $hidden['error']);
        }
    }

    public function test_unpublished_chapters_are_hidden_from_lists_and_reads(): void
    {
        $comic = $this->comic('Truyện Thử');
        $this->chapter($comic, 1);
        $this->chapter($comic, 2, now()->addDay());

        $list = $this->tools->execute('list_chapters', ['comic_id' => $comic->id], null);
        $this->assertTrue($list['ok']);
        $this->assertCount(1, $list['data']['chapters']);
        $this->assertSame('1', $list['data']['chapters'][0]['number']);

        $future = $this->tools->execute('get_chapter', ['comic_id' => $comic->id, 'number' => 2], null);
        $this->assertFalse($future['ok']);
        $this->assertSame('not_found', $future['error']);
    }

    public function test_decimal_chapter_numbers_are_resolved_exactly(): void
    {
        $comic = $this->comic('Truyện Thử');
        $this->chapter($comic, 1);
        $this->chapter($comic, 2);
        $comic->chapters()->create([
            'chapter_number' => '1.5',
            'title' => 'Ngoại truyện',
            'pages' => ['page-1.jpg'],
            'published_at' => now()->subDay(),
        ]);

        $half = $this->tools->execute('get_chapter', ['comic_id' => $comic->id, 'number' => '1.5'], null);
        $this->assertTrue($half['ok']);
        $this->assertSame('1.5', $half['data']['number']);
        $this->assertSame('Ngoại truyện', $half['data']['title']);

        $whole = $this->tools->execute('get_chapter', ['comic_id' => $comic->id, 'number' => 1], null);
        $this->assertTrue($whole['ok']);
        $this->assertSame('1', $whole['data']['number']);
        $this->assertNotSame($half['data']['title'], $whole['data']['title']);

        $invalid = $this->tools->execute('get_chapter', ['comic_id' => $comic->id, 'number' => '1.5abc'], null);
        $this->assertFalse($invalid['ok']);
        $this->assertSame('invalid_arguments', $invalid['error']);
    }

    public function test_list_and_user_tools_report_exact_totals_when_truncated(): void
    {
        $comic = $this->comic('Truyện Thử');
        foreach ([1, 2, 3] as $number) {
            $this->chapter($comic, $number);
        }

        $list = $this->tools->execute('list_chapters', ['comic_id' => $comic->id, 'limit' => 2], null);
        $this->assertTrue($list['ok']);
        $this->assertSame(3, $list['data']['total_published']);
        $this->assertSame(2, $list['data']['returned']);
        $this->assertTrue($list['data']['truncated']);

        $user = User::factory()->create();
        foreach ([$comic->id] as $comicId) {
            Library::create(['user_id' => $user->id, 'comic_id' => $comicId, 'added_at' => now(), 'status' => 'reading']);
        }
        Library::create(['user_id' => $user->id, 'comic_id' => $this->comic('Truyện Hai')->id, 'added_at' => now(), 'status' => 'reading']);

        $library = $this->tools->execute('get_my_library', ['limit' => 1], $user);
        $this->assertTrue($library['ok']);
        $this->assertSame(2, $library['data']['total']);
        $this->assertSame(1, $library['data']['returned']);
        $this->assertTrue($library['data']['truncated']);
    }

    public function test_chapter_text_is_capped_and_flagged(): void
    {
        $comic = $this->comic('Truyện Thử');
        $this->chapter($comic, 1, 'default', str_repeat('nội dung ', 300));

        $chapter = $this->tools->execute('get_chapter', ['comic_id' => $comic->id, 'number' => 1], null);
        $this->assertTrue($chapter['ok']);
        $this->assertTrue($chapter['data']['has_text']);
        $this->assertTrue($chapter['data']['content_truncated']);
        $this->assertLessThanOrEqual(1200, mb_strlen($chapter['data']['content_excerpt']));
    }

    public function test_guests_cannot_read_user_data(): void
    {
        foreach (['get_my_library', 'get_my_history'] as $tool) {
            $result = $this->tools->execute($tool, [], null);
            $this->assertFalse($result['ok']);
            $this->assertSame('unauthorized', $result['error']);
        }
    }

    public function test_user_tools_are_scoped_to_the_authenticated_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owned = $this->comic('Của tôi');
        $foreign = $this->comic('Của người khác');
        $chapter = $this->chapter($owned, 1);

        Library::create(['user_id' => $owner->id, 'comic_id' => $owned->id, 'added_at' => now(), 'status' => 'reading']);
        Library::create(['user_id' => $other->id, 'comic_id' => $foreign->id, 'added_at' => now(), 'status' => 'reading']);
        ReadingHistory::create([
            'user_id' => $owner->id, 'comic_id' => $owned->id, 'chapter_id' => $chapter->id,
            'scroll_percent' => 42.5, 'last_read_at' => now(),
        ]);
        ReadingHistory::create([
            'user_id' => $other->id, 'comic_id' => $foreign->id, 'chapter_id' => $chapter->id,
            'scroll_percent' => 10, 'last_read_at' => now(),
        ]);

        $library = $this->tools->execute('get_my_library', [], $owner);
        $this->assertTrue($library['ok']);
        $this->assertSame([$owned->id], array_column($library['data']['items'], 'id'));

        $history = $this->tools->execute('get_my_history', [], $owner);
        $this->assertTrue($history['ok']);
        $this->assertSame([$owned->id], array_column($history['data']['items'], 'id'));
        $this->assertSame(42.5, $history['data']['items'][0]['scroll_percent']);
    }

    public function test_unknown_tools_and_invalid_arguments_are_rejected(): void
    {
        $comic = $this->comic('Truyện Thử');
        $this->chapter($comic, 1);

        $unknown = $this->tools->execute('shell_exec', [], null);
        $this->assertSame('unknown_tool', $unknown['error']);

        foreach ([
            ['search_comics', ['unexpected' => true]],
            ['search_comics', ['genres' => ['Fantasy'], 'limit' => 99]],
            ['search_comics', ['status' => 'archived']],
            ['get_comic', []],
            ['list_chapters', ['comic_id' => $comic->id, 'order' => 'random']],
            ['get_chapter', ['comic_id' => $comic->id, 'number' => 'abc']],
            ['get_taxonomy', ['field' => 'secrets']],
        ] as [$tool, $args]) {
            $result = $this->tools->execute($tool, $args, null);
            $this->assertFalse($result['ok'], $tool);
            $this->assertSame('invalid_arguments', $result['error'], $tool);
        }
    }

    public function test_title_search_is_accent_insensitive(): void
    {
        $this->comic('Truyện Thử Nghiệm');

        $result = $this->tools->execute('search_comics', ['query' => 'truyen thu nghiem'], null);
        $this->assertTrue($result['ok']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('Truyện Thử Nghiệm', $result['data'][0]['title']);
        $this->assertNull($result['data'][0]['score']);
    }

    public function test_taxonomy_tool_returns_only_allowed_values(): void
    {
        $result = $this->tools->execute('get_taxonomy', [], null);
        $this->assertTrue($result['ok']);
        $this->assertContains('Fantasy', $result['data']['genres']);
        $this->assertContains('Harem', $result['data']['relationships']);
        $this->assertContains('completed', $result['data']['statuses']);
        $this->assertArrayNotHasKey('users', $result['data']);
    }
}
