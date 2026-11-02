<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Evaluation\BaselineParserLoader;
use App\Services\Evaluation\NlpEvaluationCorpus;
use App\Services\Evaluation\NlpEvaluationDataset;
use App\Services\Evaluation\NlpEvaluationReport;
use App\Services\Evaluation\NlpEvaluator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use JsonException;
use Throwable;

class EvaluateNlpCommand extends Command
{
    protected $signature = 'nlp:evaluate
        {--dataset= : Path to the labelled dataset JSON}
        {--group=* : Only evaluate these dataset groups (repeatable)}
        {--k= : Override the retrieval cut-off K}
        {--baseline=none : Compare with a baseline parser: none or head}
        {--format=table : Output format: table or json}
        {--output= : Write the full JSON report to this path}
        {--force : Allow running in the production environment}';

    protected $description = 'Evaluate ComicX Vietnamese NLP parsing and retrieval against the labelled dataset';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to run the NLP evaluation in production without --force.');

            return self::FAILURE;
        }

        $format = (string) $this->option('format');
        if (! in_array($format, ['table', 'json'], true)) {
            $this->error('--format must be table or json.');

            return self::FAILURE;
        }
        $baselineOption = (string) $this->option('baseline');
        if (! in_array($baselineOption, ['none', 'head'], true)) {
            $this->error('--baseline must be none or head.');

            return self::FAILURE;
        }

        try {
            $dataset = NlpEvaluationDataset::fromFile($this->datasetPath());
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $groups = array_values(array_filter((array) $this->option('group'), fn ($group): bool => is_string($group) && $group !== ''));
        $unknownGroups = array_values(array_diff($groups, $dataset->groups()));
        if ($unknownGroups !== []) {
            $this->error('Unknown group(s): '.implode(', ', $unknownGroups));
            $this->line('Available groups: '.implode(', ', $dataset->groups()));

            return self::FAILURE;
        }

        $k = $dataset->k();
        if ($this->option('k') !== null) {
            $k = (int) $this->option('k');
            if ($k < 1 || $k > 50) {
                $this->error('--k must be an integer between 1 and 50.');

                return self::FAILURE;
            }
        }

        $baseline = ['available' => false, 'parser' => null, 'label' => 'none', 'commit' => null,
            'sha256' => null, 'note' => null, 'reason' => null];
        if ($baselineOption === 'head') {
            $baseline = app(BaselineParserLoader::class)->load();
            if (! $baseline['available']) {
                $this->warn('Baseline parser unavailable; continuing without before/after comparison. '.$baseline['reason']);
            }
        }

        $corpus = app(NlpEvaluationCorpus::class);
        $evaluator = app(NlpEvaluator::class);
        $cache = app('cache');
        $originalCacheDriver = $cache->getDefaultDriver();
        $originalAiEnabled = config('ai.enabled');
        $connection = DB::connection();
        $startingTransactionLevel = $connection->transactionLevel();

        $cache->setDefaultDriver('array');
        $cache->driver('array')->flush();
        config(['ai.enabled' => false]);

        try {
            $connection->beginTransaction();
            try {
                $corpus->seed($dataset);
                $current = $evaluator->evaluate($dataset, $corpus, 'current', null, $k, $groups);
                $baselineEvaluation = $baseline['available']
                    ? $evaluator->evaluate($dataset, $corpus, 'head', fn (string $query): array => $baseline['parser']->parse($query), $k, $groups)
                    : null;
            } finally {
                while ($connection->transactionLevel() > $startingTransactionLevel) {
                    $connection->rollBack();
                }
            }
        } catch (Throwable $exception) {
            $this->error('NLP evaluation failed: '.$exception::class.': '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            $cache->driver('array')->flush();
            $cache->setDefaultDriver($originalCacheDriver);
            config(['ai.enabled' => $originalAiEnabled]);
        }

        $comparison = NlpEvaluationReport::compare($current, $baselineEvaluation);
        $changes = NlpEvaluationReport::changes($comparison);
        $report = $this->report(
            $dataset,
            $corpus,
            $current,
            $baselineEvaluation,
            $baseline,
            $comparison,
            $changes,
            $k,
            $groups,
            $baselineOption,
        );

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

            return self::SUCCESS;
        }

        $this->renderTable($dataset, $current, $baselineEvaluation, $comparison, $changes, $k, $groups);

        return self::SUCCESS;
    }

    private function datasetPath(): string
    {
        $option = $this->option('dataset');

        return $option !== null
            ? $this->absolutePath((string) $option)
            : base_path('tests/Fixtures/nlp/evaluation_dataset.json');
    }

    private function absolutePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }

    private function relativePath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }
        $root = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }

    private function report(
        NlpEvaluationDataset $dataset,
        NlpEvaluationCorpus $corpus,
        array $current,
        ?array $baselineEvaluation,
        array $baseline,
        array $comparison,
        array $changes,
        int $k,
        array $groups,
        string $baselineOption,
    ): array {
        $pipeline = $current['overall']['retrieval']['pipeline'];
        $oracle = $current['overall']['retrieval']['oracle'];

        return [
            'dataset' => [
                'path' => $this->relativePath($dataset->source()),
                'checksum_sha256' => $dataset->checksum(),
                'version' => $dataset->version(),
                'k' => $k,
                'groups_requested' => $groups,
                'cases_total' => count($dataset->cases()),
                'cases_evaluated' => $current['cases_evaluated'],
                'corpus_comics' => count($dataset->comics()),
                'corpus_genres' => count($dataset->genres()),
            ],
            'environment' => [
                'generated_at' => now()->toIso8601String(),
                'app_env' => app()->environment(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'git_head' => $baseline['commit'] ?? $this->gitHead(),
                'db_connection' => $connection = DB::connection()->getName(),
                'db_database' => config("database.connections.{$connection}.database"),
                'cache_driver' => 'array',
                'ai_enabled' => false,
                'provider_calls' => 0,
                'notes' => [
                    'AI provider access is force-disabled; valid parses use the rule-based fallback, so fallback_rate is 100% by design and not a quality signal.',
                    'Evaluation comics are seeded with the nlp-eval- prefix inside a transaction that is always rolled back.',
                    'Retrieval is scored against the synthetic corpus; out-of-corpus rows count as false positives.',
                    'Lookup intent is derived from normalized catalog title/alt-title/author/description matches because the production parser has no intent classifier.',
                ],
            ],
            'baseline' => [
                'requested' => $baselineOption,
                'available' => $baseline['available'],
                'label' => $baseline['label'],
                'git_commit' => $baseline['commit'],
                'source_sha256' => $baseline['sha256'],
                'note' => $baseline['note'],
                'reason' => $baseline['reason'],
            ],
            'current' => $current,
            'baseline_evaluation' => $baselineEvaluation,
            'comparison' => $comparison,
            'changes' => $changes,
            'diagnostics' => [
                'retrieval_pipeline_failures' => $pipeline['failures'],
                'retrieval_oracle_failures' => $oracle['failures'],
                'publication_leaks' => $pipeline['leakage_cases'],
                'oracle_publication_leaks' => $oracle['leakage_cases'],
                'exclusion_violations' => $pipeline['exclusion_violation_cases'],
                'out_of_corpus_results' => $pipeline['out_of_corpus_results'],
                'baseline_regressions' => $changes['regressions'],
                'baseline_improvements' => $changes['improvements'],
            ],
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

    private function renderTable(
        NlpEvaluationDataset $dataset,
        array $current,
        ?array $baseline,
        array $comparison,
        array $changes,
        int $k,
        array $groups,
    ): void {
        $this->info('ComicX NLP evaluation');
        $this->line('Dataset: '.($dataset->source() ?? 'inline').' (sha256 '.substr($dataset->checksum(), 0, 16).')');
        $this->line('Cases: '.$current['cases_evaluated'].' | K: '.$k
            .' | Groups: '.($groups === [] ? 'all' : implode(', ', $groups))
            .' | Baseline: '.($baseline === null ? 'none' : 'HEAD'));
        $this->newLine();

        $summaryRows = [
            ['Intent accuracy', 'intent_accuracy'],
            ['Needs-more-info accuracy', 'needs_more_info_accuracy'],
            ['Slot precision (micro)', 'slots_precision'],
            ['Slot recall (micro)', 'slots_recall'],
            ['Slot F1 (micro)', 'slots_f1'],
            ['Slot exact match', 'slots_exact_match_accuracy'],
            ['Status accuracy', 'status_accuracy'],
            ['Status accuracy (labelled)', 'status_labeled_accuracy'],
            ['Exclusion accuracy', 'exclusion_accuracy'],
            ['Exclusion F1 (micro)', 'exclusion_f1'],
            ['Invalid-input accuracy', 'invalid_accuracy'],
            ['Clarification rate', 'clarification_rate'],
            ['Fallback rate', 'fallback_rate'],
            ['Error rate', 'error_rate'],
            ['P@K (pipeline)', 'retrieval_pipeline_precision_at_k'],
            ['R@K (pipeline)', 'retrieval_pipeline_recall_at_k'],
            ['P@K (oracle slots)', 'retrieval_oracle_precision_at_k'],
            ['R@K (oracle slots)', 'retrieval_oracle_recall_at_k'],
            ['Empty-result accuracy', 'retrieval_pipeline_empty_accuracy'],
            ['Leakage cases', 'retrieval_leakage_cases'],
            ['Exclusion violations', 'retrieval_exclusion_violations'],
        ];
        $rows = [];
        foreach ($summaryRows as [$label, $key]) {
            $value = $comparison['overall'][$key] ?? ['current' => null, 'baseline' => null, 'delta' => null];
            $rows[] = [
                $label,
                $this->metricCell($key, $value['current']),
                $this->metricCell($key, $value['baseline']),
                $this->deltaCell($value['delta'], $value['unit'] ?? 'ratio'),
            ];
        }
        $this->table(['Metric', 'Current', 'HEAD baseline', 'Delta'], $rows);

        $groupRows = [];
        foreach (array_keys($current['groups']) as $group) {
            $entry = $comparison['groups'][$group] ?? [];
            $groupRows[] = [
                $group,
                $current['groups'][$group]['cases'],
                $this->pairCell($entry, 'intent_accuracy'),
                $this->pairCell($entry, 'slots_f1'),
                $this->pairCell($entry, 'exclusion_accuracy'),
                $this->pairCell($entry, 'retrieval_pipeline_precision_at_k'),
                $this->pairCell($entry, 'retrieval_pipeline_recall_at_k'),
                $this->pairCell($entry, 'retrieval_leakage_cases'),
            ];
        }
        $this->table(
            ['Group', 'Cases', 'Intent', 'Slot F1', 'Exclusion', 'P@K', 'R@K', 'Leaks'],
            $groupRows,
        );

        $retrievalRows = [];
        foreach (['oracle' => 'Oracle (expected slots)', 'pipeline' => 'Pipeline (predicted slots)'] as $mode => $label) {
            $result = $current['overall']['retrieval'][$mode];
            $retrievalRows[] = [
                $label,
                $result['cases'],
                $this->pairCell($comparison['overall'], 'retrieval_'.$mode.'_precision_at_k'),
                $this->pairCell($comparison['overall'], 'retrieval_'.$mode.'_recall_at_k'),
                $this->pct($result['empty_accuracy']),
                $result['leakage_case_count'],
                $result['exclusion_violations'],
            ];
        }
        $this->table(['Retrieval', 'Cases', 'P@K cur/base', 'R@K cur/base', 'Empty acc', 'Leak cases', 'Excl violations'], $retrievalRows);

        $diagnostics = $current['overall']['retrieval']['pipeline'];
        if ($diagnostics['failures'] !== []) {
            $this->warn('Retrieval cases below perfect P@K/R@K: '.implode(', ', $diagnostics['failures']));
        }
        if ($diagnostics['leakage_cases'] !== []) {
            $this->warn('Publication leakage detected:');
            foreach ($diagnostics['leakage_cases'] as $caseId => $keys) {
                $this->line('  - '.$caseId.': '.implode(', ', $keys));
            }
        }
        if ($diagnostics['exclusion_violation_cases'] !== []) {
            $this->warn('Exclusion violations detected:');
            foreach ($diagnostics['exclusion_violation_cases'] as $caseId => $keys) {
                $this->line('  - '.$caseId.': '.implode(', ', $keys));
            }
        }
        if ($changes['regressions'] !== []) {
            $this->newLine();
            $this->warn('Baseline regressions (group metrics, HEAD -> current):');
            foreach (array_slice($changes['regressions'], 0, 10) as $regression) {
                $this->line('  - '.$regression['group'].' / '.$regression['metric'].': '
                    .$this->changeValue($regression['baseline'], $regression['unit'] ?? 'ratio').' -> '
                    .$this->changeValue($regression['current'], $regression['unit'] ?? 'ratio')
                    .' ('.$this->deltaCell($regression['delta'], $regression['unit'] ?? 'ratio').')');
            }
        }
        if ($changes['improvements'] !== []) {
            $this->newLine();
            $this->info('Baseline improvements (group metrics, HEAD -> current):');
            foreach (array_slice($changes['improvements'], 0, 10) as $improvement) {
                $this->line('  - '.$improvement['group'].' / '.$improvement['metric'].': '
                    .$this->changeValue($improvement['baseline'], $improvement['unit'] ?? 'ratio').' -> '
                    .$this->changeValue($improvement['current'], $improvement['unit'] ?? 'ratio')
                    .' ('.$this->deltaCell($improvement['delta'], $improvement['unit'] ?? 'ratio').')');
            }
        }
    }

    private function metricCell(string $key, mixed $value): string
    {
        return in_array($key, ['retrieval_leakage_cases', 'retrieval_exclusion_violations'], true)
            ? $this->count($value)
            : $this->pct($value);
    }

    private function pairCell(array $metrics, string $key): string
    {
        $entry = $metrics[$key] ?? null;
        if (! is_array($entry)) {
            return $this->pct(null);
        }
        $current = $this->metricCell($key, $entry['current'] ?? null);
        if ($entry['baseline'] === null) {
            return $current;
        }

        return $current.' / '.$this->metricCell($key, $entry['baseline']);
    }

    private function deltaCell(mixed $delta, string $unit = 'ratio'): string
    {
        if (! is_numeric($delta)) {
            return '-';
        }
        if ($unit === 'count') {
            return ((float) $delta >= 0 ? '+' : '').(int) $delta;
        }

        return ((float) $delta >= 0 ? '+' : '').number_format((float) $delta * 100, 1).' pp';
    }

    private function pct(mixed $value): string
    {
        return is_numeric($value) ? number_format((float) $value * 100, 1).'%' : 'n/a';
    }

    private function count(mixed $value): string
    {
        return is_numeric($value) ? (string) (int) $value : 'n/a';
    }

    private function changeValue(mixed $value, string $unit): string
    {
        return $unit === 'count' ? $this->count($value) : $this->pct($value);
    }
}
