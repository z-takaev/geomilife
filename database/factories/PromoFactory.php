<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Promo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promo>
 */
final class PromoFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (Promo $promo): void {
            $promo
                ->copyMedia($this->imagePath())
                ->toMediaCollection('image');
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = fake('ru_RU');
        $startAt = fake()->dateTimeBetween('-3 months', '+1 month');

        return [
            'title' => $faker->sentence(6),
            'slug' => fake()->unique()->slug(5),
            'excerpt' => $faker->paragraph(),
            'content' => '<p>'.$faker->paragraph().'</p><p>'.$faker->paragraph().'</p>',
            'published_at' => fake()->dateTimeBetween('-1 year', $startAt),
            'start_at' => $startAt,
            'end_at' => fake()->dateTimeBetween($startAt, '+6 months'),
            'is_active' => true,
        ];
    }

    private function imagePath(): string
    {
        $fileName = fake()->randomElement([
            'promo-daily-health-mobile.webp',
            'promo-healthy-sweets-mobile.webp',
            'promo-honey-mobile.webp',
        ]);

        return base_path('tests/Fixtures/images/Promo/'.$fileName);
    }
}
