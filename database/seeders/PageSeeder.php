<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

final class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'key' => 'confidentiality',
                'title' => 'Конфиденциальность',
                'slug' => 'konfidentsialnost',
            ],
            [
                'key' => 'policy',
                'title' => 'Политика',
                'slug' => 'politika',
            ],
            [
                'key' => 'delivery_payment',
                'title' => 'Доставка и оплата',
                'slug' => 'dostavka-i-oplata',
            ],
        ];

        foreach ($pages as $page) {
            Page::query()->firstOrCreate(
                ['key' => $page['key']],
                [
                    'title' => $page['title'],
                    'slug' => $page['slug'],
                    'content' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
