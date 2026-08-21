<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Comic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $author = Author::create(['name' => 'Used Author', 'slug' => 'used-author']);
        $comic = Comic::factory()->create();
        $comic->authors()->attach($author->id, ['role' => 'story']);

        $this->actingAs($admin)
            ->delete(route('admin.authors.destroy', $author))
            ->assertRedirect(route('admin.authors.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('authors', ['id' => $author->id]);
    }

    public function test_unused_author_can_be_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $author = Author::create(['name' => 'Unused Author', 'slug' => 'unused-author']);

        $this->actingAs($admin)
            ->delete(route('admin.authors.destroy', $author))
            ->assertRedirect(route('admin.authors.index'));

        $this->assertDatabaseMissing('authors', ['id' => $author->id]);
    }

    public function test_author_search_works(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Author::create(['name' => 'Alpha Writer', 'slug' => 'alpha-writer']);
        Author::create(['name' => 'Beta Writer', 'slug' => 'beta-writer']);

        $this->actingAs($admin)
            ->get(route('admin.authors.index', ['search' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Writer')
            ->assertDontSee('Beta Writer');
    }
}
