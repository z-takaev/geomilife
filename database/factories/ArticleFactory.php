<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
final class ArticleFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (Article $article): void {
            $article
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

        return [
            'title' => $faker->sentence(6),
            'slug' => fake()->unique()->slug(5),
            'excerpt' => $faker->paragraph(),
            'content' => '<p>'.$faker->paragraph().'</p><p>'.$faker->paragraph().'</p>',
            'published_at' => fake()->dateTimeBetween('-1 year'),
            'is_active' => true,
        ];
    }

    private function imagePath(): string
    {
        $fileName = fake()->randomElement([
            'promo-daily-health-landscape.webp',
            'promo-healthy-sweets-landscape.webp',
            'promo-honey-landscape.webp',
        ]);

        return base_path('tests/Fixtures/images/Article/'.$fileName);
    }
}
