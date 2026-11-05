<?php

declare(strict_types=1);

namespace App\Services\Evaluation;

use App\Services\RecommendationTaxonomyService;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Loads the pre-change rule parser from git HEAD for a reproducible
 * before/after comparison without creating a second committed implementation.
 */
final class BaselineParserLoader
{
    private const RELATIVE_PATH = 'laravel-blade/app/Services/AI/RuleBasedPreferenceParser.php';

    private const CLASS_NAME = 'App\\Services\\AI\\EvaluationBaseline\\RuleBasedPreferenceParser';

    /** @return array{available: bool, parser: ?object, label: string, commit: ?string, sha256: ?string, note: ?string, reason: ?string} */
    public function load(): array
    {
        try {
            $result = Process::path(base_path())->timeout(20)->run([
                'git', 'show', 'HEAD:'.self::RELATIVE_PATH,
            ]);
            if (! $result->successful() || trim($result->output()) === '') {
                return $this->unavailable('git show HEAD failed: '.trim($result->errorOutput()));
            }
            $source = $result->output();
            $commit = trim(Process::path(base_path())->timeout(10)->run(['git', 'rev-parse', '--short', 'HEAD'])->output());
            $sha256 = hash('sha256', $source);
            $current = is_file(base_path(self::RELATIVE_PATH)) ? file_get_contents(base_path(self::RELATIVE_PATH)) : null;

            if (! class_exists(self::CLASS_NAME, false)) {
                $count = 0;
                $rewritten = preg_replace(
                    '/namespace\s+App\\\\Services\\\\AI\s*;/',
                    'namespace App\\\\Services\\\\AI\\\\EvaluationBaseline;',
                    $source,
                    1,
                    $count,
                );
                if (! is_string($rewritten) || $count !== 1) {
                    return $this->unavailable('Unable to rewrite the HEAD parser namespace.');
                }
                $directory = storage_path('framework/testing');
                if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
                    return $this->unavailable('Unable to create a temporary directory for the baseline parser.');
                }
                $file = $directory.DIRECTORY_SEPARATOR.'nlp_eval_head_parser_'.getmypid().'_'.substr($sha256, 0, 12).'.php';
                if (file_put_contents($file, $rewritten, LOCK_EX) === false) {
                    return $this->unavailable('Unable to write the temporary baseline parser.');
                }
                try {
                    require $file;
                } finally {
                    @unlink($file);
                }
            }

            if (! class_exists(self::CLASS_NAME, false)) {
                return $this->unavailable('The HEAD parser class could not be loaded.');
            }
            $class = self::CLASS_NAME;

            return [
                'available' => true,
                'parser' => new $class(app(RecommendationTaxonomyService::class)),
                'label' => 'head',
                'commit' => $commit !== '' ? $commit : null,
                'sha256' => substr($sha256, 0, 16),
                'note' => is_string($current) && hash('sha256', $current) === $sha256
                    ? 'HEAD parser is identical to the working tree parser.'
                    : null,
                'reason' => null,
            ];
        } catch (Throwable $exception) {
            return $this->unavailable($exception->getMessage());
        }
    }

    /** @return array{available: false, parser: null, label: string, commit: null, sha256: null, note: null, reason: string} */
    private function unavailable(string $reason): array
    {
        return [
            'available' => false,
            'parser' => null,
            'label' => 'head',
            'commit' => null,
            'sha256' => null,
            'note' => null,
            'reason' => $reason !== '' ? $reason : 'Baseline parser is unavailable.',
        ];
    }
}
