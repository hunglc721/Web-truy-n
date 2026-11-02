<?php

declare(strict_types=1);

namespace App\Services\Evaluation;

/**
 * Flattens evaluator output and builds before/after deltas without hiding
 * undefined metrics: a metric that is null on either side gets a null delta and
 * can never be reported as an improvement or regression.
 */
final class NlpEvaluationReport
{
    private const COUNT_METRICS = [
        'cases', 'status_false_positives', 'retrieval_oracle_cases', 'retrieval_pipeline_cases',
        'retrieval_leakage_cases', 'retrieval_exclusion_violations',
    ];

    private const HIGHER_IS_BETTER = [
        'intent_accuracy', 'needs_more_info_accuracy', 'invalid_accuracy',
        'slots_precision', 'slots_recall', 'slots_f1', 'slots_exact_match_accuracy',
        'status_accuracy', 'status_labeled_accuracy',
        'exclusion_accuracy', 'exclusion_precision', 'exclusion_recall', 'exclusion_f1',
        'retrieval_oracle_precision_at_k', 'retrieval_oracle_recall_at_k',
        'retrieval_pipeline_precision_at_k', 'retrieval_pipeline_recall_at_k',
        'retrieval_pipeline_micro_precision', 'retrieval_pipeline_micro_recall',
        'retrieval_pipeline_empty_accuracy',
    ];

    private const LOWER_IS_BETTER = [
        'error_rate', 'fallback_rate', 'retrieval_leakage_cases', 'retrieval_exclusion_violations',
    ];

    /** @return array<string, int|float|null> */
    public static function summary(array $evaluation): array
    {
        return self::fromAggregate($evaluation['overall'] ?? []);
    }

    /** @return array<string, int|float|null> */
    private static function fromAggregate(array $overall): array
    {
        $oracle = $overall['retrieval']['oracle'] ?? [];
        $pipeline = $overall['retrieval']['pipeline'] ?? [];
        $slots = $overall['slots'] ?? [];
        $exclusions = $overall['exclusions'] ?? [];

        return [
            'cases' => $overall['cases'] ?? null,
            'intent_accuracy' => $overall['intent_accuracy'] ?? null,
            'needs_more_info_accuracy' => $overall['needs_more_info_accuracy'] ?? null,
            'clarification_rate' => $overall['clarification_rate'] ?? null,
            'invalid_accuracy' => $overall['invalid']['accuracy'] ?? null,
            'slots_precision' => $slots['micro']['precision'] ?? null,
            'slots_recall' => $slots['micro']['recall'] ?? null,
            'slots_f1' => $slots['micro']['f1'] ?? null,
            'slots_exact_match_accuracy' => $slots['exact_match_accuracy'] ?? null,
            'status_accuracy' => $overall['status']['accuracy'] ?? null,
            'status_labeled_accuracy' => $overall['status']['labeled_accuracy'] ?? null,
            'status_false_positives' => $overall['status']['false_positives'] ?? null,
            'exclusion_accuracy' => $exclusions['accuracy'] ?? null,
            'exclusion_precision' => $exclusions['micro']['precision'] ?? null,
            'exclusion_recall' => $exclusions['micro']['recall'] ?? null,
            'exclusion_f1' => $exclusions['micro']['f1'] ?? null,
            'fallback_rate' => $overall['fallback_rate'] ?? null,
            'error_rate' => $overall['error_rate'] ?? null,
            'retrieval_oracle_cases' => $oracle['cases'] ?? null,
            'retrieval_oracle_precision_at_k' => $oracle['precision_at_k'] ?? null,
            'retrieval_oracle_recall_at_k' => $oracle['recall_at_k'] ?? null,
            'retrieval_pipeline_cases' => $pipeline['cases'] ?? null,
            'retrieval_pipeline_precision_at_k' => $pipeline['precision_at_k'] ?? null,
            'retrieval_pipeline_recall_at_k' => $pipeline['recall_at_k'] ?? null,
            'retrieval_pipeline_micro_precision' => $pipeline['micro_precision'] ?? null,
            'retrieval_pipeline_micro_recall' => $pipeline['micro_recall'] ?? null,
            'retrieval_pipeline_empty_accuracy' => $pipeline['empty_accuracy'] ?? null,
            'retrieval_leakage_cases' => $pipeline['leakage_case_count'] ?? null,
            'retrieval_exclusion_violations' => $pipeline['exclusion_violations'] ?? null,
        ];
    }

