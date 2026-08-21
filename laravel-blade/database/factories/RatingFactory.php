<?php

namespace Database\Factories;

use App\Models\Comic;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rating>
 */
class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'user_id'  => User::factory(),
            'comic_id' => Comic::factory(),
            'score'    => fake()->randomFloat(1, 1.0, 5.0),
            'review'   => fake()->optional(0.7)->paragraph(),
        ];
    }

    /**
     * Score cụ thể
     */
    public function score(float $score): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
        ]);
    }
}
