<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Seo;
use Illuminate\Database\Seeder;

final class SeoSeeder extends Seeder
{
    public function run(): void
    {
        $routeNames = [
            'home',
            'contacts.index',
            'faqs.index',
            'news.index',
            'articles.index',
            'promos.index',
        ];

        foreach ($routeNames as $routeName) {
            Seo::query()->firstOrCreate(
                ['route_name' => $routeName],
                ['title' => null, 'description' => null],
            );
        }
    }
}