    /** @return array{overall: array<string, array{current: mixed, baseline: mixed, delta: ?float}>, groups: array<string, array<string, array{current: mixed, baseline: mixed, delta: ?float}>>} */
    public static function compare(array $current, ?array $baseline): array
    {
        $currentSummary = self::summary($current);
        $baselineSummary = $baseline === null ? [] : self::summary($baseline);
        $overall = [];
        foreach ($currentSummary as $metric => $value) {
            $baselineValue = $baselineSummary[$metric] ?? null;
            $overall[$metric] = [
                'current' => $value,
                'baseline' => $baselineValue,
                'unit' => in_array($metric, self::COUNT_METRICS, true) ? 'count' : 'ratio',
                'delta' => is_numeric($value) && is_numeric($baselineValue)
                    ? (in_array($metric, self::COUNT_METRICS, true)
                        ? (int) ((float) $value - (float) $baselineValue)
                        : EvaluationMetrics::delta((float) $value, (float) $baselineValue))
                    : null,
            ];
        }

        $groupNames = array_values(array_unique([
            ...array_keys($current['groups'] ?? []),
            ...array_keys($baseline['groups'] ?? []),
        ]));
        $groups = [];
        foreach ($groupNames as $group) {
            $currentGroup = self::fromAggregate($current['groups'][$group] ?? []);
            $baselineGroup = self::fromAggregate($baseline['groups'][$group] ?? []);
            foreach ($currentGroup as $metric => $value) {
                $baselineValue = $baselineGroup[$metric] ?? null;
                $groups[$group][$metric] = [
                    'current' => $value,
                    'baseline' => $baselineValue,
                    'unit' => in_array($metric, self::COUNT_METRICS, true) ? 'count' : 'ratio',
                    'delta' => is_numeric($value) && is_numeric($baselineValue)
                        ? (in_array($metric, self::COUNT_METRICS, true)
                            ? (int) ((float) $value - (float) $baselineValue)
                            : EvaluationMetrics::delta((float) $value, (float) $baselineValue))
                        : null,
                ];
            }
        }

        return ['overall' => $overall, 'groups' => $groups];
    }

    /**
     * @return array{regressions: list<array<string, mixed>>, improvements: list<array<string, mixed>>}
     */
    public static function changes(array $comparison): array
    {
        $regressions = [];
        $improvements = [];
        foreach ($comparison['groups'] ?? [] as $group => $metrics) {
            foreach ($metrics as $metric => $values) {
                if (! in_array($metric, self::HIGHER_IS_BETTER, true) && ! in_array($metric, self::LOWER_IS_BETTER, true)) {
                    continue;
                }
                $delta = $values['delta'] ?? null;
                if ($delta === null || abs((float) $delta) < 0.0000001) {
                    continue;
                }
                $row = [
                    'scope' => 'group:'.$group,
                    'group' => $group,
                    'metric' => $metric,
                    'unit' => $values['unit'] ?? 'ratio',
                    'current' => $values['current'],
                    'baseline' => $values['baseline'],
                    'delta' => $delta,
                    'magnitude' => abs((float) $delta),
                ];
                $regression = in_array($metric, self::LOWER_IS_BETTER, true)
                    ? (float) $delta > 0
                    : (float) $delta < 0;
                if ($regression) {
                    $regressions[] = $row;
                } else {
                    $improvements[] = $row;
                }
            }
        }
        usort($regressions, static fn (array $a, array $b): int => $b['magnitude'] <=> $a['magnitude']);
        usort($improvements, static fn (array $a, array $b): int => $b['magnitude'] <=> $a['magnitude']);

        return ['regressions' => $regressions, 'improvements' => $improvements];
    }
}
