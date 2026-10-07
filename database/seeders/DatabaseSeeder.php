<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Faq;
use App\Models\HomeSlide;
use App\Models\News;
use App\Models\Promo;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            FilamentUserSeeder::class,
            PageSeeder::class,
        ]);

        if ($this->container->environment('local')) {
            HomeSlide::factory()->count(2)->create();
            Faq::factory()->count(5)->create();
            Article::factory()->count(5)->create();
            News::factory()->count(5)->create();
            Promo::factory()->count(5)->create();
        }
    }
}
