<?php

namespace Database\Factories;

use App\Models\Comic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comic>
 */
class ComicFactory extends Factory
{
    protected $model = Comic::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title'         => ucwords($title),
            'slug'          => Str::slug($title),
            'cover_image'   => 'covers/' . fake()->uuid() . '.jpg',
            'description'   => fake()->paragraph(),
            'status'        => fake()->randomElement(['ongoing', 'completed', 'hiatus']),
            'is_original'   => false,
            'is_featured'   => false,
            'views'         => fake()->numberBetween(0, 100000),
            'avg_rating'    => fake()->randomFloat(1, 1, 5),
            'total_ratings' => fake()->numberBetween(0, 5000),
            'trending_rank' => null,
            'published_at'  => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }

    /** State: truyện đang trending */
    public function trending(): static
    {
        return $this->state(fn (array $attributes) => [
            'trending_rank' => fake()->numberBetween(1, 100),
        ]);
    }

    /** State: WebComics Original */
    public function original(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_original' => true,
        ]);
    }

    /** State: truyện nổi bật */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /** State: truyện đã hoàn thành */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
