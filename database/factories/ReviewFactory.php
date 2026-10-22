<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReviewSourceEnum;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
final class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_name' => fake()->firstName(),
            'text' => fake()->realText(300),
            'rating' => fake()->randomFloat(1, 1, 5),
            'source' => fake()->optional()->randomElement(ReviewSourceEnum::cases()),
            'reviewed_at' => fake()->dateTimeBetween('-1 year'),
            'is_active' => true,
        ];
    }
}
