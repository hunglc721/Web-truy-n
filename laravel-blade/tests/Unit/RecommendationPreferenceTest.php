<?php

namespace Tests\Unit;

use App\Data\RecommendationPreference;
use PHPUnit\Framework\TestCase;

class RecommendationPreferenceTest extends TestCase
{
    public function test_empty_updates_preserve_dimensions_and_status(): void
    {
        $data = ['genres' => ['Fantasy'], 'themes' => ['System'], 'settings' => ['Modern'],
            'character_traits' => ['Smart MC'], 'tones' => ['Dark'], 'relationships' => ['Romance'], 'status' => 'completed'];
        $old = RecommendationPreference::fromArray($data);
        foreach ([[], null, ''] as $empty) {
            $new = RecommendationPreference::fromArray(array_fill_keys(array_keys($data), $empty));
            $this->assertSame($old->toArray(), $old->merge($new)->toArray());
        }
    }

    public function test_new_dimension_replaces_old_without_mutating_either_object(): void
    {
        $old = RecommendationPreference::fromArray(['genres' => ['Fantasy'], 'themes' => ['System'],
            'character_traits' => ['Weak to Strong']]);
        $new = RecommendationPreference::fromArray(['genres' => [], 'themes' => [],
            'character_traits' => ['Overpowered MC'], 'status' => 'completed', 'exclude' => ['Harem']]);
        $beforeOld = $old->toArray();
        $beforeNew = $new->toArray();
        $merged = $old->merge($new)->toArray();
        $this->assertSame(['Fantasy'], $merged['genres']);
        $this->assertSame(['System'], $merged['themes']);
        $this->assertSame(['Overpowered MC'], $merged['character_traits']);
        $this->assertSame('completed', $merged['status']);
        $this->assertSame(['Harem'], $merged['exclude']);
        $this->assertSame($beforeOld, $old->toArray());
        $this->assertSame($beforeNew, $new->toArray());
    }

    public function test_exclusions_accumulate_and_win_across_all_positive_dimensions(): void
    {
        $old = RecommendationPreference::fromArray(['genres' => ['Romance'], 'relationships' => ['Harem'], 'exclude' => ['Dark']]);
        $new = RecommendationPreference::fromArray(['exclude' => ['Harem', 'Romance'], 'tones' => ['Dark']]);
        $merged = $old->merge($new)->toArray();
        $this->assertSame([], $merged['genres']);
        $this->assertSame([], $merged['relationships']);
        $this->assertSame([], $merged['tones']);
        $this->assertSame(['Dark', 'Harem', 'Romance'], $merged['exclude']);
        $this->assertSame([], RecommendationPreference::fromArray(['relationships' => ['Harem'], 'exclude' => ['Harem']])->toArray()['relationships']);
    }

    public function test_normalization_and_returned_array_cannot_modify_dto(): void
    {
        $preference = RecommendationPreference::fromArray(['genres' => [' Fantasy ', 'Fantasy', '', null, 12, []],
            'themes' => ' System ', 'settings' => false, 'status' => [], 'exclude' => null,
            'needs_more_info' => true, 'follow_up_question' => ' Bạn thích gì? ', 'results' => ['not a preference']]);
        $array = $preference->toArray();
        $this->assertSame(['Fantasy'], $array['genres']);
        $this->assertSame(['System'], $array['themes']);
        $this->assertSame([], $array['settings']);
        $this->assertNull($array['status']);
        $this->assertSame([], $array['exclude']);
        $this->assertSame('Bạn thích gì?', $array['follow_up_question']);
        $this->assertArrayNotHasKey('results', $array);
        $array['genres'][] = 'Action';
        $this->assertSame(['Fantasy'], $preference->toArray()['genres']);
    }
}
