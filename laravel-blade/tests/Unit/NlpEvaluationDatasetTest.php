<?php

namespace Tests\Unit;

use App\Services\Evaluation\NlpEvaluationDataset;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NlpEvaluationDatasetTest extends TestCase
{
    private function fixturePath(): string
    {
        return dirname(__DIR__).'/Fixtures/nlp/evaluation_dataset.json';
    }

    public function test_repository_fixture_loads_and_covers_every_required_group(): void
    {
        $dataset = NlpEvaluationDataset::fromFile($this->fixturePath());

        $this->assertSame(1, $dataset->version());
        $this->assertSame(8, $dataset->k());
        $this->assertSame(14, count($dataset->comics()));
        $this->assertSame(41, count($dataset->cases()));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $dataset->checksum());
        $this->assertSame([
            'lookup', 'accented', 'unaccented', 'colloquial', 'taxonomy',
            'negation', 'multi', 'vague', 'invalid', 'outside_taxonomy',
        ], $dataset->groups());
    }

    public function test_fixture_has_unique_ids_and_only_known_relevant_comic_keys(): void
    {
        $dataset = NlpEvaluationDataset::fromFile($this->fixturePath());
        $keys = array_column($dataset->comics(), 'key');
        $ids = array_column($dataset->cases(), 'id');

        $this->assertSame(count($ids), count(array_unique($ids)));
        foreach ($dataset->cases() as $case) {
            foreach ($case['search']['relevant'] ?? [] as $key) {
                $this->assertContains($key, $keys, "Case {$case['id']} references {$key}");
            }
        }
    }

    public function test_query_fixture_is_expanded_and_empty_result_cases_are_explicit(): void
    {
        $dataset = NlpEvaluationDataset::fromFile($this->fixturePath());
        $cases = [];
        foreach ($dataset->cases() as $case) {
            $cases[$case['id']] = $case;
        }

        $this->assertSame(4001, mb_strlen($cases['invalid-oversized']['query']));
        $this->assertTrue($cases['vague-no-results']['search']['expect_empty']);
        $this->assertSame([], $cases['vague-no-results']['search']['relevant']);
        $this->assertTrue($cases['lookup-unpublished-hidden']['search']['expect_empty']);
    }

    public function test_duplicate_case_id_is_rejected(): void
    {
        $data = NlpEvaluationDataset::fromFile($this->fixturePath())->toArray();
        $data['cases'][] = $data['cases'][0];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate case id');
        NlpEvaluationDataset::fromArray($data);
    }

    public function test_unknown_relevant_comic_key_is_rejected(): void
    {
        $data = NlpEvaluationDataset::fromFile($this->fixturePath())->toArray();
        $data['cases'][0]['search']['relevant'] = ['missing_comic'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unknown comic key');
        NlpEvaluationDataset::fromArray($data);
    }

    public function test_expected_value_outside_corpus_taxonomy_is_rejected(): void
    {
        $data = NlpEvaluationDataset::fromFile($this->fixturePath())->toArray();
        $data['cases'][0]['expected']['genres'] = ['Not A Genre'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('outside the corpus taxonomy');
        NlpEvaluationDataset::fromArray($data);
    }

    public function test_expect_empty_with_relevant_comics_is_rejected(): void
    {
        $data = NlpEvaluationDataset::fromFile($this->fixturePath())->toArray();
        $data['cases'][0]['search']['expect_empty'] = true;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot set expect_empty with relevant comics');
        NlpEvaluationDataset::fromArray($data);
    }

    public function test_search_without_relevance_ground_truth_must_declare_empty_expectation(): void
    {
        $data = NlpEvaluationDataset::fromFile($this->fixturePath())->toArray();
        $data['cases'][0]['search']['relevant'] = [];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must set expect_empty=true');
        NlpEvaluationDataset::fromArray($data);
    }

    public function test_case_must_define_exactly_one_query_source(): void
    {
        $data = NlpEvaluationDataset::fromFile($this->fixturePath())->toArray();
        unset($data['cases'][0]['query']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one of query or query_fixture');
        NlpEvaluationDataset::fromArray($data);
    }

    public function test_invalid_status_and_k_are_rejected(): void
    {
        $data = NlpEvaluationDataset::fromFile($this->fixturePath())->toArray();
        $data['corpus']['comics'][0]['status'] = 'airing';

        try {
            NlpEvaluationDataset::fromArray($data);
            $this->fail('Expected invalid comic status to be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('not a supported status', $exception->getMessage());
        }

        $data = NlpEvaluationDataset::fromFile($this->fixturePath())->toArray();
        $data['k'] = 0;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('k must be an integer between 1 and 50');
        NlpEvaluationDataset::fromArray($data);
    }
}
