<?php

declare(strict_types=1);

namespace App\Services\Evaluation;

use App\Data\RecommendationPreference;
use App\Helpers\VietnameseHelper;
use App\Services\AI\PreferenceParser;
use App\Services\PreferenceRecommendationService;
use App\Services\SearchService;
use Throwable;

/**
 * Runs the labelled dataset through the existing production parser and
 * retrieval services. It does not contain a second NLP implementation: intent
 * and slot scores are derived from RuleBasedPreferenceParser output, and
 * retrieval comes from SearchService/PreferenceRecommendationService.
 */
final class NlpEvaluator
{
    public function __construct(
        private SearchService $search,
        private PreferenceRecommendationService $recommendations,
        private PreferenceParser $parser,
    ) {}

    /**
     * @param  callable(string): array|null  $parserOverride  Baseline parser callable; null uses production PreferenceParser.
     * @param  list<string>  $groups
     */
    public function evaluate(
        NlpEvaluationDataset $dataset,
        NlpEvaluationCorpus $corpus,
        string $label,
        ?callable $parserOverride = null,
        ?int $k = null,
        array $groups = [],
    ): array {
        $k ??= $dataset->k();
        $cases = array_values(array_filter(
            $dataset->cases(),
            fn (array $case): bool => $groups === [] || in_array($case['group'], $groups, true),
        ));

        $rows = [];
        foreach ($cases as $case) {
            $rows[] = $this->evaluateCase($dataset, $corpus, $case, $parserOverride, $k);
        }

        $grouped = [];
        foreach (array_values(array_unique(array_column($cases, 'group'))) as $group) {
            $groupRows = array_values(array_filter($rows, fn (array $row): bool => $row['group'] === $group));
            $grouped[$group] = $this->aggregate($groupRows);
        }

        return [
            'label' => $label,
            'k' => $k,
            'cases_evaluated' => count($rows),
            'parser_mode' => $parserOverride === null ? 'current' : 'baseline',
            'overall' => $this->aggregate($rows),
            'groups' => $grouped,
            'cases' => $rows,
        ];
    }

    /** @param array<string, mixed> $case */
    private function evaluateCase(
        NlpEvaluationDataset $dataset,
        NlpEvaluationCorpus $corpus,
        array $case,
        ?callable $parserOverride,
        int $k,
    ): array {
        $expected = $case['expected'];
        $parsed = $this->parse($case['query'], $parserOverride);
        $predicted = $parsed['preferences'];
        $scored = $expected['intent'] !== 'invalid' && ! $parsed['invalid'];
        $predictedIntent = $this->intent($dataset, $case['query'], $parsed);

        $predictedSlots = $this->slots($predicted);
        $predictedStatus = $this->status($predicted);
        $predictedExclude = $this->list($predicted['exclude'] ?? []);
        $predictedNeedsMoreInfo = is_array($predicted) && array_key_exists('needs_more_info', $predicted)
            ? (bool) $predicted['needs_more_info'] : null;

        $fields = [];
        $allFieldsExact = true;
        foreach (NlpEvaluationDataset::SLOT_FIELDS as $field) {
            $fields[$field] = EvaluationMetrics::setPrf($predictedSlots[$field], $expected[$field]);
            $allFieldsExact = $allFieldsExact && $this->sameSet($predictedSlots[$field], $expected[$field]);
        }

        $statusOk = $predictedStatus === $expected['status'];
        $excludePrf = EvaluationMetrics::setPrf($predictedExclude, $expected['exclude']);
        $needsOk = $predictedNeedsMoreInfo === null ? null : $predictedNeedsMoreInfo === $expected['needs_more_info'];

        return [
            'id' => $case['id'],
            'group' => $case['group'],
            'query' => $case['query'],
            'scored' => $scored,
            'expected_intent' => $expected['intent'],
            'predicted_intent' => $predictedIntent,
            'intent_ok' => $predictedIntent === $expected['intent'],
            'needs_more_info' => [
                'expected' => $expected['needs_more_info'],
                'predicted' => $predictedNeedsMoreInfo,
                'ok' => $needsOk,
            ],
            'slots' => [
                'expected' => array_intersect_key($expected, array_flip(NlpEvaluationDataset::SLOT_FIELDS)),
                'predicted' => $predictedSlots,
                'fields' => $fields,
                'exact' => $allFieldsExact,
            ],
            'status' => [
                'expected' => $expected['status'],
                'predicted' => $predictedStatus,
                'ok' => $statusOk,
                'false_positive' => $predictedStatus !== null && ! $statusOk,
            ],
            'exclusions' => [
                'expected' => $expected['exclude'],
                'predicted' => $predictedExclude,
                'ok' => $this->sameSet($predictedExclude, $expected['exclude']),
                'prf' => $excludePrf,
            ],
            'parser' => [
                'source' => $parsed['source'],
                'fallback_reason' => $parsed['fallback_reason'],
                'cached' => $parsed['cached'],
            ],
            'search' => array_key_exists('search', $case) && $case['search'] !== null
                ? $this->evaluateSearch($case, $corpus, $predicted, $k)
                : null,
            'error' => $parsed['error'],
        ];
    }

