<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\ComicRecommendation;
use App\Models\Library;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComputeRecommendationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_compute_recommendations_command_calculates_collaborative_co_occurrences(): void
    {
        $users = User::factory()->count(5)->create();
        $comicA = Comic::factory()->create(['title' => 'Comic Alpha']);
        $comicB = Comic::factory()->create(['title' => 'Comic Beta']);
        $comicC = Comic::factory()->create(['title' => 'Comic Gamma']);

        // User 0, 1, 2, 3 bookmark cả Comic Alpha và Comic Beta (Đồng xuất hiện cao)
        foreach ($users->take(4) as $user) {
            Library::create(['user_id' => $user->id, 'comic_id' => $comicA->id]);
            Library::create(['user_id' => $user->id, 'comic_id' => $comicB->id]);
        }

        // User 4 chỉ đọc Comic Gamma
        $chapterC = \App\Models\Chapter::factory()->create(['comic_id' => $comicC->id]);
        ReadingHistory::create([
            'user_id'      => $users[4]->id,
            'comic_id'     => $comicC->id,
            'chapter_id'   => $chapterC->id,
            'last_read_at' => now(),
        ]);

        // Chạy lệnh tính toán
        $this->artisan('comics:compute-recommendations', ['--min-shared' => 1])
            ->assertSuccessful();

        // Kiểm tra bảng comic_recommendations có cặp (Comic A -> Comic B)
        $this->assertDatabaseHas('comic_recommendations', [
            'comic_id'             => $comicA->id,
            'recommended_comic_id' => $comicB->id,
        ]);

        $rec = ComicRecommendation::where('comic_id', $comicA->id)
            ->where('recommended_comic_id', $comicB->id)
            ->first();

        $this->assertNotNull($rec);
        $this->assertGreaterThan(0.5, $rec->score);

        // Kiểm tra RecommendationService::forComic() ưu tiên lấy Comic Beta
        $service = app(RecommendationService::class);
        $recommendations = $service->forComic($comicA, 4);

        $this->assertTrue($recommendations->contains('id', $comicB->id));
    }
}
