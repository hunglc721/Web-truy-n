<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_a_draft_or_scheduled_comic(): void
    {
        $draft = Comic::factory()->create(['title' => 'Draft Comic', 'published_at' => null]);
        $scheduled = Comic::factory()->create(['title' => 'Scheduled Comic', 'published_at' => now()->addDay()]);

        $this->get('/truyen/'.$draft->slug)->assertNotFound();
        $this->get('/truyen/'.$scheduled->slug)->assertNotFound();
    }

    public function test_guest_can_open_a_published_comic(): void
    {
        $comic = Comic::factory()->create(['title' => 'Published Comic']);

        $this->get('/truyen/'.$comic->slug)->assertOk();
    }

    public function test_admin_can_still_preview_a_draft_comic(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $draft = Comic::factory()->create(['title' => 'Draft Comic', 'published_at' => null]);

        $this->actingAs($admin)->get('/truyen/'.$draft->slug)->assertOk();
    }

    public function test_guest_cannot_read_a_published_chapter_of_a_draft_comic(): void
    {
        $comic = Comic::factory()->create(['title' => 'Hidden Parent Comic', 'published_at' => null]);
        $chapter = Chapter::factory()->for($comic)->create(['published_at' => now()->subDay()]);

        $this->get('/truyen/'.$comic->slug.'/'.$chapter->slug)->assertNotFound();
    }

    public function test_admin_can_still_preview_a_chapter_of_a_draft_comic(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $comic = Comic::factory()->create(['title' => 'Hidden Parent Comic', 'published_at' => null]);
        $chapter = Chapter::factory()->for($comic)->create(['published_at' => now()->subDay()]);

        $this->actingAs($admin)
            ->get('/truyen/'.$comic->slug.'/'.$chapter->slug)
            ->assertOk();
    }
}
