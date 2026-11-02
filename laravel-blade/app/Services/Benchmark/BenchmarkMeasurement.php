<?php

declare(strict_types=1);

namespace App\Services\Benchmark;

use Illuminate\Support\Facades\DB;

/**
 * Small, deterministic timer around a production service call.
 *
 * Timing and query logging are measured in separate passes: enabling the query
 * log changes the cost of the call, so it must not distort the wall-clock
 * samples. The query pass runs once and only feeds the SQL diagnostics.
 */
final class BenchmarkMeasurement
{
    /**
     * @param  callable():mixed  $callback
     * @param  (callable():void)|null  $reset  Runs before every timed sample but outside the timer.
     * @return array{
     *     iterations: int,
     *     warmups: int,
     *     timing_ms: array{min: float, median: float, p95: float, mean: float, max: float, samples: list<float>},
     *     queries: int,
     *     result: mixed,
     *     sql: array{exists: int, comics_selects: int, writes: int, distinct: int, samples: list<string>}
     * }
     */
    public function measure(callable $callback, int $iterations = 5, int $warmups = 1, ?callable $reset = null): array
    {
        $iterations = max(1, $iterations);
        $warmups = max(0, $warmups);

        for ($index = 0; $index < $warmups; $index++) {
            if ($reset !== null) {
                $reset();
            }
            $callback();
        }

        $samples = [];
        $result = null;
        for ($index = 0; $index < $iterations; $index++) {
            if ($reset !== null) {
                $reset();
            }
            $start = hrtime(true);
            $result = $callback();
            $samples[] = (hrtime(true) - $start) / 1_000_000;
        }

        $queries = $this->capture($callback, $reset);

        return [
            'iterations' => $iterations,
            'warmups' => $warmups,
            'timing_ms' => $this->summarize($samples),
            'queries' => count($queries),
            'result' => $result,
            'sql' => $this->analyze($queries),
        ];
    }

    /** @return list<array{query: string, bindings: array, time: float}> */
    private function capture(callable $callback, ?callable $reset): array
    {
        $connection = DB::connection();
        $wasLogging = $connection->logging();

        if ($reset !== null) {
            $reset();
        }

        $connection->flushQueryLog();
        $connection->enableQueryLog();
        try {
            $callback();

            return $connection->getQueryLog();
        } finally {
            if (! $wasLogging) {
                $connection->disableQueryLog();
            }
        }
    }

    /** @param list<float> $samples */
    private function summarize(array $samples): array
    {
        sort($samples);
        $count = count($samples);
        $sum = array_sum($samples);
        $p95Index = max(0, min($count - 1, (int) ceil($count * 0.95) - 1));

        return [
            'min' => round($samples[0], 3),
            'median' => round($samples[(int) floor(($count - 1) / 2)], 3),
            'p95' => round($samples[$p95Index], 3),
            'mean' => round($sum / max(1, $count), 3),
            'max' => round($samples[$count - 1], 3),
            'samples' => array_map(static fn (float $value): float => round($value, 3), $samples),
        ];
    }

    /** @param list<array{query: string, bindings: array, time: float}> $queries */
    private function analyze(array $queries): array
    {
        $exists = 0;
        $comicsSelects = 0;
        $writes = 0;
        $fingerprints = [];

        foreach ($queries as $query) {
            $sql = strtolower((string) ($query['query'] ?? ''));
            $exists += (int) preg_match_all('/\bexists\b/', $sql);
            if (preg_match('/\bfrom\s+["`]?comics["`]?\b/', $sql) === 1) {
                $comicsSelects++;
            }
            if (preg_match('/^\s*(insert|update|delete)\b/', $sql) === 1) {
                $writes++;
            }

            $normalized = preg_replace('/\s+/', ' ', trim($sql)) ?? '';
            $normalized = preg_replace('/\b\d+\b/', '?', $normalized) ?? '';
            if ($normalized !== '') {
                $fingerprints[substr($normalized, 0, 180)] = true;
            }
        }

        return [
            'exists' => $exists,
            'comics_selects' => $comicsSelects,
            'writes' => $writes,
            'distinct' => count($fingerprints),
            'samples' => array_slice(array_keys($fingerprints), 0, 5),
        ];
    }
}
