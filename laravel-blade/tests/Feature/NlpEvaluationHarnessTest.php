<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Services\AI\AIClientInterface;
use App\Services\Evaluation\BaselineParserLoader;
use App\Services\Evaluation\NlpEvaluationCorpus;
use App\Services\Evaluation\NlpEvaluationDataset;
use App\Services\Evaluation\NlpEvaluationReport;
use App\Services\Evaluation\NlpEvaluator;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Tests\TestCase;

class NlpEvaluationHarnessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Evaluation must be deterministic and offline in tests.
        config(['ai.enabled' => false]);
        Cache::flush();
    }

    private function dataset(): NlpEvaluationDataset
    {
        return NlpEvaluationDataset::fromFile(base_path('tests/Fixtures/nlp/evaluation_dataset.json'));
    }

    /** @return array<string, array<string, mixed>> */
    private function cases(array $evaluation): array
    {
        $cases = [];
        foreach ($evaluation['cases'] as $case) {
            $cases[$case['id']] = $case;
        }

        return $cases;
    }

    public function test_current_parser_matches_the_labelled_dataset(): void
    {
        $dataset = $this->dataset();
        $corpus = app(NlpEvaluationCorpus::class);
        $corpus->seed($dataset);
        $evaluation = app(NlpEvaluator::class)->evaluate($dataset, $corpus, 'current');

        $this->assertSame(41, $evaluation['cases_evaluated']);
        $this->assertSame(1.0, $evaluation['overall']['intent_accuracy']);
        $this->assertSame(1.0, $evaluation['overall']['needs_more_info_accuracy']);
        $this->assertSame(1.0, $evaluation['overall']['slots']['micro']['f1']);
        $this->assertSame(1.0, $evaluation['overall']['slots']['exact_match_accuracy']);
        $this->assertSame(1.0, $evaluation['overall']['status']['accuracy']);
        $this->assertSame(1.0, $evaluation['overall']['exclusions']['accuracy']);
        $this->assertSame(1.0, $evaluation['overall']['invalid']['accuracy']);
        $this->assertSame(0.0, $evaluation['overall']['error_rate']);
        $this->assertSame([], $evaluation['overall']['errors']);
    }

    public function test_parser_evaluation_preserves_negation_and_multiple_conditions(): void
    {
        $dataset = $this->dataset();
        $corpus = app(NlpEvaluationCorpus::class);
        $corpus->seed($dataset);
        $evaluation = app(NlpEvaluator::class)->evaluate($dataset, $corpus, 'current', null, 8);
        $cases = $this->cases($evaluation);

        $comedy = $cases['negation-comedy-no-romance'];
        $this->assertSame('preferences', $comedy['predicted_intent']);
        $this->assertSame(['Comedy'], $comedy['slots']['predicted']['genres']);
        $this->assertSame(['Comedy'], $comedy['slots']['predicted']['tones']);
        $this->assertSame(['Romance'], $comedy['exclusions']['predicted']);
        $this->assertSame(['hai_huoc'], $comedy['search']['pipeline']['returned']);

        $unaccented = $cases['negation-unaccented-comedy-no-romance'];
        $this->assertSame(['Comedy'], $unaccented['slots']['predicted']['genres']);
        $this->assertSame(['Romance'], $unaccented['exclusions']['predicted']);
        $this->assertSame(['hai_huoc'], $unaccented['search']['pipeline']['returned']);

        $dung = $cases['accented-dung-negation'];
        $this->assertSame(['Weak to Strong'], $dung['slots']['predicted']['character_traits']);
        $this->assertSame(['Romance'], $dung['exclusions']['predicted']);
        $this->assertEqualsCanonicalizing(['solo', 'vo_luyen'], $dung['search']['pipeline']['returned']);

        $multi = $cases['multi-fantasy-completed-op-no-harem'];
        $this->assertSame('completed', $multi['status']['predicted']);
        $this->assertSame(['Harem'], $multi['exclusions']['predicted']);
        // Full matches are returned exclusively: a status- or tag-only match can
        // no longer dilute P@K once solo satisfies every requested condition.
        $this->assertSame(['solo'], $multi['search']['pipeline']['returned']);
        $this->assertSame(1.0, $multi['search']['pipeline']['precision_at_k']);

        // Multi-value dimensions must match every requested value before a
        // result counts as full. These were the P3 ranking failures.
        $themes = $cases['slang-tu-tien-xuyen-khong'];
        $this->assertSame(['isekai_cultivation'], $themes['search']['pipeline']['returned']);
        $this->assertSame(1.0, $themes['search']['pipeline']['precision_at_k']);

        $traits = $cases['slang-cold-smart'];
        $this->assertSame(['system_revenge'], $traits['search']['pipeline']['returned']);
        $this->assertSame(1.0, $traits['search']['pipeline']['precision_at_k']);

        $school = $cases['multi-school-comedy-no-romance'];
        $this->assertSame(['hai_huoc'], $school['search']['pipeline']['returned']);
        $this->assertSame(1.0, $school['search']['pipeline']['precision_at_k']);
    }

    public function test_search_evaluation_scores_labelled_results_and_enforces_public_lookup_rules(): void
    {
        $dataset = $this->dataset();
        $corpus = app(NlpEvaluationCorpus::class);
        $corpus->seed($dataset);
        $evaluation = app(NlpEvaluator::class)->evaluate($dataset, $corpus, 'current', null, 8, ['lookup']);
        $cases = $this->cases($evaluation);

        $this->assertSame(['vo_luyen'], $cases['lookup-title-accented']['search']['pipeline']['returned']);
        $this->assertSame(1.0, $cases['lookup-title-accented']['search']['pipeline']['precision_at_k']);
        $this->assertSame(['solo'], $cases['lookup-author']['search']['pipeline']['returned']);
        $this->assertSame(['trinh_tham'], $cases['lookup-description']['search']['pipeline']['returned']);

        // Unaccented author lookup resolves through the author's ASCII slug.
        $author = $cases['lookup-author-unaccented']['search']['pipeline'];
        $this->assertSame(['vo_luyen'], $author['returned']);
        $this->assertSame(1.0, $author['precision_at_k']);
        $this->assertSame(1.0, $author['recall_at_k']);

        // Unaccented description lookup uses comics.description_normalized.
        $description = $cases['lookup-description-unaccented']['search']['pipeline'];
        $this->assertSame(['trinh_tham'], $description['returned']);
        $this->assertSame(1.0, $description['precision_at_k']);

        // Unpublished comics must never appear in public search results.
        $unpublished = $cases['lookup-unpublished-hidden']['search']['pipeline'];
        $this->assertSame([], $unpublished['returned']);
        $this->assertSame([], $unpublished['leaked']);
        $this->assertTrue($unpublished['empty_ok']);
        $this->assertNotContains('lookup-unpublished-hidden', $evaluation['overall']['retrieval']['pipeline']['failures']);
    }

    public function test_before_after_comparison_uses_the_same_dataset_without_assuming_an_improvement(): void
    {
        $dataset = $this->dataset();
        $corpus = app(NlpEvaluationCorpus::class);
        $corpus->seed($dataset);
        $groups = ['negation', 'multi', 'unaccented'];
        $evaluator = app(NlpEvaluator::class);

        $current = $evaluator->evaluate($dataset, $corpus, 'current', null, 8, $groups);
        $baselineParser = static fn (string $query): array => [
            'genres' => [], 'themes' => [], 'settings' => [], 'character_traits' => [],
            'tones' => [], 'relationships' => [], 'status' => null, 'exclude' => [],
            'needs_more_info' => true, 'follow_up_question' => 'Bạn thích thể loại nào?',
        ];
        $baseline = $evaluator->evaluate($dataset, $corpus, 'stub-baseline', $baselineParser, 8, $groups);

        $comparison = NlpEvaluationReport::compare($current, $baseline);
        $this->assertSame(1.0, $comparison['overall']['slots_f1']['current']);
        $this->assertSame(0.0, $comparison['overall']['slots_f1']['baseline']);
        $this->assertSame(1.0, $comparison['overall']['slots_f1']['delta']);
        $this->assertSame(1.0, $comparison['overall']['exclusion_accuracy']['current']);
        $this->assertLessThan(1.0, $comparison['overall']['exclusion_accuracy']['baseline']);

        $changes = NlpEvaluationReport::changes($comparison);
        $this->assertSame([], $changes['regressions']);
        $this->assertNotEmpty($changes['improvements']);
    }

    public function test_baseline_loader_rewrites_and_loads_the_head_parser_from_git(): void
    {
        $this->seed(RecommendationTaxonomySeeder::class);
        $source = (string) file_get_contents(app_path('Services/AI/RuleBasedPreferenceParser.php'));
        // Fakes match Symfony's rendered commandline, which is quoted on
        // Windows, so inspect the raw command array instead of a glob pattern.
        Process::fake(function ($process) use ($source) {
            $command = is_array($process->command)
                ? implode(' ', $process->command)
                : (string) $process->command;

            return str_contains($command, 'rev-parse')
                ? Process::result(output: "abc1234\n")
                : Process::result(output: $source);
        });

        $baseline = app(BaselineParserLoader::class)->load();

        $this->assertTrue($baseline['available']);
        $this->assertSame('head', $baseline['label']);
        $this->assertSame('abc1234', $baseline['commit']);
        $this->assertNotNull($baseline['parser']);
        $parsed = $baseline['parser']->parse('truyện hệ thống');
        $this->assertSame(['System'], $parsed['themes']);
    }

    public function test_command_runs_in_a_rolled_back_transaction_without_provider_calls(): void
    {
        $fake = new class implements AIClientInterface
        {
            public int $calls = 0;

            public function complete(string $instruction, string $message, array $options = []): array
            {
                $this->calls++;
                throw new RuntimeException('The evaluation must never call an AI provider.');
            }
        };
        $this->app->instance(AIClientInterface::class, $fake);
        config(['ai.enabled' => true]);
        $outputPath = storage_path('framework/testing/nlp-eval-command-test.json');
        @unlink($outputPath);

        $exit = Artisan::call('nlp:evaluate', [
            '--format' => 'json',
            '--group' => ['invalid'],
            '--baseline' => 'none',
            '--output' => $outputPath,
        ]);
        $output = Artisan::output();
        $report = json_decode(trim($output), true);

        $this->assertSame(0, $exit);
        $this->assertIsArray($report);
        $this->assertSame(2, $report['dataset']['cases_evaluated']);
        $this->assertFalse($report['environment']['ai_enabled']);
        $this->assertSame(0, $report['environment']['provider_calls']);
        $this->assertSame(0, $fake->calls);
        $this->assertTrue(config('ai.enabled'));
        $this->assertSame(0, Comic::query()->where('slug', 'like', 'nlp-eval-%')->count());
        $this->assertFileExists($outputPath);
        $written = json_decode((string) file_get_contents($outputPath), true);
        $this->assertSame($report['dataset']['checksum_sha256'], $written['dataset']['checksum_sha256']);
        @unlink($outputPath);
    }

    public function test_command_refuses_production_without_force(): void
    {
        $this->app['env'] = 'production';

        $exit = Artisan::call('nlp:evaluate', ['--format' => 'json', '--group' => ['invalid']]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Refusing', Artisan::output());
        $this->assertSame(0, Comic::query()->where('slug', 'like', 'nlp-eval-%')->count());
    }
}
