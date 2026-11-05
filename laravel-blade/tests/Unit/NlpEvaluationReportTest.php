<?php

namespace Tests\Unit;

use App\Services\Evaluation\NlpEvaluationReport;
use PHPUnit\Framework\TestCase;

class NlpEvaluationReportTest extends TestCase
{
    public function test_summary_flattens_nested_metrics_and_keeps_undefined_values_null(): void
    {
        $summary = NlpEvaluationReport::summary([
            'overall' => [
                'cases' => 3,
                'intent_accuracy' => 0.75,
                'slots' => ['micro' => ['precision' => 1.0, 'recall' => 0.5, 'f1' => 2 / 3]],
                'status' => ['accuracy' => 1.0, 'labeled_accuracy' => null, 'false_positives' => 0],
                'retrieval' => ['pipeline' => ['precision_at_k' => 0.5, 'leakage_case_count' => 1]],
            ],
        ]);

        $this->assertSame(3, $summary['cases']);
        $this->assertSame(0.75, $summary['intent_accuracy']);
        $this->assertSame(1.0, $summary['slots_precision']);
        $this->assertSame(2 / 3, $summary['slots_f1']);
        $this->assertNull($summary['status_labeled_accuracy']);
        $this->assertSame(0.5, $summary['retrieval_pipeline_precision_at_k']);
        $this->assertSame(1, $summary['retrieval_leakage_cases']);
        $this->assertNull($summary['exclusion_accuracy']);
    }

    public function test_compare_computes_ratio_and_count_deltas_without_hiding_null_baselines(): void
    {
        $current = ['overall' => [
            'cases' => 2,
            'intent_accuracy' => 1.0,
            'retrieval' => ['pipeline' => ['leakage_case_count' => 2, 'exclusion_violations' => 1]],
        ], 'groups' => []];
        $baseline = ['overall' => [
            'cases' => 2,
            'intent_accuracy' => 0.5,
            'retrieval' => ['pipeline' => ['leakage_case_count' => 0, 'exclusion_violations' => 3]],
        ], 'groups' => []];

        $comparison = NlpEvaluationReport::compare($current, $baseline);
        $this->assertSame(0.5, $comparison['overall']['intent_accuracy']['delta']);
        $this->assertSame('ratio', $comparison['overall']['intent_accuracy']['unit']);
        $this->assertSame(2, $comparison['overall']['retrieval_leakage_cases']['delta']);
        $this->assertSame('count', $comparison['overall']['retrieval_leakage_cases']['unit']);
        $this->assertSame(-2, $comparison['overall']['retrieval_exclusion_violations']['delta']);

        $withoutBaseline = NlpEvaluationReport::compare($current, null);
        $this->assertNull($withoutBaseline['overall']['intent_accuracy']['baseline']);
        $this->assertNull($withoutBaseline['overall']['intent_accuracy']['delta']);
    }

    public function test_changes_respects_metric_direction_and_skips_neutral_metrics(): void
    {
        $comparison = ['groups' => ['negation' => [
            'slots_f1' => ['current' => 1.0, 'baseline' => 0.5, 'delta' => 0.5, 'unit' => 'ratio'],
            'error_rate' => ['current' => 0.1, 'baseline' => 0.0, 'delta' => 0.1, 'unit' => 'ratio'],
            'retrieval_leakage_cases' => ['current' => 2, 'baseline' => 0, 'delta' => 2, 'unit' => 'count'],
            'retrieval_exclusion_violations' => ['current' => 1, 'baseline' => 4, 'delta' => -3, 'unit' => 'count'],
            'clarification_rate' => ['current' => 0.1, 'baseline' => 0.9, 'delta' => -0.8, 'unit' => 'ratio'],
            'intent_accuracy' => ['current' => 1.0, 'baseline' => 1.0, 'delta' => 0.0, 'unit' => 'ratio'],
        ]]];

        $changes = NlpEvaluationReport::changes($comparison);
        $regressionMetrics = array_column($changes['regressions'], 'metric');
        $improvementMetrics = array_column($changes['improvements'], 'metric');

        $this->assertContains('error_rate', $regressionMetrics);
        $this->assertContains('retrieval_leakage_cases', $regressionMetrics);
        $this->assertContains('slots_f1', $improvementMetrics);
        $this->assertContains('retrieval_exclusion_violations', $improvementMetrics);
        $this->assertNotContains('clarification_rate', $regressionMetrics);
        $this->assertNotContains('clarification_rate', $improvementMetrics);
        $this->assertNotContains('intent_accuracy', $regressionMetrics);
        $this->assertNotContains('intent_accuracy', $improvementMetrics);
    }
}
