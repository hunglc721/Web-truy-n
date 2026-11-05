<?php

namespace Tests\Unit;

use App\Services\Evaluation\EvaluationMetrics;
use PHPUnit\Framework\TestCase;

class EvaluationMetricsTest extends TestCase
{
    public function test_set_prf_counts_true_positive_false_positive_and_false_negative(): void
    {
        $result = EvaluationMetrics::setPrf(['Fantasy', 'Action'], ['Action', 'Comedy']);

        $this->assertSame(1, $result['tp']);
        $this->assertSame(1, $result['fp']);
        $this->assertSame(1, $result['fn']);
        $this->assertSame(0.5, $result['precision']);
        $this->assertSame(0.5, $result['recall']);
        $this->assertSame(0.5, $result['f1']);
    }

    public function test_set_prf_deduplicates_values_and_ignores_order(): void
    {
        $result = EvaluationMetrics::setPrf(['Action', 'Action'], ['Action']);

        $this->assertSame(1, $result['tp']);
        $this->assertSame(0, $result['fp']);
        $this->assertSame(1.0, $result['precision']);
        $this->assertSame(1.0, $result['recall']);
        $this->assertSame(1.0, $result['f1']);
    }

    public function test_missing_ground_truth_is_not_scored_as_correct(): void
    {
        $bothEmpty = EvaluationMetrics::setPrf([], []);

        $this->assertNull($bothEmpty['precision']);
        $this->assertNull($bothEmpty['recall']);
        $this->assertNull($bothEmpty['f1']);
    }

    public function test_one_sided_empty_sets_score_zero_instead_of_null(): void
    {
        $falseNegative = EvaluationMetrics::setPrf([], ['Action']);
        $falsePositive = EvaluationMetrics::setPrf(['Action'], []);

        $this->assertSame([0.0, 0.0, 0.0], [
            $falseNegative['precision'], $falseNegative['recall'], $falseNegative['f1'],
        ]);
        $this->assertSame([0.0, 0.0, 0.0], [
            $falsePositive['precision'], $falsePositive['recall'], $falsePositive['f1'],
        ]);
    }

    public function test_precision_at_k_uses_returned_count_and_recall_at_k_uses_relevant_count(): void
    {
        $returned = ['a', 'b', 'c'];
        $relevant = ['b'];

        $this->assertSame(1 / 3, EvaluationMetrics::precisionAtK($returned, $relevant, 3));
        $this->assertSame(1.0, EvaluationMetrics::recallAtK($returned, $relevant, 3));
    }

    public function test_precision_at_k_with_empty_results_is_zero_when_relevance_exists(): void
    {
        $this->assertSame(0.0, EvaluationMetrics::precisionAtK([], ['a'], 5));
        $this->assertSame(0.0, EvaluationMetrics::recallAtK([], ['a'], 5));
    }

    public function test_precision_and_recall_at_k_are_null_without_relevance_labels(): void
    {
        $this->assertNull(EvaluationMetrics::precisionAtK(['a'], [], 5));
        $this->assertNull(EvaluationMetrics::recallAtK(['a'], [], 5));
        $this->assertNull(EvaluationMetrics::precisionAtK(['a'], ['a'], 0));
    }

    public function test_precision_at_k_slices_to_the_cutoff(): void
    {
        $returned = ['x', 'x2', 'hit', 'x3'];
        $relevant = ['hit'];

        $this->assertSame(0.0, EvaluationMetrics::precisionAtK($returned, $relevant, 2));
        $this->assertSame(1 / 3, EvaluationMetrics::precisionAtK($returned, $relevant, 3));
    }

    public function test_mean_ignores_undefined_values_and_returns_null_when_none_are_defined(): void
    {
        $this->assertSame(0.5, EvaluationMetrics::mean([1.0, 0.0, null]));
        $this->assertNull(EvaluationMetrics::mean([null, null]));
        $this->assertNull(EvaluationMetrics::mean([]));
    }

    public function test_exact_set_accuracy_is_order_insensitive_but_not_missing_label_tolerant(): void
    {
        $this->assertSame(1.0, EvaluationMetrics::exactSetAccuracy([
            [['Comedy', 'Romance'], ['Romance', 'Comedy']],
            [[], []],
        ]));
        $this->assertSame(0.5, EvaluationMetrics::exactSetAccuracy([
            [['Comedy'], ['Comedy']],
            [['Comedy'], []],
        ]));
        $this->assertNull(EvaluationMetrics::exactSetAccuracy([]));
    }

    public function test_rate_delta_and_harmonic_helpers(): void
    {
        $this->assertSame(0.5, EvaluationMetrics::rate([true, false]));
        $this->assertNull(EvaluationMetrics::rate([]));
        $this->assertSame(0.25, EvaluationMetrics::delta(0.75, 0.5));
        $this->assertNull(EvaluationMetrics::delta(null, 0.5));
        $this->assertNull(EvaluationMetrics::harmonic(null, 0.5));
        $this->assertSame(0.0, EvaluationMetrics::harmonic(0.0, 0.0));
    }
}
