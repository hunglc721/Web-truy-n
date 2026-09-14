<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Genre;
use App\Models\Tag;
use App\Services\AI\AIClientInterface;
use App\Services\AI\IntentParser;
use App\Services\AI\RuleBasedPreferenceParser;
use App\Services\RecommendationTaxonomyService;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_complete_idempotent_and_preserves_legacy_tags_and_relations(): void
    {
        $legacy = Tag::create(['name' => 'HOT', 'color' => '#123456']);
        $existing = Tag::create(['name' => 'System', 'color' => '#abcdef']);
        $comic = Comic::factory()->create();
        $comic->tags()->attach([$legacy->id, $existing->id]);
        Genre::create(['name' => 'Fantasy']);
        $this->seed(RecommendationTaxonomySeeder::class);
        $ids = Tag::orderBy('id')->pluck('id')->all();
        $this->seed(RecommendationTaxonomySeeder::class);
        $this->assertSame($ids, Tag::orderBy('id')->pluck('id')->all());
        $expected = [
            'theme' => ['System', 'Dungeon', 'Revenge', 'Survival', 'Isekai', 'Regression', 'Cultivation'],
            'setting' => ['Modern', 'School', 'Medieval', 'Post Apocalypse'],
            'character' => ['Overpowered MC', 'Weak to Strong', 'Smart MC', 'Anti Hero', 'Cold MC'],
            'tone' => ['Dark', 'Comedy', 'Emotional', 'Serious'],
            'relationship' => ['No Romance', 'Low Romance', 'Romance', 'Harem'],
        ];
        foreach ($expected as $category => $names) {
            $this->assertEqualsCanonicalizing($names, Tag::where('category', $category)->pluck('name')->all());
        }
        $this->assertDatabaseCount('tags', 25);
        $this->assertDatabaseCount('genres', 1);
        $this->assertDatabaseMissing('tags', ['name' => 'Fantasy']);
        $this->assertNull($legacy->fresh()->category);
        $this->assertSame('#123456', $legacy->fresh()->color);
        $this->assertSame('#abcdef', $existing->fresh()->color);
        $this->assertEqualsCanonicalizing([$legacy->id, $existing->id], $comic->tags()->pluck('tags.id')->all());
    }

    public function test_service_returns_only_categorized_taxonomy_and_current_genres(): void
    {
        $this->seed(RecommendationTaxonomySeeder::class);
        Genre::create(['name' => 'Fantasy']);
        Tag::create(['name' => 'HOT']);
        Tag::create(['name' => 'Advertisement', 'category' => 'marketing']);
        $taxonomy = app(RecommendationTaxonomyService::class)->all();
        $this->assertSame(['genres', 'themes', 'settings', 'character_traits', 'tones', 'relationships', 'statuses'], array_keys($taxonomy));
        $this->assertSame(['Fantasy'], $taxonomy['genres']);
        $this->assertContains('System', $taxonomy['themes']);
        $this->assertNotContains('School', $taxonomy['themes']);
        $this->assertSame(['ongoing', 'completed', 'hiatus', 'cancelled'], $taxonomy['statuses']);
        $this->assertNotContains('HOT', array_merge(...array_values($taxonomy)));
        $this->assertNotContains('Advertisement', array_merge(...array_values($taxonomy)));
    }

    public function test_ai_and_fallback_cannot_return_tags_outside_database_taxonomy(): void
    {
        $this->seed(RecommendationTaxonomySeeder::class);
        Genre::create(['name' => 'Fantasy']);
        Tag::create(['name' => 'HOT']);
        Tag::where('name', 'System')->delete();
        Tag::where('name', 'Overpowered MC')->delete();
        $preferences = app(RuleBasedPreferenceParser::class)->parse('fantasy');
        $preferences['themes'] = ['System', 'Super Ultra God', 'HOT', 'Dungeon'];
        $preferences['exclude'] = ['HOT', 'System', 'Harem'];
        $this->mock(AIClientInterface::class)->shouldReceive('complete')->once()
            ->withArgs(function ($prompt) {
                $this->assertStringNotContainsString('"System"', $prompt);
                $this->assertStringNotContainsString('"HOT"', $prompt);
                $this->assertStringNotContainsString('Overpowered MC', $prompt);

                return true;
            })->andReturn(['success' => true, 'content' => json_encode($preferences), 'error' => null]);
        $result = app(IntentParser::class)->parse('fantasy system không harem');
        $this->assertTrue($result['success']);
        $this->assertSame(['Dungeon'], $result['preferences']['themes']);
        $this->assertSame(['Harem'], $result['preferences']['exclude']);
        $this->assertSame([], app(RuleBasedPreferenceParser::class)->parse('system')['themes']);
        $this->assertNull(app(RuleBasedPreferenceParser::class)->quickReply('System'));
    }
}
