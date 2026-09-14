<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Genre;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RecommendationTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminComicMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RecommendationTaxonomySeeder::class);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_create_saves_metadata_and_existing_relations(): void
    {
        $genre = Genre::create(['name' => 'Fantasy']);
        $regular = Tag::create(['name' => 'HOT']);
        $ids = Tag::whereNotNull('category')->pluck('id')->all();
        $this->post(route('admin.comics.store'), ['title' => 'Metadata Comic', 'status' => 'ongoing',
            'genre_ids' => [$genre->id], 'tag_ids' => [$regular->id], 'recommendation_tag_ids' => $ids])
            ->assertSessionHasNoErrors()->assertRedirect();
        $comic = Comic::where('title', 'Metadata Comic')->firstOrFail();
        $this->assertEqualsCanonicalizing([...$ids, $regular->id], $comic->tags->pluck('id')->all());
        $this->assertSame([$genre->id], $comic->genres->pluck('id')->all());
    }

    public function test_update_and_remove_metadata_preserves_other_tags_and_genres(): void
    {
        $comic = Comic::factory()->create();
        $genre = Genre::create(['name' => 'Fantasy']);
        $comic->genres()->attach($genre);
        $legacy = Tag::create(['name' => 'HOT']);
        $other = Tag::create(['name' => 'Special', 'category' => 'marketing']);
        $system = Tag::where('name', 'System')->firstOrFail();
        $dark = Tag::where('name', 'Dark')->firstOrFail();
        $comic->tags()->attach([$legacy->id, $other->id, $system->id]);
        $this->put(route('admin.comics.update', $comic->id), ['recommendation_tag_ids' => [$dark->id]])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEqualsCanonicalizing([$legacy->id, $other->id, $dark->id], $comic->fresh()->tags->pluck('id')->all());
        $this->put(route('admin.comics.update', $comic->id), ['recommendation_tag_ids' => ''])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEqualsCanonicalizing([$legacy->id, $other->id], $comic->fresh()->tags->pluck('id')->all());
        $this->assertSame([$genre->id], $comic->fresh()->genres->pluck('id')->all());
    }

    public function test_regular_tag_update_preserves_metadata(): void
    {
        $comic = Comic::factory()->create();
        $system = Tag::where('name', 'System')->firstOrFail();
        $comic->tags()->attach($system);
        $legacy = Tag::create(['name' => 'HOT']);
        $this->put(route('admin.comics.update', $comic->id), ['tag_ids' => [$legacy->id]])->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing([$system->id, $legacy->id], $comic->fresh()->tags->pluck('id')->all());
    }

    public function test_invalid_category_and_missing_ids_are_rejected_for_create_and_update(): void
    {
        $comic = Comic::factory()->create();
        $genre = Genre::create(['name' => 'Fantasy']);
        foreach ([Tag::create(['name' => 'HOT'])->id, Tag::create(['name' => 'Ad', 'category' => 'marketing'])->id, 999999] as $id) {
            $this->post(route('admin.comics.store'), ['title' => 'Invalid', 'status' => 'ongoing',
                'genre_ids' => [$genre->id], 'recommendation_tag_ids' => [$id]])->assertSessionHasErrors('recommendation_tag_ids.0');
            $this->put(route('admin.comics.update', $comic->id), ['recommendation_tag_ids' => [$id]])->assertSessionHasErrors('recommendation_tag_ids.0');
        }
        $this->put(route('admin.comics.update', $comic->id), ['tag_ids' => [Tag::where('name', 'System')->value('id')]])
            ->assertSessionHasErrors('tag_ids.0');
        $this->assertDatabaseMissing('comics', ['title' => 'Invalid']);
    }

    public function test_forms_group_categories_and_select_saved_and_old_input(): void
    {
        $comic = Comic::factory()->create();
        $system = Tag::where('name', 'System')->firstOrFail();
        $comic->tags()->attach($system);
        foreach ([route('admin.comics.create'), route('admin.comics.edit', $comic->id)] as $url) {
            $response = $this->get($url)->assertOk();
            $response->assertViewHas('metadataTags', fn ($groups) => $groups->every(fn ($tags, $category) => $tags->every(fn ($tag) => $tag->category === $category)));
            $response->assertViewHas('tags', fn ($tags) => $tags->whereNotNull('category')->isEmpty());
        }
        $html = $this->get(route('admin.comics.edit', $comic->id))->getContent();
        $this->assertMatchesRegularExpression('/name="recommendation_tag_ids\[\]" value="'.$system->id.'"\s+checked/', $html);
        $html = $this->withSession(['_old_input' => ['recommendation_tag_ids' => []]])->get(route('admin.comics.edit', $comic->id))->getContent();
        $this->assertDoesNotMatchRegularExpression('/name="recommendation_tag_ids\[\]" value="'.$system->id.'"\s+checked/', $html);
    }

    public function test_member_cannot_write_metadata(): void
    {
        $comic = Comic::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->put(route('admin.comics.update', $comic->id), ['recommendation_tag_ids' => [Tag::first()->id]])->assertRedirect('/');
        $this->assertCount(0, $comic->fresh()->tags);
    }
}