    /**
     * @return array{invalid: bool, preferences: ?array, source: ?string, fallback_reason: ?string, cached: bool, error: ?string}
     */
    private function parse(string $query, ?callable $parserOverride): array
    {
        $empty = [
            'invalid' => false,
            'preferences' => null,
            'source' => null,
            'fallback_reason' => null,
            'cached' => false,
            'error' => null,
        ];

        if (trim($query) === '' || mb_strlen($query) > 4000) {
            if ($parserOverride === null) {
                // Exercise the real production guard so invalid cases never reach a provider.
                $result = $this->parser->parseNaturalLanguage($query, 'session:nlp-eval');

                return [
                    ...$empty,
                    'invalid' => ($result['error'] ?? null) === 'invalid_input',
                    'source' => $result['source'] ?? null,
                    'error' => ($result['error'] ?? null) === 'invalid_input'
                        ? null : (string) ($result['error'] ?? 'invalid_input_not_detected'),
                ];
            }

            return [...$empty, 'invalid' => true];
        }

        try {
            if ($parserOverride !== null) {
                $preferences = $parserOverride($query);
                $source = 'baseline';
                $fallbackReason = null;
                $cached = false;
            } else {
                $result = $this->parser->parseNaturalLanguage($query, 'session:nlp-eval');
                if (! ($result['success'] ?? false) || ! is_array($result['preferences'] ?? null)) {
                    return [...$empty, 'source' => $result['source'] ?? null,
                        'fallback_reason' => $result['fallback_reason'] ?? null,
                        'error' => (string) ($result['error'] ?? 'parse_failed')];
                }
                $preferences = $result['preferences'];
                $source = isset($result['source']) ? (string) $result['source'] : null;
                $fallbackReason = isset($result['fallback_reason']) ? (string) $result['fallback_reason'] : null;
                $cached = (bool) ($result['cached'] ?? false);
            }
            if (! is_array($preferences)) {
                return [...$empty, 'source' => $source, 'error' => 'parser_returned_non_array'];
            }

            return [
                'invalid' => false,
                'preferences' => $this->normalizePreferences($preferences),
                'source' => $source,
                'fallback_reason' => $fallbackReason,
                'cached' => $cached,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            return [...$empty, 'error' => $exception::class.': '.$exception->getMessage()];
        }
    }

    /** @param array<string, mixed> $preferences */
    private function normalizePreferences(array $preferences): array
    {
        $normalized = [];
        foreach (NlpEvaluationDataset::SLOT_FIELDS as $field) {
            $normalized[$field] = $this->list($preferences[$field] ?? []);
        }
        $normalized['status'] = $this->status($preferences);
        $normalized['exclude'] = $this->list($preferences['exclude'] ?? []);
        $normalized['needs_more_info'] = (bool) ($preferences['needs_more_info'] ?? false);
        $normalized['follow_up_question'] = $preferences['follow_up_question'] ?? null;

        return $normalized;
    }

    /** @param array<string, mixed> $preferences */
    private function slots(?array $preferences): array
    {
        $slots = [];
        foreach (NlpEvaluationDataset::SLOT_FIELDS as $field) {
            $slots[$field] = $this->list($preferences[$field] ?? []);
        }

        return $slots;
    }

    /** @param array<string, mixed> $preferences */
    private function status(?array $preferences): ?string
    {
        $status = $preferences['status'] ?? null;

        return is_string($status) && trim($status) !== '' ? trim($status) : null;
    }

    /** @return list<string> */
    private function list(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }
        $result = [];
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                $result[] = trim($value);
            }
        }

        return array_values(array_unique($result));
    }

    /** @param list<string> $left @param list<string> $right */
    private function sameSet(array $left, array $right): bool
    {
        $left = array_values(array_unique($left));
        $right = array_values(array_unique($right));
        sort($left);
        sort($right);

        return $left === $right;
    }

    private function intent(
        NlpEvaluationDataset $dataset,
        string $query,
        array $parsed,
    ): string {
        if ($parsed['invalid']) {
            return 'invalid';
        }
        if ($parsed['error'] !== null) {
            return 'error';
        }
        if (($parsed['preferences']['needs_more_info'] ?? false) === false) {
            return 'preferences';
        }

        return $this->looksLikeLookup($dataset, $query) ? 'lookup' : 'clarify';
    }

    /**
     * Lookup is not a parser output, so the harness derives it from the same
     * lexicon the catalog exposes: normalized title/alt-title/author matches,
     * plus at least a two-token description match. This is a metric-layer
     * observable, not a second preference parser.
     */
    private function looksLikeLookup(NlpEvaluationDataset $dataset, string $query): bool
    {
        $needle = $this->normalizeText($query);
        if ($needle === '') {
            return false;
        }
        $tokens = count(preg_split('/\s+/u', $needle) ?: []);

        foreach ($dataset->comics() as $comic) {
            foreach ([$comic['title'], $comic['alt_titles'], $comic['author']] as $field) {
                if ($field !== '' && str_contains($this->normalizeText($field), $needle)) {
                    return true;
                }
            }
            if ($tokens >= 2 && str_contains($this->normalizeText($comic['description']), $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(string $text): string
    {
        $text = VietnameseHelper::removeAccents($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /** @param array<string, mixed> $case */
    private function evaluateSearch(
        array $case,
        NlpEvaluationCorpus $corpus,
        ?array $predicted,
        int $k,
    ): array {
        $search = $case['search'];
        $engine = $search['engine'];
        $expected = $case['expected'];
        $oraclePreference = [
            ...array_intersect_key($expected, array_flip(NlpEvaluationDataset::SLOT_FIELDS)),
            'status' => $expected['status'],
            'exclude' => $expected['exclude'],
            'needs_more_info' => false,
        ];

        try {
            $pipeline = $this->scoreSearch(
                $engine,
                $this->retrieve($engine, $case['query'], $predicted ?? [], $k, $corpus),
                $search['relevant'],
                $expected['exclude'],
                $corpus,
                $k,
            );
        } catch (Throwable $exception) {
            $pipeline = $this->failedSearch($engine, $search['relevant'], $k, $exception);
        }

        $oracle = null;
        if ($engine === 'recommendation') {
            try {
                $oracle = $this->scoreSearch(
                    $engine,
                    $this->retrieve($engine, $case['query'], $oraclePreference, $k, $corpus),
                    $search['relevant'],
                    $expected['exclude'],
                    $corpus,
                    $k,
                );
            } catch (Throwable $exception) {
                $oracle = $this->failedSearch($engine, $search['relevant'], $k, $exception);
            }
        }

        return [
            'engine' => $engine,
            'expected_empty' => (bool) $search['expect_empty'],
            'oracle' => $oracle,
            'pipeline' => $pipeline,
        ];
    }

    /** @return list<string> */
    private function retrieve(
        string $engine,
        string $query,
        array $preferences,
        int $k,
        NlpEvaluationCorpus $corpus,
    ): array {
        $ids = match ($engine) {
            'live' => $this->search->liveSearch($query, $k)->pluck('id')->all(),
            'advanced' => array_map(
                fn ($comic): int => (int) $comic->id,
                $this->search->advancedSearch(['q' => $query, 'per_page' => $k, 'sort' => 'views'])->items(),
            ),
            'recommendation' => $this->recommendations
                ->recommend(RecommendationPreference::fromArray([...$preferences, 'needs_more_info' => false]), $k)
                ->map(fn (array $row): int => (int) $row['comic']->id)
                ->all(),
            default => throw new \InvalidArgumentException("Unknown search engine: {$engine}"),
        };

        $keys = [];
        foreach ($ids as $id) {
            $keys[] = $corpus->keyFor((int) $id) ?? 'db:'.$id;
        }

        return array_slice(array_values(array_unique($keys)), 0, $k);
    }

    /**
     * @param  list<string>  $returnedKeys
     * @param  list<string>  $relevant
     * @param  list<string>  $expectedExclude
     */
    private function scoreSearch(
        string $engine,
        array $returnedKeys,
        array $relevant,
        array $expectedExclude,
        NlpEvaluationCorpus $corpus,
        int $k,
    ): array {
        $returned = array_slice(array_values(array_unique($returnedKeys)), 0, $k);
        $relevant = array_values(array_unique($relevant));
        $emptyExpected = $relevant === [];
        $meta = $corpus->meta();
        $leaks = [];
        $violations = [];
        $unknown = [];

        foreach ($returned as $key) {
            if (! isset($meta[$key])) {
                $unknown[] = $key;

                continue;
            }
            if (! $meta[$key]['published']) {
                $leaks[] = $key;
            }
            $values = [...$meta[$key]['genres'], ...$meta[$key]['tags'], $meta[$key]['status']];
            if (array_values(array_intersect($expectedExclude, $values)) !== []) {
                $violations[] = $key;
            }
        }

        return [
            'engine' => $engine,
            'returned' => $returned,
            'relevant' => $relevant,
            'hits' => count(array_intersect($returned, $relevant)),
            'precision_at_k' => EvaluationMetrics::precisionAtK($returned, $relevant, $k),
            'recall_at_k' => EvaluationMetrics::recallAtK($returned, $relevant, $k),
            'empty_expected' => $emptyExpected,
            'empty_ok' => $emptyExpected ? $returned === [] : null,
            'leaked' => array_values(array_unique($leaks)),
            'exclusion_violations' => array_values(array_unique($violations)),
            'out_of_corpus' => array_values(array_unique($unknown)),
            'error' => null,
        ];
    }

    /**
     * @param  list<string>  $relevant
     */
    private function failedSearch(string $engine, array $relevant, int $k, Throwable $exception): array
    {
        return [
            'engine' => $engine,
            'returned' => [],
            'relevant' => array_values(array_unique($relevant)),
            'hits' => 0,
            'precision_at_k' => $relevant === [] ? null : 0.0,
            'recall_at_k' => $relevant === [] ? null : 0.0,
            'empty_expected' => $relevant === [],
            'empty_ok' => $relevant === [] ? 0.0 : null,
            'leaked' => [],
            'exclusion_violations' => [],
            'out_of_corpus' => [],
            'error' => $exception::class.': '.$exception->getMessage(),
        ];
    }

    /** @param list<array<string, mixed>> $rows */
    private function aggregate(array $rows): array
    {
        $scored = array_values(array_filter($rows, fn (array $row): bool => $row['scored']));
        $intent = EvaluationMetrics::rate(array_map(fn (array $row): bool => $row['intent_ok'], $rows));
        $needs = EvaluationMetrics::rate(array_values(array_filter(
            array_map(fn (array $row): ?bool => $row['needs_more_info']['ok'], $scored),
            static fn (?bool $value): bool => $value !== null,
        )));

        $fieldTotals = array_fill_keys(NlpEvaluationDataset::SLOT_FIELDS, ['tp' => 0, 'fp' => 0, 'fn' => 0]);
        $statusCorrect = [];
        $statusLabeled = [];
        $statusFalsePositives = 0;
        $excludePairs = [];
        $excludeTotals = ['tp' => 0, 'fp' => 0, 'fn' => 0];
        $slotsExact = [];
        $clarification = [];
        $fallback = [];
        $fallbackReasons = [];
        $errors = [];

        foreach ($scored as $row) {
            $slotsExact[] = $row['slots']['exact'];
            foreach (NlpEvaluationDataset::SLOT_FIELDS as $field) {
                $fieldTotals[$field]['tp'] += $row['slots']['fields'][$field]['tp'];
                $fieldTotals[$field]['fp'] += $row['slots']['fields'][$field]['fp'];
                $fieldTotals[$field]['fn'] += $row['slots']['fields'][$field]['fn'];
            }
            $statusCorrect[] = $row['status']['ok'];
            if ($row['status']['expected'] !== null) {
                $statusLabeled[] = $row['status']['ok'];
            }
            if ($row['status']['false_positive']) {
                $statusFalsePositives++;
            }
            $excludePairs[] = [$row['exclusions']['predicted'], $row['exclusions']['expected']];
            $excludeTotals['tp'] += $row['exclusions']['prf']['tp'];
            $excludeTotals['fp'] += $row['exclusions']['prf']['fp'];
            $excludeTotals['fn'] += $row['exclusions']['prf']['fn'];
            $clarification[] = $row['needs_more_info']['predicted'] === true;
            if (in_array($row['parser']['source'], ['fallback', 'ai'], true)) {
                $fallback[] = $row['parser']['source'] === 'fallback';
            }
            if ($row['parser']['fallback_reason'] !== null) {
                $reason = $row['parser']['fallback_reason'];
                $fallbackReasons[$reason] = ($fallbackReasons[$reason] ?? 0) + 1;
            }
            if ($row['error'] !== null) {
                $errors[$row['id']] = $row['error'];
            }
        }

        $overallTotals = ['tp' => 0, 'fp' => 0, 'fn' => 0];
        $fieldMicro = [];
        foreach ($fieldTotals as $field => $totals) {
            $fieldMicro[$field] = EvaluationMetrics::prf($totals['tp'], $totals['fp'], $totals['fn']);
            $overallTotals['tp'] += $totals['tp'];
            $overallTotals['fp'] += $totals['fp'];
            $overallTotals['fn'] += $totals['fn'];
        }

        $invalidCases = array_values(array_filter($rows, fn (array $row): bool => $row['expected_intent'] === 'invalid'));
        $invalidDetected = count(array_filter($invalidCases, fn (array $row): bool => $row['predicted_intent'] === 'invalid'));
        $falseInvalid = count(array_filter($rows, fn (array $row): bool => $row['expected_intent'] !== 'invalid' && $row['predicted_intent'] === 'invalid'));

        return [
            'cases' => count($rows),
            'scored_cases' => count($scored),
            'intent_accuracy' => $intent,
            'needs_more_info_accuracy' => $needs,
            'clarification_rate' => EvaluationMetrics::rate($clarification),
            'invalid' => [
                'cases' => count($invalidCases),
                'detected' => $invalidDetected,
                'accuracy' => $invalidCases === [] ? null : (float) ($invalidDetected / count($invalidCases)),
                'false_invalid' => $falseInvalid,
            ],
            'slots' => [
                'micro' => EvaluationMetrics::prf($overallTotals['tp'], $overallTotals['fp'], $overallTotals['fn']),
                'exact_match_accuracy' => EvaluationMetrics::rate($slotsExact),
                'fields' => $fieldMicro,
            ],
            'status' => [
                'accuracy' => EvaluationMetrics::rate($statusCorrect),
                'labeled_accuracy' => EvaluationMetrics::rate($statusLabeled),
                'labeled_cases' => count($statusLabeled),
                'false_positives' => $statusFalsePositives,
            ],
            'exclusions' => [
                'accuracy' => EvaluationMetrics::exactSetAccuracy($excludePairs),
                'micro' => EvaluationMetrics::prf($excludeTotals['tp'], $excludeTotals['fp'], $excludeTotals['fn']),
            ],
            'fallback_rate' => $fallback === [] ? null : EvaluationMetrics::rate($fallback),
            'fallback_reasons' => $fallbackReasons,
            'error_rate' => count($rows) === 0 ? null : (float) (count($errors) / count($rows)),
            'errors' => $errors,
            'retrieval' => [
                'oracle' => $this->aggregateRetrieval($rows, 'oracle'),
                'pipeline' => $this->aggregateRetrieval($rows, 'pipeline'),
            ],
        ];
    }

    /** @param list<array<string, mixed>> $rows */
    private function aggregateRetrieval(array $rows, string $mode): array
    {
        $scores = [];
        $empty = [];
        $leakCases = [];
        $violationCases = [];
        $failures = [];
        $hits = 0;
        $returnedTotal = 0;
        $relevantTotal = 0;
        $leakedResults = 0;
        $violations = 0;
        $unknown = 0;

        foreach ($rows as $row) {
            $result = $row['search'][$mode] ?? null;
            if (! is_array($result)) {
                continue;
            }
            $scores[] = $result;
            if ($result['error'] !== null) {
                $failures[] = $row['id'];
            }
            if ($result['empty_expected']) {
                $empty[] = (bool) $result['empty_ok'];
                if (! $result['empty_ok']) {
                    $failures[] = $row['id'];
                }
            } else {
                $hits += $result['hits'];
                $returnedTotal += count($result['returned']);
                $relevantTotal += count($result['relevant']);
                if ($result['error'] === null
                    && (($result['precision_at_k'] ?? 0.0) < 1.0 || ($result['recall_at_k'] ?? 0.0) < 1.0)) {
                    $failures[] = $row['id'];
                }
            }
            if ($result['leaked'] !== []) {
                $leakCases[$row['id']] = $result['leaked'];
                $leakedResults += count($result['leaked']);
            }
            if ($result['exclusion_violations'] !== []) {
                $violationCases[$row['id']] = $result['exclusion_violations'];
                $violations += count($result['exclusion_violations']);
            }
            $unknown += count($result['out_of_corpus']);
        }

        return [
            'cases' => count($scores),
            'labeled_cases' => count($scores) - count($empty),
            'precision_at_k' => EvaluationMetrics::mean(array_map(
                static fn (array $result): ?float => $result['precision_at_k'], $scores,
            )),
            'recall_at_k' => EvaluationMetrics::mean(array_map(
                static fn (array $result): ?float => $result['recall_at_k'], $scores,
            )),
            'micro_precision' => $returnedTotal > 0
                ? (float) ($hits / $returnedTotal)
                : ($relevantTotal > 0 ? 0.0 : null),
            'micro_recall' => $relevantTotal > 0 ? (float) ($hits / $relevantTotal) : null,
            'empty_expected_cases' => count($empty),
            'empty_accuracy' => EvaluationMetrics::rate($empty),
            'leakage_cases' => $leakCases,
            'leakage_case_count' => count($leakCases),
            'leaked_results' => $leakedResults,
            'exclusion_violation_cases' => $violationCases,
            'exclusion_violations' => $violations,
            'out_of_corpus_results' => $unknown,
            'failures' => array_values(array_unique($failures)),
        ];
    }
}
