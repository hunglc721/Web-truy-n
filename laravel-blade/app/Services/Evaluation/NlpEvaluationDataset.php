<?php

declare(strict_types=1);

namespace App\Services\Evaluation;

use App\Services\RecommendationTaxonomyService;
use InvalidArgumentException;
use JsonException;

/**
 * Loads and validates the self-contained NLP evaluation dataset.
 *
 * Validation runs before any database work so a malformed label file fails
 * fast instead of silently scoring against missing ground truth.
 */
final class NlpEvaluationDataset
{
    public const INTENTS = ['lookup', 'preferences', 'clarify', 'invalid'];

    public const SLOT_FIELDS = ['genres', 'themes', 'settings', 'character_traits', 'tones', 'relationships'];

    public const SEARCH_ENGINES = ['live', 'advanced', 'recommendation'];

    public const TAG_CATEGORIES = ['theme', 'setting', 'character', 'tone', 'relationship'];

    /** @var list<array<string, mixed>> */
    private array $cases;

    /** @var list<array<string, mixed>> */
    private array $comics;

    /** @var list<string> */
    private array $genres;

    /** @var array<string, array<string, mixed>> */
    private array $comicsByKey = [];

    private function __construct(
        private array $data,
        private string $checksum,
        private ?string $source = null,
    ) {
        $version = $data['version'] ?? null;
        if (! is_int($version) || $version < 1) {
            throw new InvalidArgumentException('version must be a positive integer.');
        }
        $k = $data['k'] ?? null;
        if (! is_int($k) || $k < 1 || $k > 50) {
            throw new InvalidArgumentException('k must be an integer between 1 and 50.');
        }
        $this->genres = $this->validatedGenres($data['corpus']['genres'] ?? null);
        $this->comics = $this->validatedComics($data['corpus']['comics'] ?? null);
        foreach ($this->comics as $comic) {
            $this->comicsByKey[$comic['key']] = $comic;
        }
        $this->cases = $this->validatedCases($data['cases'] ?? null);
    }

