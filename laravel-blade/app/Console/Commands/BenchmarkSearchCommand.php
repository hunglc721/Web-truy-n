<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Benchmark\BenchmarkFixture;
use App\Services\Benchmark\BenchmarkMeasurement;
use App\Services\SearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use JsonException;
use PDO;
use Throwable;

class BenchmarkSearchCommand extends Command
{
    protected $signature = 'benchmark:search
        {--sizes=100,500,1200,5000 : Comma separated candidate counts}
        {--iterations=5 : Timed samples per case}
        {--format=table : Output format: table or json}
        {--output= : Write the full JSON report to this path}
        {--explain : Include a MySQL EXPLAIN plan for a representative keyword query}
        {--force : Allow running in the production environment}';

    protected $description = 'Benchmark SearchService live/advanced search (time, SQL count, description_normalized, cache)';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to run the search benchmark in production without --force.');

            return self::FAILURE;
        }

        if (! Schema::hasTable('comics')) {
            $this->error('The comics table does not exist. Run migrations against a disposable database first.');

            return self::FAILURE;
        }
        if (! Schema::hasColumn('comics', 'description_normalized')) {
            $this->error('The description_normalized column is missing. Run migrations first.');

            return self::FAILURE;
        }

        $sizes = $this->sizes();
        if ($sizes === []) {
            $this->error('--sizes must contain at least one positive integer.');

            return self::FAILURE;
        }
        $format = (string) $this->option('format');
        if (! in_array($format, ['table', 'json'], true)) {
            $this->error('--format must be table or json.');

            return self::FAILURE;
        }
        $iterations = max(1, min(30, (int) $this->option('iterations')));

        $cache = app('cache');
        $originalCacheDriver = $cache->getDefaultDriver();
        $cache->setDefaultDriver('array');
        $cache->driver('array')->flush();

        $connection = DB::connection();
        $startingTransactionLevel = $connection->transactionLevel();
        $measurement = app(BenchmarkMeasurement::class);
        $results = [];
        $diagnostics = [];
        $largestSize = max($sizes);
        $explain = null;

        try {
            $connection->beginTransaction();
            try {
                foreach ($sizes as $size) {
                    $fixture = new BenchmarkFixture('bench-search');
                    $fixtureMeta = $fixture->seed($size);
                    if ($format !== 'json') {
                        $this->line("Seeded {$fixtureMeta['comics']} candidates in {$fixtureMeta['elapsed_ms']} ms");
                    }

                    foreach ($this->cases($fixture, $fixtureMeta, $measurement, $iterations) as $case) {
                        $results[] = ['candidates' => $size] + $case + ['seed' => $fixtureMeta['elapsed_ms']];
                        if ($format !== 'json') {
                            $this->renderCase($case);
                        }
                    }

                    // EXPLAIN must run before the fixture transaction is rolled
                    // back, otherwise the plan is measured against an empty table.
                    if ($this->option('explain') && $size === $largestSize) {
                        $explain = $this->explain();
                    }

                    $diagnostics[$size] = [
                        'cache_invalidation' => $this->cacheInvalidation($fixture),
                        'description_column' => $this->descriptionColumn($fixture, $measurement, $iterations),
                    ];
                }
            } finally {
                while ($connection->transactionLevel() > $startingTransactionLevel) {
                    $connection->rollBack();
                }
            }
        } catch (Throwable $exception) {
            $this->error('Search benchmark failed: '.$exception::class.': '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            $cache->driver('array')->flush();
            $cache->setDefaultDriver($originalCacheDriver);
        }

        $report = [
            'schema_version' => 1,
            'benchmark' => 'search',
            'environment' => $this->environment($iterations),
            'results' => $results,
            'diagnostics' => [
                'per_size' => $diagnostics,
                'explain' => $explain,
            ],
            'notes' => [
                'liveSearch is cached by normalized keyword + limit for 300 seconds; cold flushes the cache before every sample.',
                'advancedSearch is not cached and records a search_keywords write when q is present.',
                'min_chapters uses a published-chapter EXISTS count, not the chapters_count alias.',
                'Synthetic comics are created inside a transaction that is always rolled back.',
            ],
        ];

        return $this->emit($report, $format);
    }

    /** @return list<int> */
    private function sizes(): array
    {
        $values = array_filter(array_map('trim', explode(',', (string) $this->option('sizes'))), fn (string $value): bool => $value !== '');
        $sizes = [];
        foreach ($values as $value) {
            if (! ctype_digit($value) || (int) $value < 1) {
                continue;
            }
            $sizes[] = (int) $value;
        }

        return array_values(array_unique($sizes));
    }

    /** @return list<array<string, mixed>> */
    private function cases(BenchmarkFixture $fixture, array $meta, BenchmarkMeasurement $measurement, int $iterations): array
    {
        $search = app(SearchService::class);
        $genres = $fixture->genreNames();
        $tags = $fixture->tagNames();
        $cases = [];
        $flush = static fn () => Cache::flush();

        $live = [
            'live:title_accented' => fn () => $search->liveSearch('Bench Truyện 42', 8),
            'live:title_unaccented' => fn () => $search->liveSearch('bench truyen 42', 8),
            'live:partial' => fn () => $search->liveSearch('Truyện 42', 8),
            'live:description_unaccented' => fn () => $search->liveSearch('phieu luu', 8),
            'live:author_unaccented' => fn () => $search->liveSearch('tac gia 07', 8),
            'live:genre' => fn () => $search->liveSearch('Bench Genre 03', 8),
            'live:short_input' => fn () => $search->liveSearch('a', 8),
        ];
        foreach ($live as $name => $callback) {
            $cases[] = $this->case($name, 'cold', 0, $measurement->measure(
                static fn () => $callback()->count(),
                $iterations,
                1,
                $flush,
            ));
        }
        // One warm case is enough to prove the cache-hit path; every keyword has
        // its own cache key, so the others are expected to behave the same.
        $cases[] = $this->case('live:title_accented', 'warm', 0, $measurement->measure(
            fn () => $search->liveSearch('Bench Truyện 42', 8)->count(),
            $iterations,
            1,
        ));

        $advanced = [
            'advanced:keyword_accented' => ['q' => 'Bench Truyện 42'],
            'advanced:keyword_unaccented' => ['q' => 'bench truyen 42'],
            'advanced:description_accented' => ['q' => 'phiêu lưu'],
            'advanced:description_unaccented' => ['q' => 'phieu luu'],
            'advanced:author_unaccented' => ['q' => 'tac gia 07'],
            'advanced:genres_and_3' => ['genres' => array_slice($genres, 0, 3), 'genre_mode' => 'and'],
            'advanced:genres_or_4' => ['genres' => array_slice($genres, 0, 4), 'genre_mode' => 'or'],
            'advanced:tags_and_3' => ['tags' => array_slice($tags['theme'] ?? [], 0, 3)],
            'advanced:exclude_genres' => ['exclude_genres' => array_slice($genres, 0, 3)],
            'advanced:filters_combo' => [
                'status' => 'ongoing',
                'country' => 'JP',
                'year' => 2010,
                'min_rating' => 3.0,
                'is_original' => true,
                'sort' => 'rating',
            ],
            'advanced:min_chapters' => ['min_chapters' => 3],
            'advanced:sort_latest' => ['sort' => 'latest'],
            'advanced:sort_trending' => ['sort' => 'trending'],
            'advanced:sort_alphabetical' => ['sort' => 'alphabetical'],
            'advanced:per_page_50' => ['per_page' => 50],
        ];
        foreach ($advanced as $name => $params) {
            $cases[] = $this->case($name, 'none', 0, $measurement->measure(
                static fn () => $search->advancedSearch($params)->count(),
                $iterations,
                1,
            ));
        }

        return $cases;
    }

    /**
     * Proves the current invalidation contract: live search cache is TTL-only.
     *
     * @return array{query: string, stale_after_update: bool, refreshed_after_flush: bool, ttl_seconds: int}
     */
    private function cacheInvalidation(BenchmarkFixture $fixture): array
    {
        $search = app(SearchService::class);
        $comicId = $fixture->comicIds()[0] ?? 0;
        $query = 'Truyện 1 Tu Tiên';

        Cache::flush();
        $before = $search->liveSearch($query, 8)->first();
        $beforeTitle = $before?->title;

        if ($comicId > 0) {
            DB::table('comics')->where('id', $comicId)->update([
                'title' => 'Bench Truyện 1 Tu Tiên Changed',
                'title_normalized' => 'bench truyen 1 tu tien changed',
                'updated_at' => now(),
            ]);
        }

        $afterUpdate = $search->liveSearch($query, 8)->first()?->title;
        Cache::flush();
        $afterFlush = $search->liveSearch($query, 8)->first()?->title;

        if ($comicId > 0) {
            DB::table('comics')->where('id', $comicId)->update([
                'title' => 'Bench Truyện 1 Tu Tiên',
                'title_normalized' => 'bench truyen 1 tu tien',
                'updated_at' => now(),
            ]);
        }

        return [
            'query' => $query,
            'stale_after_update' => $beforeTitle !== null && $afterUpdate === $beforeTitle,
            'refreshed_after_flush' => $afterFlush === 'Bench Truyện 1 Tu Tiên Changed',
            'ttl_seconds' => 300,
        ];
    }

    /**
     * Compare the accented description column with the normalized one.
     * This isolates the migration's effect from the rest of advancedSearch.
     */
    private function descriptionColumn(BenchmarkFixture $fixture, BenchmarkMeasurement $measurement, int $iterations): array
    {
        $base = fn () => DB::table('comics')
            ->whereNull('deleted_at')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        $accented = $measurement->measure(
            fn () => $base()->where('description', 'like', '%phiêu lưu%')->count(),
            $iterations,
            1,
        );
        $normalized = $measurement->measure(
            fn () => $base()->where('description_normalized', 'like', '%phieu luu%')->count(),
            $iterations,
            1,
        );

        return [
            'accented' => [
                'median_ms' => $accented['timing_ms']['median'],
                'queries' => $accented['queries'],
                'matches' => (int) $accented['result'],
            ],
            'normalized' => [
                'median_ms' => $normalized['timing_ms']['median'],
                'queries' => $normalized['queries'],
                'matches' => (int) $normalized['result'],
            ],
        ];
    }

    private function case(string $name, string $cache, int $conditions, array $measurement): array
    {
        return [
            'case' => $name,
            'cache' => $cache,
            'conditions' => $conditions,
            'result_count' => is_numeric($measurement['result']) ? (int) $measurement['result'] : null,
            'timing_ms' => $measurement['timing_ms'],
            'queries' => $measurement['queries'],
            'sql' => $measurement['sql'],
        ];
    }

    private function environment(int $iterations): array
    {
        $connection = DB::connection();
        $version = null;
        try {
            $version = (string) $connection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (Throwable) {
            // Some drivers do not expose a server version.
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'app_env' => app()->environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'git_head' => $this->gitHead(),
            'db_connection' => $connection->getName(),
            'db_database' => config('database.connections.'.$connection->getName().'.database'),
            'db_server_version' => $version,
            'cache_driver' => 'array',
            'iteration_default' => $iterations,
            'os' => PHP_OS_FAMILY,
        ];
    }

    private function explain(): ?array
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return ['available' => false, 'reason' => 'EXPLAIN capture is only implemented for MySQL.'];
        }

        try {
            $normalize = static fn (array $rows): array => array_map(
                static fn (object $row): array => (array) $row,
                $rows,
            );

            return [
                'available' => true,
                'plans' => [
                    'keyword_or_accented' => [
                        'query' => 'title/description keyword search with leading wildcard LIKE, sorted by views',
                        'rows' => $normalize(DB::select(
                            'EXPLAIN SELECT id FROM comics
                             WHERE deleted_at IS NULL
                               AND published_at IS NOT NULL AND published_at <= ?
                               AND (title LIKE ? OR title_normalized LIKE ? OR description LIKE ? OR description_normalized LIKE ?)
                             ORDER BY views DESC LIMIT 18',
                            [now(), '%truyen%', '%truyen%', '%truyen%', '%truyen%'],
                        )),
                    ],
                    'keyword_or_accented_without_order' => [
                        'query' => 'same keyword predicates without ORDER BY, exposing the full scan',
                        'rows' => $normalize(DB::select(
                            'EXPLAIN SELECT id FROM comics
                             WHERE deleted_at IS NULL
                               AND published_at IS NOT NULL AND published_at <= ?
                               AND (title LIKE ? OR title_normalized LIKE ? OR description LIKE ? OR description_normalized LIKE ?)',
                            [now(), '%truyen%', '%truyen%', '%truyen%', '%truyen%'],
                        )),
                    ],
                    'description_normalized_only' => [
                        'query' => 'description_normalized leading wildcard LIKE (the P9 column)',
                        'rows' => $normalize(DB::select(
                            'EXPLAIN SELECT id FROM comics
                             WHERE deleted_at IS NULL
                               AND published_at IS NOT NULL AND published_at <= ?
                               AND description_normalized LIKE ?',
                            [now(), '%phieu luu%'],
                        )),
                    ],
                ],
            ];
        } catch (Throwable $exception) {
            return ['available' => false, 'reason' => $exception::class];
        }
    }

    private function gitHead(): ?string
    {
        try {
            $result = Process::path(base_path())->timeout(10)->run(['git', 'rev-parse', '--short', 'HEAD']);
            $commit = trim($result->output());

            return $result->successful() && $commit !== '' ? $commit : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function renderCase(array $case): void
    {
        $timing = $case['timing_ms'];
        $this->line(sprintf(
            '  %-32s cache=%-4s median=%8.3f ms p95=%8.3f ms queries=%-3d writes=%-2d results=%s',
            $case['case'],
            $case['cache'],
            $timing['median'],
            $timing['p95'],
            $case['queries'],
            $case['sql']['writes'],
            $case['result_count'] ?? 'n/a',
        ));
    }

    private function emit(array $report, string $format): int
    {
        if ($this->option('output') !== null) {
            $path = $this->absolutePath((string) $this->option('output'));
            $directory = dirname($path);
            if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
                $this->error("Unable to create output directory: {$directory}");

                return self::FAILURE;
            }
            try {
                $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                $this->error('Unable to encode the JSON report: '.$exception->getMessage());

                return self::FAILURE;
            }
            if (file_put_contents($path, $json.PHP_EOL, LOCK_EX) === false) {
                $this->error("Unable to write the report: {$path}");

                return self::FAILURE;
            }
            if ($format !== 'json') {
                $this->line("Full JSON report written to {$path}");
            }
        }

        if ($format === 'json') {
            try {
                $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            } catch (JsonException $exception) {
                $this->error('Unable to encode JSON output: '.$exception->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function absolutePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }
}
