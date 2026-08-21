<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Comic;
use App\Models\Rating;
use App\Models\User;
use App\Services\RatingService;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RatingServiceTest extends TestCase
{
    use RefreshDatabase;

    private RatingService $ratingService;

    protected function setUp(): void
    {
        parent::setUp();
        $recommendationService = new RecommendationService();
        $this->ratingService = new RatingService($recommendationService);
    }

    public function test_rate_creates_new_rating_and_updates_comic_average(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create([
            'avg_rating'    => 0,
            'total_ratings' => 0,
        ]);

        $result = $this->ratingService->rate($user, $comic, 4.5, 'Bộ truyện rất hay!');

        $this->assertEquals('success', $result['status']);
        $this->assertFalse($result['is_updated']);
        $this->assertEquals(4.5, $result['avg_rating']);
        $this->assertEquals(1, $result['total_ratings']);

        $this->assertDatabaseHas('ratings', [
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
            'score'    => 4.5,
            'review'   => 'Bộ truyện rất hay!',
        ]);

        $this->assertDatabaseHas('comics', [
            'id'            => $comic->id,
            'avg_rating'    => 4.5,
            'total_ratings' => 1,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'       => ActivityLog::ACTION_COMIC_RATED,
            'subject_type' => Comic::class,
            'subject_id'   => $comic->id,
        ]);
    }

    public function test_rate_updates_existing_rating_when_same_user_rates_again(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        // Đánh giá lần 1: 3 sao
        $this->ratingService->rate($user, $comic, 3.0, 'Khá bình thường');

        // Đánh giá lần 2: sửa thành 5 sao
        $result = $this->ratingService->rate($user, $comic, 5.0, 'Đoạn sau quá xuất sắc!');

        $this->assertTrue($result['is_updated']);
        $this->assertEquals(5.0, $result['avg_rating']);
        $this->assertEquals(1, $result['total_ratings']);

        // Không tạo bản ghi thứ 2, chỉ update bản ghi cũ
        $this->assertEquals(1, Rating::where('user_id', $user->id)->where('comic_id', $comic->id)->count());

        $this->assertDatabaseHas('ratings', [
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
            'score'    => 5.0,
            'review'   => 'Đoạn sau quá xuất sắc!',
        ]);
    }

    public function test_rate_throws_exception_when_score_out_of_bounds(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->ratingService->rate($user, $comic, 5.5);
    }

    public function test_rate_throws_exception_when_score_less_than_one(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->ratingService->rate($user, $comic, 0.5);
    }

    public function test_remove_rating_deletes_record_and_recalculates_average(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $comic = Comic::factory()->create();

        // 2 user đánh giá
        $this->ratingService->rate($user1, $comic, 5.0);
        $this->ratingService->rate($user2, $comic, 3.0);

        $comic->refresh();
        $this->assertEquals(4.0, $comic->avg_rating);
        $this->assertEquals(2, $comic->total_ratings);

        // User 2 xóa đánh giá
        $result = $this->ratingService->removeRating($user2, $comic);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(5.0, $result['avg_rating']);
        $this->assertEquals(1, $result['total_ratings']);

        $this->assertDatabaseMissing('ratings', [
            'user_id'  => $user2->id,
            'comic_id' => $comic->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'       => ActivityLog::ACTION_COMIC_UNRATED,
            'subject_type' => Comic::class,
            'subject_id'   => $comic->id,
        ]);
    }

    public function test_get_rating_breakdown_calculates_correct_distribution(): void
    {
        $comic = Comic::factory()->create();
        $users = User::factory()->count(4)->create();

        // 2 lượt 5 sao, 1 lượt 4 sao, 1 lượt 2 sao
        $this->ratingService->rate($users[0], $comic, 5.0);
        $this->ratingService->rate($users[1], $comic, 4.8); // round -> 5
        $this->ratingService->rate($users[2], $comic, 3.9); // round -> 4
        $this->ratingService->rate($users[3], $comic, 2.0); // round -> 2

        $breakdown = $this->ratingService->getRatingBreakdown($comic);

        $this->assertEquals(4, $breakdown['total_ratings']);
        $this->assertEquals(2, $breakdown['stars'][5]['count']);
        $this->assertEquals(50.0, $breakdown['stars'][5]['percentage']);
        $this->assertEquals(1, $breakdown['stars'][4]['count']);
        $this->assertEquals(25.0, $breakdown['stars'][4]['percentage']);
        $this->assertEquals(0, $breakdown['stars'][3]['count']);
        $this->assertEquals(0.0, $breakdown['stars'][3]['percentage']);
        $this->assertEquals(1, $breakdown['stars'][2]['count']);
        $this->assertEquals(25.0, $breakdown['stars'][2]['percentage']);
        $this->assertEquals(0, $breakdown['stars'][1]['count']);
    }

    public function test_get_user_rating_returns_correct_rating_or_null(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $comic = Comic::factory()->create();

        $this->ratingService->rate($user1, $comic, 4.0, 'Tuyệt vời');

        $rating1 = $this->ratingService->getUserRating($user1, $comic);
        $rating2 = $this->ratingService->getUserRating($user2, $comic);

        $this->assertNotNull($rating1);
        $this->assertEquals(4.0, $rating1->score);
        $this->assertEquals('Tuyệt vời', $rating1->review);

        $this->assertNull($rating2);
    }
}
