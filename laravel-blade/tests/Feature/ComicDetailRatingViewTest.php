<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComicDetailRatingViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_comic_detail_page_renders_rating_section_with_overview(): void
    {
        $comic = Comic::factory()->create([
            'title'         => 'Huyền Thoại Game Thủ',
            'slug'          => 'huyen-thoai-game-thu',
            'avg_rating'    => 4.8,
            'total_ratings' => 125,
        ]);

        $response = $this->get(route('comics.show', $comic->slug));

        $response->assertOk();
        // Kiểm tra section và các ID thành phần chính của giao diện
        $response->assertSee('ratings-section', false);
        $response->assertSee('Đánh Giá', false);
        $response->assertSee('rating-avg-display', false);
        $response->assertSee('rating-histogram-bars', false);
        $response->assertSee('4.8');
        $response->assertSee('125');
        $response->assertSee('reviews-list-container', false);
    }

    public function test_guest_sees_login_prompt_in_rating_box(): void
    {
        $comic = Comic::factory()->create(['slug' => 'test-comic-guest']);

        $response = $this->get(route('comics.show', $comic->slug));

        $response->assertOk();
        $response->assertSee('Vui lòng đăng nhập để gửi đánh giá');
        $response->assertSee(route('login'));
        $response->assertDontSee('id="star-selector"', false);
    }

    public function test_authenticated_user_sees_interactive_star_selector_and_form(): void
    {
        $user  = User::factory()->create();
        $comic = Comic::factory()->create(['slug' => 'test-comic-auth']);

        $response = $this->actingAs($user)->get(route('comics.show', $comic->slug));

        $response->assertOk();
        $response->assertSee('Đánh giá của bạn');
        $response->assertSee('id="star-selector"', false);
        $response->assertSee('id="rating-review-input"', false);
        $response->assertSee('id="btn-submit-rating"', false);
        $response->assertSee('Gửi Đánh Giá');
    }

    public function test_comic_detail_page_with_multiple_ratings_renders_stats(): void
    {
        $comic = Comic::factory()->create(['slug' => 'test-comic-stats']);
        $users = User::factory()->count(3)->create();

        Rating::factory()->create(['user_id' => $users[0]->id, 'comic_id' => $comic->id, 'score' => 5.0, 'review' => 'Cực phẩm!']);
        Rating::factory()->create(['user_id' => $users[1]->id, 'comic_id' => $comic->id, 'score' => 4.0, 'review' => 'Rất hay']);
        Rating::factory()->create(['user_id' => $users[2]->id, 'comic_id' => $comic->id, 'score' => 3.0, 'review' => null]);
        $comic->recalculateRating();

        $response = $this->get(route('comics.show', $comic->slug));

        $response->assertOk();
        $response->assertSee('4.0'); // Avg: (5+4+3)/3 = 4.0
        $response->assertSee('3');   // Total ratings
    }
}
