<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        $name = $this->faker->name();

        return [
            'name'   => $name,
            'slug'   => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(100, 9999),
            'bio'    => $this->faker->sentence(),
            'avatar' => null,
        ];
    }
}
