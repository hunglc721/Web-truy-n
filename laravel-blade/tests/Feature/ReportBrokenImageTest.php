<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportBrokenImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_broken_image_successfully(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'     => $comic->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->postJson(route('reports.store'), [
            'comic_id'    => $comic->id,
            'chapter_id'  => $chapter->id,
            'page_number' => 5,
            'image_url'   => 'https://cdn.example.com/comics/1/p5_broken.jpg',
            'type'        => 'broken_image',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('reports', [
            'user_id'     => $user->id,
            'comic_id'    => $comic->id,
            'chapter_id'  => $chapter->id,
            'page_number' => 5,
            'image_url'   => 'https://cdn.example.com/comics/1/p5_broken.jpg',
            'type'        => 'broken_image',
            'status'      => 'pending',
        ]);
    }

    public function test_guest_can_report_broken_image(): void
    {
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'     => $comic->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->postJson(route('reports.store'), [
            'comic_id'    => $comic->id,
            'chapter_id'  => $chapter->id,
            'page_number' => 12,
            'image_url'   => 'https://cdn.example.com/p12.jpg',
        ]);

        $response->assertStatus(201);

        $report = Report::latest()->first();
        $this->assertNotNull($report);
        $this->assertNull($report->user_id);
        $this->assertEquals(12, $report->page_number);
        $this->assertEquals('pending', $report->status);
    }

    public function test_report_validation_fails_for_invalid_chapter(): void
    {
        $comic1 = Comic::factory()->create();
        $comic2 = Comic::factory()->create();
        $chapter2 = Chapter::factory()->create(['comic_id' => $comic2->id]);

        // Gửi chapter2 nhưng lại truyền comic1_id (sai mismatch quan hệ)
        $response = $this->postJson(route('reports.store'), [
            'comic_id'    => $comic1->id,
            'chapter_id'  => $chapter2->id,
            'page_number' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['chapter_id']);
    }

    public function test_reader_contains_retry_and_report_javascript_handlers(): void
    {
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'        => $comic->id,
            'published_at'    => now()->subDay(),
            'pages'           => ['https://cdn.example.com/p1.jpg'],
            'page_dimensions' => [['width' => 800, 'height' => 1200]],
        ]);

        $response = $this->get(route('chapters.show', [$comic->slug, $chapter->slug]));
        $response->assertOk();

        $response->assertSee('onerror="handleImageError(this)"', false);
        $response->assertSee('data-retries="0"', false);
        $response->assertSee('function handleImageError');
        $response->assertSee('function reportBrokenImage');
        $response->assertSee('Báo lỗi cho Admin');
    }
}
