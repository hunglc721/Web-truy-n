<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\RecommendationPreference;
use App\Models\Comic;
use App\Models\User;
use App\Services\Benchmark\BenchmarkFixture;
use App\Services\Benchmark\BenchmarkMeasurement;
use App\Services\PreferenceRecommendationService;
use App\Services\RecommendationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use JsonException;
use PDO;
use Throwable;

class BenchmarkRecommendationCommand extends Command
{
    protected $signature = 'benchmark:recommendation
        {--sizes=100,500,1200,5000 : Comma separated candidate counts}
        {--iterations=5 : Timed samples per case}
        {--format=table : Output format: table or json}
        {--output= : Write the full JSON report to this path}
        {--force : Allow running in the production environment}';

    protected $description = 'Benchmark RecommendationService and PreferenceRecommendationService (time, SQL count, EXISTS, hydration)';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to run the recommendation benchmark in production without --force.');

            return self::FAILURE;
        }

        if (! Schema::hasTable('comics')) {
            $this->error('The comics table does not exist. Run migrations against a disposable database first.');

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

        try {
            $connection->beginTransaction();
            try {
                foreach ($sizes as $size) {
                    $fixture = new BenchmarkFixture('bench-rec');
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
                }
            } finally {
                while ($connection->transactionLevel() > $startingTransactionLevel) {
                    $connection->rollBack();
                }
            }
        } catch (Throwable $exception) {
            $this->error('Recommendation benchmark failed: '.$exception::class.': '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            $cache->driver('array')->flush();
            $cache->setDefaultDriver($originalCacheDriver);
        }

        $report = [
            'schema_version' => 1,
            'benchmark' => 'recommendation',
            'environment' => $this->environment($iterations),
            'results' => $results,
            'notes' => [
                'Timing excludes query logging; SQL diagnostics come from a separate single pass.',
                'cold means the cache was flushed before every timed sample; warm means the first call populated the cache.',
                'Synthetic comics are created inside a transaction that is always rolled back.',
                'candidates is the number of synthetic comics; hidden comics are publication-filtered by the production services.',
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

    /**
     * @return list<array<string, mixed>>
     */
    private function cases(BenchmarkFixture $fixture, array $meta, BenchmarkMeasurement $measurement, int $iterations): array
    {
        $recommendations = app(RecommendationService::class);
        $preferences = app(PreferenceRecommendationService::class);
        $userId = (int) $meta['user_id'];
        $comicId = (int) $meta['comic_id'];
        $user = User::query()->find($userId);
        $comic = Comic::query()->find($comicId);

        $cases = [];
        $flush = static fn () => Cache::flush();

        if ($user !== null) {
            $cases[] = $this->case('forUser', 'cold', 0, $measurement->measure(
                fn () => $recommendations->forUser($user, 6)->count(),
                $iterations,
                1,
                $flush,
            ));
            $cases[] = $this->case('forUser', 'warm', 0, $measurement->measure(
                fn () => $recommendations->forUser($user, 6)->count(),
                $iterations,
                1,
            ));
        }

        if ($comic !== null) {
            $cases[] = $this->case('forComic', 'cold', 0, $measurement->measure(
                fn () => $recommendations->forComic($comic, 6)->count(),
                $iterations,
                1,
                $flush,
            ));
            $cases[] = $this->case('forComic', 'warm', 0, $measurement->measure(
                fn () => $recommendations->forComic($comic, 6)->count(),
                $iterations,
                1,
            ));
        }

        $cases[] = $this->case('forGuest', 'cold', 0, $measurement->measure(
            fn () => $recommendations->forGuest(6)->count(),
            $iterations,
            1,
            $flush,
        ));
        $cases[] = $this->case('forGuest', 'warm', 0, $measurement->measure(
            fn () => $recommendations->forGuest(6)->count(),
            $iterations,
            1,
        ));

        foreach ($this->preferenceScenarios($fixture) as $name => $data) {
            $preference = RecommendationPreference::fromArray($data);
            $conditions = $this->conditionCount($data);
            $cases[] = $this->case('preference:'.$name, 'none', $conditions, $measurement->measure(
                fn () => $preferences->recommend($preference, 8)->count(),
                $iterations,
                1,
            ));
        }

        return $cases;
    }

    /** @return array<string, array<string, mixed>> */
    private function preferenceScenarios(BenchmarkFixture $fixture): array
    {
        $genres = $fixture->genreNames();
        $tags = $fixture->tagNames();
        $themes = $tags['theme'] ?? [];
        $settings = $tags['setting'] ?? [];
        $characters = $tags['character'] ?? [];
        $tones = $tags['tone'] ?? [];

        return [
            '1_genre' => ['genres' => array_slice($genres, 0, 1)],
            '2_genres_2_tags' => [
                'genres' => array_slice($genres, 0, 2),
                'themes' => array_slice($themes, 0, 1),
                'tones' => array_slice($tones, 0, 1),
            ],
            '4_genres_6_tags' => [
                'genres' => array_slice($genres, 0, 4),
                'themes' => array_slice($themes, 0, 2),
                'settings' => array_slice($settings, 0, 2),
                'character_traits' => array_slice($characters, 0, 1),
                'tones' => array_slice($tones, 0, 1),
            ],
            '12_conditions' => [
                'genres' => array_slice($genres, 0, 4),
                'themes' => array_slice($themes, 0, 2),
                'settings' => array_slice($settings, 0, 2),
                'character_traits' => array_slice($characters, 0, 2),
                'tones' => array_slice($tones, 0, 2),
            ],
            '8_genres_partial_fallback' => ['genres' => array_slice($genres, 0, 8)],
            'status_only' => ['status' => 'ongoing'],
            'exclusions' => [
                'genres' => array_slice($genres, 0, 2),
                'exclude' => array_slice($genres, 2, 2),
            ],
        ];
    }

    private function conditionCount(array $data): int
    {
        $count = 0;
        foreach (['genres', 'themes', 'settings', 'character_traits', 'tones', 'relationships'] as $field) {
            $count += count((array) ($data[$field] ?? []));
        }

        return $count;
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
            // SQLite in-memory and some drivers do not expose a server version.
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
            '  %-30s cache=%-4s cond=%-2d median=%8.3f ms p95=%8.3f ms queries=%-3d exists=%-3d results=%s',
            $case['case'],
            $case['cache'],
            $case['conditions'],
            $timing['median'],
            $timing['p95'],
            $case['queries'],
            $case['sql']['exists'],
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
