<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductAvailability;
use App\Enums\ProductUnit;
use App\Models\Category;
use App\Models\Product;
use App\Support\Media\FixtureImages;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    private const array TITLES = [
        'Масло льняное сыродавленное',
        'Масло чёрного тмина холодного отжима',
        'Мёд горный натуральный',
        'Мёд разнотравье',
        'Урбеч из грецкого ореха',
        'Урбеч из семян льна',
        'Чай горный травяной',
        'Мука из семян расторопши',
        'Семена чиа',
        'Натуральная пастила без сахара',
    ];

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            app(FixtureImages::class)->attachRandom(
                model: $product,
                directory: 'Product',
                processImage: static fn (Image $image): Image => $image->fit(Fit::Fill, 1200, 1200, backgroundColor: '#ffffff'),
            );

            $variants = [];
            $variantsCount = fake()->numberBetween(1, 4);

            for ($order = 1; $order <= $variantsCount; $order++) {
                $unit = fake()->randomElement(ProductUnit::cases());

                $variants[] = [
                    'sku' => Str::upper(fake()->unique()->bothify('PRD-#####-???')),
                    'unit' => $unit,
                    'value' => match ($unit) {
                        ProductUnit::PIECE => fake()->numberBetween(1, 12),
                        ProductUnit::GRAM, ProductUnit::MILLILITER => fake()->numberBetween(1, 20) * 50,
                    },
                    'price' => fake()->randomFloat(2, 100, 5000),
                    'discount_percent' => fake()->optional(0.3)->numberBetween(1, 99),
                    'status' => fake()->randomElement(ProductAvailability::cases()),
                    'is_active' => fake()->boolean(85),
                    'order' => $order,
                ];
            }

            $product->variants()->createMany($variants);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(self::TITLES);

        return [
            'category_id' => fake()->randomElement(Category::query()->pluck('id')->all()),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->realText(300),
            'is_new' => fake()->boolean(),
            'is_hit' => fake()->boolean(),
            'is_active' => fake()->boolean(85),
        ];
    }
}
