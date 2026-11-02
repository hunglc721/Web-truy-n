<?php

declare(strict_types=1);

namespace App\Services\Benchmark;

use App\Helpers\VietnameseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Synthetic, prefix-scoped catalog used by the performance benchmarks.
 *
 * Everything is created inside the caller's transaction and removed again by
 * the command's rollback. Names are deterministic so two runs on the same
 * candidate count produce the same SQL shapes and result counts.
 */
final class BenchmarkFixture
{
    public const GENRE_COUNT = 16;

    public const TAG_COUNT_PER_CATEGORY = 4;

    /** @var list<int> */
    private array $comicIds = [];

    /** @var array<int, int> comic id => first published chapter id */
    private array $firstChapterIds = [];

    /** @var list<string> */
    private array $genreNames = [];

    /** @var array<string, list<string>> */
    private array $tagNames = [];

    /** @var list<int> */
    private array $authorIds = [];

    private ?int $userId = null;

    public function __construct(private readonly string $prefix = 'bench')
    {
    }

    /**
     * @return array{
     *     prefix: string,
     *     comics: int,
     *     published: int,
     *     hidden: int,
     *     chapters: int,
     *     user_id: int,
     *     comic_id: int,
     *     genre_names: list<string>,
     *     tag_names: array<string, list<string>>,
     *     elapsed_ms: float
     * }
     */
    public function seed(int $comics, int $interactedComics = 50): array
    {
        $comics = max(1, $comics);
        $startedAt = hrtime(true);

        $this->purge();
        $this->seedTaxonomy();
        $this->seedAuthors();
        $this->seedComics($comics);
        $this->seedChapters(min(200, $comics), min(200, $comics));
        $this->seedRecommendations(min(40, $comics), $comics);
        $this->seedUserAndActivity(min($interactedComics, $comics));

        return [
            'prefix' => $this->prefix,
            'comics' => count($this->comicIds),
            'published' => count($this->comicIds) - $this->hiddenCount,
            'hidden' => $this->hiddenCount,
            'chapters' => $this->chapterCount,
            'user_id' => (int) $this->userId,
            'comic_id' => $this->comicIds[0] ?? 0,
            'genre_names' => $this->genreNames,
            'tag_names' => $this->tagNames,
            'elapsed_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 3),
        ];
    }

    /** @return list<int> */
    public function comicIds(): array
    {
        return $this->comicIds;
    }

    /** @return array<int, int> */
    public function firstChapterIds(): array
    {
        return $this->firstChapterIds;
    }

    /** @return list<string> */
    public function genreNames(): array
    {
        return $this->genreNames;
    }

    /** @return array<string, list<string>> */
    public function tagNames(): array
    {
        return $this->tagNames;
    }

    private int $hiddenCount = 0;

    private int $chapterCount = 0;

    private function purge(): void
    {
        $pattern = $this->prefix.'-%';
        $comicIds = DB::table('comics')->where('slug', 'like', $pattern)->pluck('id')->all();

        if ($comicIds !== []) {
            DB::table('reading_histories')->whereIn('comic_id', $comicIds)->delete();
            DB::table('libraries')->whereIn('comic_id', $comicIds)->delete();
            DB::table('comic_recommendations')->whereIn('comic_id', $comicIds)->delete();
            DB::table('comic_recommendations')->whereIn('recommended_comic_id', $comicIds)->delete();
            DB::table('comic_genre')->whereIn('comic_id', $comicIds)->delete();
            DB::table('comic_tag')->whereIn('comic_id', $comicIds)->delete();
            DB::table('comic_author')->whereIn('comic_id', $comicIds)->delete();
            DB::table('chapters')->whereIn('comic_id', $comicIds)->delete();
            DB::table('comics')->whereIn('id', $comicIds)->delete();
        }

        DB::table('authors')->where('slug', 'like', $pattern)->delete();
        DB::table('genres')->where('slug', 'like', $pattern)->delete();
        DB::table('tags')->where('slug', 'like', $pattern)->delete();
        DB::table('users')->where('email', $this->prefix.'-bench@example.test')->delete();

        $this->comicIds = [];
        $this->firstChapterIds = [];
        $this->genreNames = [];
        $this->tagNames = [];
        $this->authorIds = [];
        $this->userId = null;
        $this->hiddenCount = 0;
        $this->chapterCount = 0;
    }

