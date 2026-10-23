<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Faq;
use App\Models\HomeSlide;
use App\Models\News;
use App\Models\Product;
use App\Models\Promo;
use App\Models\Review;
use Illuminate\Database\Seeder;

final class FactorySeeder extends Seeder
{
    public function run(): void
    {
        HomeSlide::factory()->count(2)->create();
        Faq::factory()->count(10)->create();
        Review::factory()->count(10)->create();
        Article::factory()->count(5)->create();
        News::factory()->count(5)->create();
        Promo::factory()->count(5)->create();
        Product::factory()->count(10)->create();
    }
}
