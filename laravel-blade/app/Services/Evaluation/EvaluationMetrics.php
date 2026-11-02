<?php

declare(strict_types=1);

namespace App\Services\Evaluation;

/**
 * Small, dependency-free metric helpers used by the NLP evaluation harness.
 *
 * All ratios are returned as floats in [0, 1] or null when the metric is not
 * defined for the supplied labels. Missing ground truth is never treated as a
 * correct prediction: empty expected and empty predicted sets yield null, while
 * one-sided empty sets yield 0.
 */
final class EvaluationMetrics
{
    /**
     * Set-based precision/recall/F1 for one labelled dimension.
     *
     * @param  list<string>  $predicted
     * @param  list<string>  $expected
     * @return array{tp: int, fp: int, fn: int, precision: ?float, recall: ?float, f1: ?float}
     */
    public static function setPrf(array $predicted, array $expected): array
    {
        $predicted = array_values(array_unique($predicted));
        $expected = array_values(array_unique($expected));
        $tp = count(array_intersect($predicted, $expected));

        return self::prf($tp, count($predicted) - $tp, count($expected) - $tp);
    }

    /**
     * @return array{tp: int, fp: int, fn: int, precision: ?float, recall: ?float, f1: ?float}
     */
    public static function prf(int $tp, int $fp, int $fn): array
    {
        $precision = ($tp + $fp) > 0
            ? (float) ($tp / ($tp + $fp))
            : (($tp + $fn) > 0 ? 0.0 : null);
        $recall = ($tp + $fn) > 0
            ? (float) ($tp / ($tp + $fn))
            : (($tp + $fp) > 0 ? 0.0 : null);

        return [
            'tp' => $tp,
            'fp' => $fp,
            'fn' => $fn,
            'precision' => $precision,
            'recall' => $recall,
            'f1' => self::harmonic($precision, $recall),
        ];
    }

    /**
     * P@K = |returned ∩ relevant| / min(K, |returned|).
     *
     * Returns null when there is no relevance ground truth. When ground truth
     * exists but the system returned nothing, P@K is defined as 0.
     *
     * @param  list<string>  $returned
     * @param  list<string>  $relevant
     */
    public static function precisionAtK(array $returned, array $relevant, int $k): ?float
    {
        $relevant = array_values(array_unique($relevant));
        if ($relevant === [] || $k <= 0) {
            return null;
        }
        $returned = array_slice(array_values(array_unique($returned)), 0, $k);
        if ($returned === []) {
            return 0.0;
        }

        return (float) (count(array_intersect($returned, $relevant)) / min($k, count($returned)));
    }

    /**
     * R@K = |returned ∩ relevant| / |relevant|, excluding unlabelled cases.
     *
     * @param  list<string>  $returned
     * @param  list<string>  $relevant
     */
    public static function recallAtK(array $returned, array $relevant, int $k): ?float
    {
        $relevant = array_values(array_unique($relevant));
        if ($relevant === [] || $k <= 0) {
            return null;
        }
        $returned = array_slice(array_values(array_unique($returned)), 0, $k);

        return (float) (count(array_intersect($returned, $relevant)) / count($relevant));
    }

    /**
     * @param  list<?float>  $values
     */
    public static function mean(array $values): ?float
    {
        $values = array_values(array_filter($values, static fn ($value): bool => $value !== null));
        if ($values === []) {
            return null;
        }

        return (float) (array_sum($values) / count($values));
    }

    /**
     * Case-level exact accuracy for set-valued labels (order-insensitive).
     *
     * @param  list<array{0: list<string>, 1: list<string>}>  $pairs
     */
    public static function exactSetAccuracy(array $pairs): ?float
    {
        if ($pairs === []) {
            return null;
        }
        $correct = 0;
        foreach ($pairs as [$predicted, $expected]) {
            $predicted = array_values(array_unique($predicted));
            $expected = array_values(array_unique($expected));
            sort($predicted);
            sort($expected);
            if ($predicted === $expected) {
                $correct++;
            }
        }

        return (float) ($correct / count($pairs));
    }

    /**
     * @param  list<bool>  $values
     */
    public static function rate(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        return (float) (count(array_filter($values)) / count($values));
    }

    public static function harmonic(?float $precision, ?float $recall): ?float
    {
        if ($precision === null || $recall === null) {
            return null;
        }
        if ($precision + $recall === 0.0) {
            return 0.0;
        }

        return (2 * $precision * $recall) / ($precision + $recall);
    }

    public static function delta(?float $current, ?float $baseline): ?float
    {
        if ($current === null || $baseline === null) {
            return null;
        }

        return $current - $baseline;
    }
}
