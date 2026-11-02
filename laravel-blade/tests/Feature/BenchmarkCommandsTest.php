<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class BenchmarkCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendation_benchmark_reports_time_sql_exists_and_hydration(): void
    {
        $output = new BufferedOutput();
        $exit = Artisan::call('benchmark:recommendation', [
            '--sizes' => '60',
            '--iterations' => '1',
            '--format' => 'json',
        ], $output);

        $raw = trim($output->fetch());
        $this->assertSame(0, $exit, $raw);
        $this->assertStringStartsWith('{', $raw, substr($raw, 0, 400));
        $report = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(1, $report['schema_version']);
        $this->assertSame('recommendation', $report['benchmark']);
        $this->assertSame('array', $report['environment']['cache_driver']);
        $this->assertNotEmpty($report['results']);

        $cases = collect($report['results']);
        $coldUser = $cases->firstWhere('case', 'forUser');
        $warmUser = $cases->where('case', 'forUser')->firstWhere('cache', 'warm');
        $multi = $cases->firstWhere('case', 'preference:12_conditions');

        $this->assertSame('cold', $coldUser['cache']);
        $this->assertGreaterThan(0, $coldUser['queries']);
        $this->assertSame('warm', $warmUser['cache']);
        $this->assertSame(0, $warmUser['queries']);
        $this->assertSame(12, $multi['conditions']);
        $this->assertGreaterThanOrEqual(12, $multi['sql']['exists']);
        $this->assertGreaterThanOrEqual(1, $multi['queries']);
        $this->assertArrayHasKey('median', $multi['timing_ms']);
    }

    public function test_search_benchmark_reports_query_counts_cache_and_description_normalized(): void
    {
        $output = new BufferedOutput();
        $exit = Artisan::call('benchmark:search', [
            '--sizes' => '60',
            '--iterations' => '1',
            '--format' => 'json',
        ], $output);

        $raw = trim($output->fetch());
        $this->assertSame(0, $exit, $raw);
        $this->assertStringStartsWith('{', $raw, substr($raw, 0, 400));
        $report = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('search', $report['benchmark']);
        $cases = collect($report['results']);

        $liveCold = $cases->firstWhere('case', 'live:title_accented');
        $liveWarm = $cases->where('case', 'live:title_accented')->firstWhere('cache', 'warm');
        $advanced = $cases->firstWhere('case', 'advanced:keyword_unaccented');
        $minChapters = $cases->firstWhere('case', 'advanced:min_chapters');

        $this->assertSame('cold', $liveCold['cache']);
        $this->assertGreaterThan(0, $liveCold['queries']);
        $this->assertSame(0, $liveWarm['queries']);

        $this->assertSame(1, $advanced['sql']['writes'], 'advanced keyword search records a search keyword');
        $this->assertGreaterThanOrEqual(1, $advanced['result_count']);
        $this->assertGreaterThan(0, $minChapters['result_count']);

        $diagnostics = $report['diagnostics']['per_size']['60'];
        $this->assertTrue($diagnostics['cache_invalidation']['stale_after_update']);
        $this->assertTrue($diagnostics['cache_invalidation']['refreshed_after_flush']);
        $this->assertGreaterThan(0, $diagnostics['description_column']['normalized']['matches']);
        $this->assertSame(0, $liveCold['sql']['writes'], 'live search must not write');
    }
}
