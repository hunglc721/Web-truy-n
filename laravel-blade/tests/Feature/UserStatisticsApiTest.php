<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Library;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatisticsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_statistics_endpoints(): void
    {
        $this->getJson('/api/user/statistics/overview')->assertUnauthorized();
        $this->getJson('/api/user/statistics/genres')->assertUnauthorized();
        $this->getJson('/api/user/statistics/badges')->assertUnauthorized();
        $this->getJson('/api/user/statistics/weekly')->assertUnauthorized();
        $this->getJson('/api/user/statistics/export')->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_overview_statistics(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);

        ReadingHistory::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'chapter_id' => $chapter->id, 'last_read_at' => now()]);

        $response = $this->actingAs($user)->getJson('/api/user/statistics/overview');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'data' => [
                'total_library_comics',
                'total_chapters_read',
                'total_ratings',
                'total_comments',
                'total_likes',
                'reading_streak_days',
                'reader_tier' => ['level', 'name', 'icon', 'progress_percent'],
            ],
        ]);
        $response->assertJson([
            'status' => 'success',
            'data'   => [
                'total_chapters_read' => 1,
            ],
        ]);
    }

    public function test_authenticated_user_can_get_badges(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/user/statistics/badges');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => ['id', 'name', 'description', 'icon', 'is_unlocked'],
            ],
        ]);
    }

    public function test_authenticated_user_can_get_weekly_activity(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/user/statistics/weekly');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => ['date', 'day_name', 'count'],
            ],
        ]);
        $this->assertCount(7, $response->json('data'));
    }

    public function test_authenticated_user_can_export_data_as_json(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create(['title' => 'Export Comic']);
        Library::create(['user_id' => $user->id, 'comic_id' => $comic->id, 'status' => 'reading']);

        $response = $this->actingAs($user)->getJson('/api/user/statistics/export');

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="webcomics-history-backup-' . $user->id . '.json"');
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'exported_at',
            'library',
            'reading_history',
        ]);
        $this->assertEquals('Export Comic', $response->json('library.0.title'));
    }
}