    public static function fromFile(string $path): self
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("NLP evaluation dataset not found or unreadable: {$path}");
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new InvalidArgumentException("Unable to read NLP evaluation dataset: {$path}");
        }
        try {
            $data = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException("Invalid JSON in {$path}: {$exception->getMessage()}", 0, $exception);
        }
        if (! is_array($data)) {
            throw new InvalidArgumentException('NLP evaluation dataset must decode to a JSON object.');
        }

        return new self($data, hash('sha256', $contents), $path);
    }

    public static function fromArray(array $data): self
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return new self($data, hash('sha256', $encoded));
    }

    public function version(): int
    {
        return $this->data['version'];
    }

    public function k(): int
    {
        return $this->data['k'];
    }

    public function checksum(): string
    {
        return $this->checksum;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function description(): ?string
    {
        return isset($this->data['description']) && is_string($this->data['description'])
            ? $this->data['description'] : null;
    }

    /** @return list<string> */
    public function genres(): array
    {
        return $this->genres;
    }

    /** @return list<array<string, mixed>> */
    public function comics(): array
    {
        return $this->comics;
    }

    /** @return array<string, mixed>|null */
    public function comicByKey(string $key): ?array
    {
        return $this->comicsByKey[$key] ?? null;
    }

    public function isPublished(string $key): bool
    {
        $offset = $this->comicsByKey[$key]['published_offset_days'] ?? null;

        return is_int($offset) && $offset <= 0;
    }

    /** @return list<string> */
    public function unpublishedKeys(): array
    {
        return array_values(array_filter(array_keys($this->comicsByKey), fn (string $key): bool => ! $this->isPublished($key)));
    }

    /** @return list<string> */
    public function tagNames(): array
    {
        $names = [];
        foreach ($this->comics as $comic) {
            foreach ($comic['tags'] as $values) {
                foreach ($values as $value) {
                    $names[$value] = true;
                }
            }
        }

        return array_keys($names);
    }

    /** @return list<string> */
    public function groups(): array
    {
        return array_values(array_unique(array_column($this->cases, 'group')));
    }

    /** @return list<array<string, mixed>> */
    public function cases(): array
    {
        return $this->cases;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    /** @return list<string> */
    private function validatedGenres(mixed $genres): array
    {
        if (! is_array($genres) || $genres === [] || ! array_is_list($genres)) {
            throw new InvalidArgumentException('corpus.genres must be a non-empty list.');
        }
        $result = [];
        foreach ($genres as $genre) {
            $this->assertNonEmptyString($genre, 'corpus.genres[]');
            if (isset($result[$genre])) {
                throw new InvalidArgumentException("Duplicate corpus genre: {$genre}");
            }
            $result[$genre] = true;
        }

        return array_keys($result);
    }

    /** @return list<array<string, mixed>> */
    private function validatedComics(mixed $comics): array
    {
        if (! is_array($comics) || $comics === [] || ! array_is_list($comics)) {
            throw new InvalidArgumentException('corpus.comics must be a non-empty list.');
        }
        $seen = [];
        $result = [];
        foreach ($comics as $index => $comic) {
            $label = "corpus.comics[{$index}]";
            if (! is_array($comic)) {
                throw new InvalidArgumentException("{$label} must be an object.");
            }
            $key = $comic['key'] ?? null;
            $this->assertNonEmptyString($key, "{$label}.key");
            if (! preg_match('/^[a-z0-9_]+$/', $key)) {
                throw new InvalidArgumentException("{$label}.key must match [a-z0-9_]+, got {$key}");
            }
            if (isset($seen[$key])) {
                throw new InvalidArgumentException("Duplicate corpus comic key: {$key}");
            }
            $seen[$key] = true;

            foreach (['title', 'author', 'description'] as $field) {
                $this->assertNonEmptyString($comic[$field] ?? null, "{$label}.{$field}");
            }
            if (isset($comic['alt_titles']) && ! is_string($comic['alt_titles'])) {
                throw new InvalidArgumentException("{$label}.alt_titles must be a string.");
            }
            $status = $comic['status'] ?? null;
            if (! is_string($status) || ! in_array($status, RecommendationTaxonomyService::STATUSES, true)) {
                throw new InvalidArgumentException("{$label}.status is not a supported status.");
            }
            $genres = $comic['genres'] ?? null;
            if (! is_array($genres) || $genres === [] || ! array_is_list($genres)) {
                throw new InvalidArgumentException("{$label}.genres must be a non-empty list.");
            }
            foreach ($genres as $genre) {
                if (! is_string($genre) || ! in_array($genre, $this->genres, true)) {
                    throw new InvalidArgumentException("{$label}.genres contains an unknown genre.");
                }
            }
            $tags = $comic['tags'] ?? [];
            if (! is_array($tags)) {
                throw new InvalidArgumentException("{$label}.tags must be an object.");
            }
            $normalizedTags = [];
            foreach ($tags as $category => $values) {
                if (! in_array($category, self::TAG_CATEGORIES, true)) {
                    throw new InvalidArgumentException("{$label}.tags has unknown category {$category}.");
                }
                if (! is_array($values) || ! array_is_list($values)) {
                    throw new InvalidArgumentException("{$label}.tags.{$category} must be a list.");
                }
                foreach ($values as $value) {
                    $this->assertNonEmptyString($value, "{$label}.tags.{$category}[]");
                }
                $normalizedTags[$category] = array_values(array_unique($values));
            }
            $offset = $comic['published_offset_days'] ?? null;
            if ($offset !== null && ! is_int($offset)) {
                throw new InvalidArgumentException("{$label}.published_offset_days must be an integer or null.");
            }
            $views = $comic['views'] ?? 0;
            if (! is_int($views) || $views < 0) {
                throw new InvalidArgumentException("{$label}.views must be a non-negative integer.");
            }

            $result[] = [
                'key' => $key,
                'title' => $comic['title'],
                'alt_titles' => $comic['alt_titles'] ?? '',
                'author' => $comic['author'],
                'description' => $comic['description'],
                'status' => $status,
                'genres' => array_values(array_unique($genres)),
                'tags' => $normalizedTags,
                'published_offset_days' => $offset,
                'views' => $views,
            ];
        }

        return $result;
    }

    /** @return list<array<string, mixed>> */
    private function validatedCases(mixed $cases): array
    {
        if (! is_array($cases) || $cases === [] || ! array_is_list($cases)) {
            throw new InvalidArgumentException('cases must be a non-empty list.');
        }
        $seen = [];
        $result = [];
        $tagValues = $this->tagValuesByCategory();
        $exclusionValues = array_values(array_unique([
            ...$this->genres,
            ...$this->tagNames(),
            ...RecommendationTaxonomyService::STATUSES,
        ]));

        foreach ($cases as $index => $case) {
            $label = "cases[{$index}]";
            if (! is_array($case)) {
                throw new InvalidArgumentException("{$label} must be an object.");
            }
            $id = $case['id'] ?? null;
            $this->assertNonEmptyString($id, "{$label}.id");
            if (isset($seen[$id])) {
                throw new InvalidArgumentException("Duplicate case id: {$id}");
            }
            $seen[$id] = true;
            $group = $case['group'] ?? null;
            $this->assertNonEmptyString($group, "{$label}.group");
            $query = $this->resolveQuery($case, $label);

            $expected = $case['expected'] ?? null;
            if (! is_array($expected)) {
                throw new InvalidArgumentException("{$label}.expected must be an object.");
            }
            $intent = $expected['intent'] ?? null;
            if (! is_string($intent) || ! in_array($intent, self::INTENTS, true)) {
                throw new InvalidArgumentException("{$label}.expected.intent must be one of ".implode(', ', self::INTENTS).'.');
            }
            if (! is_bool($expected['needs_more_info'] ?? null)) {
                throw new InvalidArgumentException("{$label}.expected.needs_more_info must be boolean.");
            }
            $slots = [];
            foreach (self::SLOT_FIELDS as $field) {
                $slots[$field] = $this->validatedStringList($expected[$field] ?? [], "{$label}.expected.{$field}");
                foreach ($slots[$field] as $value) {
                    $allowed = match ($field) {
                        'genres' => $this->genres,
                        default => $tagValues[$this->tagFieldToCategory($field)] ?? [],
                    };
                    if (! in_array($value, $allowed, true)) {
                        throw new InvalidArgumentException("{$label}.expected.{$field} contains value outside the corpus taxonomy: {$value}");
                    }
                }
            }
            $status = $expected['status'] ?? null;
            if ($status !== null && (! is_string($status) || ! in_array($status, RecommendationTaxonomyService::STATUSES, true))) {
                throw new InvalidArgumentException("{$label}.expected.status is not a supported status.");
            }
            $exclude = $this->validatedStringList($expected['exclude'] ?? [], "{$label}.expected.exclude");
            foreach ($exclude as $value) {
                if (! in_array($value, $exclusionValues, true)) {
                    throw new InvalidArgumentException("{$label}.expected.exclude contains value outside the corpus taxonomy: {$value}");
                }
            }

            $search = null;
            if (array_key_exists('search', $case)) {
                $search = $this->validatedSearch($case['search'], $label);
            }

            $result[] = [
                'id' => $id,
                'group' => $group,
                'query' => $query,
                'expected' => [
                    'intent' => $intent,
                    'needs_more_info' => $expected['needs_more_info'],
                    ...$slots,
                    'status' => $status,
                    'exclude' => $exclude,
                ],
                'search' => $search,
            ];
        }

        return $result;
    }

    /** @return array<string, mixed>|null */
    private function validatedSearch(mixed $search, string $caseLabel): ?array
    {
        if (! is_array($search)) {
            throw new InvalidArgumentException("{$caseLabel}.search must be an object.");
        }
        $engine = $search['engine'] ?? null;
        if (! is_string($engine) || ! in_array($engine, self::SEARCH_ENGINES, true)) {
            throw new InvalidArgumentException("{$caseLabel}.search.engine must be one of ".implode(', ', self::SEARCH_ENGINES).'.');
        }
        $relevant = $this->validatedStringList($search['relevant'] ?? [], "{$caseLabel}.search.relevant");
        foreach ($relevant as $key) {
            if (! isset($this->comicsByKey[$key])) {
                throw new InvalidArgumentException("{$caseLabel}.search.relevant references unknown comic key: {$key}");
            }
        }
        $expectEmpty = $search['expect_empty'] ?? false;
        if (! is_bool($expectEmpty)) {
            throw new InvalidArgumentException("{$caseLabel}.search.expect_empty must be boolean.");
        }
        if ($expectEmpty && $relevant !== []) {
            throw new InvalidArgumentException("{$caseLabel}.search cannot set expect_empty with relevant comics.");
        }
        if (! $expectEmpty && $relevant === []) {
            throw new InvalidArgumentException("{$caseLabel}.search with no relevant comics must set expect_empty=true.");
        }

        return ['engine' => $engine, 'relevant' => $relevant, 'expect_empty' => $expectEmpty];
    }

    private function resolveQuery(array $case, string $label): string
    {
        $hasQuery = array_key_exists('query', $case);
        $hasFixture = array_key_exists('query_fixture', $case);
        if ($hasQuery === $hasFixture) {
            throw new InvalidArgumentException("{$label} must define exactly one of query or query_fixture.");
        }
        if ($hasQuery) {
            if (! is_string($case['query'])) {
                throw new InvalidArgumentException("{$label}.query must be a string.");
            }

            return $case['query'];
        }
        $fixture = $case['query_fixture'];
        if (! is_array($fixture)) {
            throw new InvalidArgumentException("{$label}.query_fixture must be an object.");
        }
        $repeat = $fixture['repeat'] ?? null;
        $times = $fixture['times'] ?? null;
        $this->assertNonEmptyString($repeat, "{$label}.query_fixture.repeat");
        if (! is_int($times) || $times < 1 || $times > 10000) {
            throw new InvalidArgumentException("{$label}.query_fixture.times must be an integer between 1 and 10000.");
        }

        return str_repeat($repeat, $times);
    }

    /** @return list<string> */
    private function validatedStringList(mixed $values, string $label): array
    {
        if (! is_array($values) || ! array_is_list($values)) {
            throw new InvalidArgumentException("{$label} must be a list.");
        }
        foreach ($values as $value) {
            $this->assertNonEmptyString($value, "{$label}[]");
        }

        return array_values(array_unique($values));
    }

    /** @return array<string, list<string>> */
    private function tagValuesByCategory(): array
    {
        $values = [];
        foreach ($this->comics as $comic) {
            foreach ($comic['tags'] as $category => $names) {
                foreach ($names as $name) {
                    $values[$category][$name] = true;
                }
            }
        }
        foreach ($values as $category => $names) {
            $values[$category] = array_keys($names);
        }

        return $values;
    }

    private function tagFieldToCategory(string $field): string
    {
        return match ($field) {
            'themes' => 'theme',
            'settings' => 'setting',
            'character_traits' => 'character',
            'tones' => 'tone',
            'relationships' => 'relationship',
            default => throw new InvalidArgumentException("Unknown tag field: {$field}"),
        };
    }

    private function assertNonEmptyString(mixed $value, string $label): void
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("{$label} must be a non-empty string.");
        }
    }
}