    private function seedTaxonomy(): void
    {
        $now = now();
        $genres = [];
        for ($index = 1; $index <= self::GENRE_COUNT; $index++) {
            $name = 'Bench Genre '.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $this->genreNames[] = $name;
            $genres[] = [
                'name' => $name,
                'slug' => $this->prefix.'-genre-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'icon' => null,
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('genres')->insert($genres);

        $categories = ['theme', 'setting', 'character', 'tone', 'relationship'];
        $tags = [];
        foreach ($categories as $category) {
            $this->tagNames[$category] = [];
            for ($index = 1; $index <= self::TAG_COUNT_PER_CATEGORY; $index++) {
                $name = 'Bench '.ucfirst($category).' '.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
                $this->tagNames[$category][] = $name;
                $tags[] = [
                    'name' => $name,
                    'slug' => $this->prefix.'-'.Str::slug($category).'-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                    'color' => null,
                    'category' => $category,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        DB::table('tags')->insert($tags);
    }

    private function seedAuthors(): void
    {
        $now = now();
        $authors = [];
        for ($index = 1; $index <= 20; $index++) {
            $authors[] = [
                'name' => 'Tác Giả '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'slug' => $this->prefix.'-tac-gia-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'bio' => null,
                'avatar' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('authors')->insert($authors);
        $this->authorIds = DB::table('authors')
            ->where('slug', 'like', $this->prefix.'-tac-gia-%')
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    private function seedComics(int $count): void
    {
        $genreIds = DB::table('genres')->where('slug', 'like', $this->prefix.'-genre-%')->orderBy('id')->pluck('id')->all();
        $tagIdsByCategory = [];
        foreach (['theme', 'setting', 'character', 'tone', 'relationship'] as $category) {
            $tagIdsByCategory[$category] = DB::table('tags')
                ->where('slug', 'like', $this->prefix.'-'.$category.'-%')
                ->orderBy('id')
                ->pluck('id')
                ->all();
        }

        $now = now();
        $publishedAt = $now->copy()->subDays(30)->toDateString();
        $futureAt = $now->copy()->addDays(30)->toDateString();
        $statuses = ['ongoing', 'completed', 'hiatus', 'cancelled'];
        $countries = ['JP', 'KR', 'CN', 'VN'];
        $chunk = [];

        for ($index = 1; $index <= $count; $index++) {
            $hidden = $index % 10 === 0;
            if ($hidden) {
                $this->hiddenCount++;
            }
            $status = $statuses[$index % count($statuses)];
            $title = 'Bench Truyện '.$index.' Tu Tiên';
            $description = 'Mô tả benchmark cho bộ truyện số '.$index.', hành động và phiêu lưu.';
            $chunk[] = [
                'title' => $title,
                'title_normalized' => VietnameseHelper::removeAccents($title),
                'alt_titles' => 'Bench Alt '.$index,
                'alt_titles_normalized' => VietnameseHelper::removeAccents('Bench Alt '.$index),
                'slug' => $this->prefix.'-comic-'.$index,
                'cover_image' => 'bench/cover.jpg',
                'description' => $description,
                'description_normalized' => VietnameseHelper::removeAccents($description),
                'status' => $status,
                'country' => $countries[$index % count($countries)],
                'released_year' => 2000 + ($index % 24),
                'is_original' => $index % 3 === 0 ? 1 : 0,
                'is_featured' => $index % 7 === 0 ? 1 : 0,
                'views' => ($count - $index) * 10,
                'likes_count' => $index % 50,
                'comments_count' => $index % 20,
                'avg_rating' => round(1.0 + (($index * 7) % 40) / 10, 1),
                'rating_avg' => round(1.0 + (($index * 7) % 40) / 10, 1),
                'total_ratings' => $index % 100,
                'rating_count' => $index % 100,
                'views_count' => ($count - $index) * 10,
                'trending_rank' => $index <= 50 ? $index : null,
                'published_at' => $hidden ? ($index % 20 === 0 ? null : $futureAt) : $publishedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($chunk) >= 500) {
                DB::table('comics')->insert($chunk);
                $chunk = [];
            }
        }
        if ($chunk !== []) {
            DB::table('comics')->insert($chunk);
        }

        $this->comicIds = DB::table('comics')
            ->where('slug', 'like', $this->prefix.'-comic-%')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $genreRows = [];
        $tagRows = [];
        $authorRows = [];
        foreach ($this->comicIds as $offset => $comicId) {
            $index = $offset + 1;

            $genrePicks = [
                $genreIds[$index % count($genreIds)],
                $genreIds[($index * 3 + 1) % count($genreIds)],
                $genreIds[($index * 7 + 2) % count($genreIds)],
            ];
            // Keep a deterministic full-match population for multi-genre queries.
            if ($index % 25 === 0 && count($genreIds) >= 4) {
                $genrePicks = array_slice($genreIds, 0, 4);
            }
            foreach (array_values(array_unique($genrePicks)) as $position => $genreId) {
                $genreRows[] = ['comic_id' => $comicId, 'genre_id' => $genreId, 'is_primary' => $position === 0 ? 1 : 0];
            }

            $seenTags = [];
            foreach ($tagIdsByCategory as $category => $ids) {
                $tagId = $ids[$index % count($ids)];
                $seenTags[$tagId] = true;
                if ($index % 3 === 0) {
                    $seenTags[$ids[($index + 1) % count($ids)]] = true;
                }
                // Keep a deterministic full-match population for multi-tag queries.
                if ($index % 25 === 0 && count($ids) >= 3) {
                    foreach (array_slice($ids, 0, 3) as $extraId) {
                        $seenTags[$extraId] = true;
                    }
                }
            }
            foreach (array_keys($seenTags) as $tagId) {
                $tagRows[] = ['comic_id' => $comicId, 'tag_id' => $tagId];
            }

            if ($this->authorIds !== []) {
                $authorRows[] = [
                    'comic_id' => $comicId,
                    'author_id' => $this->authorIds[$index % count($this->authorIds)],
                    'role' => 'both',
                ];
            }
        }

        foreach (array_chunk($genreRows, 1000) as $rows) {
            DB::table('comic_genre')->insert($rows);
        }
        foreach (array_chunk($tagRows, 1000) as $rows) {
            DB::table('comic_tag')->insert($rows);
        }
        foreach (array_chunk($authorRows, 1000) as $rows) {
            DB::table('comic_author')->insert($rows);
        }
    }

    private function seedChapters(int $comicLimit, int $interactedLimit): void
    {
        $now = now();
        $publishedAt = $now->copy()->subDays(10);
        $rows = [];
        $comicIds = array_slice($this->comicIds, 0, $comicLimit);

        foreach ($comicIds as $comicId) {
            for ($number = 1; $number <= 3; $number++) {
                $rows[] = [
                    'comic_id' => $comicId,
                    'chapter_number' => $number,
                    'title' => 'Chương '.$number,
                    'slug' => $this->prefix.'-'.$comicId.'-'.$number,
                    'pages' => json_encode(['bench/'.$comicId.'-'.$number.'-1.jpg'], JSON_THROW_ON_ERROR),
                    'content' => null,
                    'views' => 0,
                    'published_at' => $publishedAt,
                    'is_free' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('chapters')->insert($chunk);
        }
        $this->chapterCount = count($rows);

        $chapters = DB::table('chapters')
            ->whereIn('comic_id', array_slice($this->comicIds, 0, $interactedLimit))
            ->orderBy('comic_id')
            ->orderBy('chapter_number')
            ->get(['id', 'comic_id']);
        foreach ($chapters as $chapter) {
            $this->firstChapterIds[(int) $chapter->comic_id] ??= (int) $chapter->id;
        }
    }

    private function seedRecommendations(int $sourceLimit, int $comicCount): void
    {
        if ($comicCount < 2) {
            return;
        }
        $rows = [];
        foreach (array_slice($this->comicIds, 0, $sourceLimit) as $offset => $comicId) {
            $seen = [$comicId => true];
            for ($step = 1; $step <= 5 && count($seen) <= $comicCount; $step++) {
                $target = $this->comicIds[($offset + $step) % $comicCount];
                if (isset($seen[$target])) {
                    continue;
                }
                $seen[$target] = true;
                $rows[] = [
                    'comic_id' => $comicId,
                    'recommended_comic_id' => $target,
                    'score' => round(1.0 - ($step / 10), 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('comic_recommendations')->insert($chunk);
        }
    }

    private function seedUserAndActivity(int $interactedComics): void
    {
        $now = now();
        $this->userId = (int) DB::table('users')->insertGetId([
            'name' => 'Bench User',
            'email' => $this->prefix.'-bench@example.test',
            'is_admin' => 0,
            'password' => 'bench',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $comicIds = array_slice($this->comicIds, 0, $interactedComics);
        $historyRows = [];
        $libraryRows = [];
        foreach ($comicIds as $comicId) {
            $chapterId = $this->firstChapterIds[$comicId] ?? null;
            if ($chapterId !== null) {
                $historyRows[] = [
                    'user_id' => $this->userId,
                    'comic_id' => $comicId,
                    'chapter_id' => $chapterId,
                    'last_read_at' => $now->copy()->subMinutes(count($historyRows) + 1),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $libraryRows[] = [
                'user_id' => $this->userId,
                'comic_id' => $comicId,
                'last_read_chapter_id' => $chapterId,
                'status' => 'reading',
                'added_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($historyRows, 500) as $rows) {
            DB::table('reading_histories')->insert($rows);
        }
        foreach (array_chunk($libraryRows, 500) as $rows) {
            DB::table('libraries')->insert($rows);
        }
    }
}
