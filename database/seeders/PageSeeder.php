<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

final class PageSeeder extends Seeder
{
    public function run(): void
    {
        Page::query()->upsert([
            [
                'key' => 'production',
                'title' => 'Наше производство',
                'slug' => 'production',
            ],
            [
                'key' => 'delivery_payment',
                'title' => 'Доставка и оплата',
                'slug' => 'delivery',
            ],
            [
                'key' => 'return_exchange',
                'title' => 'Возврат и обмен',
                'slug' => 'returns',
            ],
            [
                'key' => 'about',
                'title' => 'О нас',
                'slug' => 'about',
            ],
            [
                'key' => 'confidentiality',
                'title' => 'Политика конфиденциальности',
                'slug' => 'policy',
            ],
            [
                'key' => 'policy',
                'title' => 'Пользовательское соглашение',
                'slug' => 'terms',
            ],
            [
                'key' => 'quality_control',
                'title' => 'Контроль качества',
                'slug' => 'quality-control',
            ],
        ], ['key'], ['title', 'slug']);
    }
}
