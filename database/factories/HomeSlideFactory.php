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
     * @var list<array{desktop: string, mobile: string, link_url: string, order: int}>
     */
    private const array SLIDES = [
        [
            'desktop' => 'hero-opening.jpg',
            'mobile' => 'hero-opening-mobile.webp',
            'link_url' => '/articles',
            'order' => 1,
        ],
        [
            'desktop' => 'hero-oils-promo.jpg',
            'mobile' => 'hero-oils-promo-mobile.webp',
            'link_url' => '/promos',
            'order' => 2,
        ],
    ];

    public function configure(): static
    {
        return $this->afterCreating(function (HomeSlide $homeSlide): void {
            $slide = self::SLIDES[$homeSlide->order - 1];

            $this->addImage(
                $homeSlide,
                base_path('tests/Fixtures/images/HomeSlide/desktop/'.$slide['desktop']),
                'desktop_image',
            );

            $this->addImage(
                $homeSlide,
                base_path('tests/Fixtures/images/HomeSlide/mobile/'.$slide['mobile']),
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
        /** @var array{desktop: string, mobile: string, link_url: string, order: int} $slide */
        $slide = fake()->unique()->randomElement(self::SLIDES);

        return [
            'link_url' => $slide['link_url'],
            'open_in_new_tab' => false,
            'order' => $slide['order'],
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
