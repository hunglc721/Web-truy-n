<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Comic;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'comic_id'    => Comic::factory(),
            'chapter_id'  => null,
            'parent_id'   => null,
            'content'     => fake()->sentence(fake()->numberBetween(5, 30)),
            'status'      => Comment::STATUS_APPROVED,
            'likes_count' => fake()->numberBetween(0, 100),
        ];
    }

    /** State: comment đang chờ duyệt (spam) */
    public function spam(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Comment::STATUS_SPAM,
        ]);
    }

    /** State: comment đang pending */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Comment::STATUS_PENDING,
        ]);
    }

    /** State: comment reply (có parent_id) */
    public function reply(Comment $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'comic_id'   => $parent->comic_id,
            'chapter_id' => $parent->chapter_id,
            'parent_id'  => $parent->id,
        ]);
    }

    /** State: comment trong chapter cụ thể */
    public function forChapter(Chapter $chapter): static
    {
        return $this->state(fn (array $attributes) => [
            'comic_id'   => $chapter->comic_id,
            'chapter_id' => $chapter->id,
        ]);
    }
}
