<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
final class FaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = fake('ru_RU');

        return [
            'question' => rtrim($faker->sentence(), '.').'?',
            'answer' => '<p>'.$faker->paragraph().'</p>',
            'order' => fake()->numberBetween(1, 1000),
            'is_active' => true,
        ];
    }
}
