<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Actions\Images\ProcessImageAction;
use App\Models\HomeSlide;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @extends Factory<HomeSlide>
 */
final class HomeSlideFactory extends Factory
{
    /**
     * @var list<array{desktop: string, mobile: string}>
     */
    private const array IMAGE_PAIRS = [
        [
            'desktop' => 'hero-opening.jpg',
            'mobile' => 'hero-opening-mobile.webp',
        ],
        [
            'desktop' => 'hero-oils-promo.jpg',
            'mobile' => 'hero-oils-promo-mobile.webp',
        ],
    ];

    public function configure(): static
    {
        return $this->afterCreating(function (HomeSlide $homeSlide): void {
            $imagePair = fake()->randomElement(self::IMAGE_PAIRS);

            $this->addImage(
                $homeSlide,
                base_path('tests/Fixtures/images/HomeSlide/desktop/'.$imagePair['desktop']),
                'desktop_image',
            );

            $this->addImage(
                $homeSlide,
                base_path('tests/Fixtures/images/HomeSlide/mobile/'.$imagePair['mobile']),
                'mobile_image',
            );
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'link_url' => fake()->optional()->url(),
            'open_in_new_tab' => fake()->boolean(20),
            'order' => fake()->numberBetween(1, 1000),
            'is_active' => true,
        ];
    }

    private function addImage(HomeSlide $homeSlide, string $imagePath, string $collection): void
    {
        $imageContents = file_get_contents($imagePath);

        if ($imageContents === false) {
            throw new RuntimeException("Не удалось прочитать изображение {$imagePath}.");
        }

        $homeSlide
            ->addMediaFromString(app(ProcessImageAction::class)->run($imageContents))
            ->usingName(pathinfo($imagePath, PATHINFO_FILENAME))
            ->usingFileName(Str::uuid().'.webp')
            ->toMediaCollection($collection);
    }
}
