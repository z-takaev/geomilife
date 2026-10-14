<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HomeSlide;
use App\Support\Media\FixtureImages;
use Illuminate\Database\Eloquent\Factories\Factory;

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

            app(FixtureImages::class)->attach(
                model: $homeSlide,
                file: 'HomeSlide/desktop/'.$slide['desktop'],
                collection: 'desktop_image',
            );

            app(FixtureImages::class)->attach(
                model: $homeSlide,
                file: 'HomeSlide/mobile/'.$slide['mobile'],
                collection: 'mobile_image',
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
}
