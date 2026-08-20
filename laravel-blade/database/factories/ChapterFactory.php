<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Comic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chapter>
 */
class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    public function definition(): array
    {
        $chapterNumber = fake()->unique()->numberBetween(1, 9999);

        return [
            'comic_id'       => Comic::factory(),
            'chapter_number' => $chapterNumber,
            'title'          => 'Chapter ' . $chapterNumber,
            'slug'           => 'chapter-' . $chapterNumber,
            'pages'          => $this->fakePagesArray(),
            'content'        => null,
            'views'          => fake()->numberBetween(0, 50000),
            'published_at'   => fake()->dateTimeBetween('-1 year', 'now'),
            'is_free'        => true,
        ];
    }

    /**
     * Tạo mảng URL ảnh giả lập cho cột pages (JSON array).
     */
    protected function fakePagesArray(int $count = 5): array
    {
        return array_map(
            fn($i) => 'https://cdn.example.com/chapters/' . fake()->uuid() . "/page-{$i}.jpg",
            range(1, $count)
        );
    }

    /** State: chapter trả phí */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_free' => false,
        ]);
    }

    /** State: chapter chưa publish */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }
}
