<?php

namespace Tests\Feature;

use App\Models\Comic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgeGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_mature_comic_shows_18_plus_badge_and_age_gate_modal(): void
    {
        $matureComic = Comic::factory()->create([
            'title'      => 'Truyện 18+ Trưởng Thành',
            'is_mature'  => true,
            'age_rating' => '18+',
        ]);

        $response = $this->get(route('comics.show', $matureComic->slug));
        $response->assertOk();
        $response->assertSee('🔞 18+');
        $response->assertSee('id="age-gate-modal"', false);
        $response->assertSee('Cảnh Báo Nội Dung 18+');
        $response->assertSee('Tôi Đã Đủ 18 Tuổi');
    }

    public function test_normal_comic_does_not_show_age_gate_modal(): void
    {
        $normalComic = Comic::factory()->create([
            'title'      => 'Truyện Học Đường',
            'is_mature'  => false,
            'age_rating' => 'all',
        ]);

        $response = $this->get(route('comics.show', $normalComic->slug));
        $response->assertOk();
        $response->assertDontSee('id="age-gate-modal"', false);
    }
}
