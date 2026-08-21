<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_submit_rating(): void
    {
        $comic = Comic::factory()->create();

        $response = $this->postJson("/api/comics/{$comic->id}/ratings", [
            'score'  => 5,
            'review' => 'Great comic!',
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_submit_valid_rating(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/comics/{$comic->id}/ratings", [
            'score'  => 4.5,
            'review' => 'Nội dung cực kỳ cuốn hút!',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status'        => 'success',
            'is_updated'    => false,
            'avg_rating'    => 4.5,
            'total_ratings' => 1,
            'user_score'    => 4.5,
            'user_review'   => 'Nội dung cực kỳ cuốn hút!',
        ]);

        $this->assertDatabaseHas('ratings', [
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
            'score'    => 4.5,
            'review'   => 'Nội dung cực kỳ cuốn hút!',
        ]);
    }

    public function test_submitting_invalid_score_returns_validation_error(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        // Điểm > 5
        $response1 = $this->actingAs($user)->postJson("/api/comics/{$comic->id}/ratings", [
            'score' => 6.0,
        ]);
        $response1->assertUnprocessable();
        $response1->assertJsonValidationErrors(['score']);

        // Điểm < 1
        $response2 = $this->actingAs($user)->postJson("/api/comics/{$comic->id}/ratings", [
            'score' => 0.5,
        ]);
        $response2->assertUnprocessable();
        $response2->assertJsonValidationErrors(['score']);

        // Score không phải số
        $response3 = $this->actingAs($user)->postJson("/api/comics/{$comic->id}/ratings", [
            'score' => 'invalid_number',
        ]);
        $response3->assertUnprocessable();
        $response3->assertJsonValidationErrors(['score']);
    }

    public function test_user_can_update_their_rating(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        // Đánh giá ban đầu
        Rating::factory()->create([
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
            'score'    => 2.0,
            'review'   => 'Tập đầu hơi chán',
        ]);
        $comic->recalculateRating();

        // Gửi cập nhật
        $response = $this->actingAs($user)->postJson("/api/comics/{$comic->id}/ratings", [
            'score'  => 4.0,
            'review' => 'Về sau truyện hay lên hẳn!',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status'     => 'success',
            'is_updated' => true,
            'avg_rating' => 4.0,
            'user_score' => 4.0,
        ]);

        $this->assertEquals(1, Rating::where('user_id', $user->id)->where('comic_id', $comic->id)->count());
    }

    public function test_user_can_delete_their_rating(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        Rating::factory()->create([
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
            'score'    => 5.0,
        ]);
        $comic->recalculateRating();

        $response = $this->actingAs($user)->deleteJson("/api/comics/{$comic->id}/ratings");

        $response->assertOk();
        $response->assertJson([
            'status'        => 'success',
            'avg_rating'    => 0,
            'total_ratings' => 0,
        ]);

        $this->assertDatabaseMissing('ratings', [
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
        ]);
    }

    public function test_public_can_view_ratings_summary_and_breakdown(): void
    {
        $comic = Comic::factory()->create();
        $users = User::factory()->count(3)->create();

        Rating::factory()->create(['user_id' => $users[0]->id, 'comic_id' => $comic->id, 'score' => 5.0]);
        Rating::factory()->create(['user_id' => $users[1]->id, 'comic_id' => $comic->id, 'score' => 5.0]);
        Rating::factory()->create(['user_id' => $users[2]->id, 'comic_id' => $comic->id, 'score' => 4.0]);
        $comic->recalculateRating();

        $response = $this->getJson("/api/comics/{$comic->id}/ratings/summary");

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'data' => [
                'avg_rating',
                'total_ratings',
                'stars' => [
                    '5' => ['count', 'percentage'],
                    '4' => ['count', 'percentage'],
                    '3' => ['count', 'percentage'],
                    '2' => ['count', 'percentage'],
                    '1' => ['count', 'percentage'],
                ],
            ],
        ]);
    }

    public function test_public_can_view_paginated_reviews(): void
    {
        $comic = Comic::factory()->create();
        $users = User::factory()->count(2)->create();

        Rating::factory()->create([
            'user_id'  => $users[0]->id,
            'comic_id' => $comic->id,
            'score'    => 5.0,
            'review'   => 'Review chi tiết 1',
        ]);
        Rating::factory()->create([
            'user_id'  => $users[1]->id,
            'comic_id' => $comic->id,
            'score'    => 4.0,
            'review'   => 'Review chi tiết 2',
        ]);

        $response = $this->getJson("/api/comics/{$comic->id}/ratings/reviews");

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'data' => [
                'data' => [
                    '*' => ['id', 'user_id', 'comic_id', 'score', 'review', 'user'],
                ],
            ],
        ]);
    }

    public function test_user_can_query_their_own_rating(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();

        // Chưa đánh giá
        $resBefore = $this->actingAs($user)->getJson("/api/comics/{$comic->id}/my-rating");
        $resBefore->assertOk();
        $resBefore->assertJson([
            'status'    => 'success',
            'has_rated' => false,
            'score'     => null,
        ]);

        // Đã đánh giá
        Rating::factory()->create([
            'user_id'  => $user->id,
            'comic_id' => $comic->id,
            'score'    => 4.5,
            'review'   => 'Đáng xem',
        ]);

        $resAfter = $this->actingAs($user)->getJson("/api/comics/{$comic->id}/my-rating");
        $resAfter->assertOk();
        $resAfter->assertJson([
            'status'    => 'success',
            'has_rated' => true,
            'score'     => 4.5,
            'review'    => 'Đáng xem',
        ]);
    }
}
