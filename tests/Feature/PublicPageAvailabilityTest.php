<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\News;
use App\Models\Page;
use App\Models\Promo;
use Database\Seeders\PageSeeder;

it('открывает публичную страницу', function (string $routeName): void {
    $this->get(route($routeName))->assertOk();
})->with([
    'главная' => 'home',
    'контакты' => 'contacts.index',
    'вопросы и ответы' => 'faqs.index',
    'отзывы' => 'reviews.index',
    'список новостей' => 'news.index',
    'список статей' => 'articles.index',
    'список акций' => 'promos.index',
    'карта сайта' => 'sitemap',
]);

it('открывает публичную детальную страницу', function (string $modelClass, string $routeName): void {
    $model = $modelClass::factory()->create();

    $this->get(route($routeName, $model))->assertOk();
})->with([
    'новость' => [News::class, 'news.show'],
    'статья' => [Article::class, 'articles.show'],
    'акция' => [Promo::class, 'promos.show'],
]);

it('открывает целевую страницу', function (): void {
    $page = Page::query()->create([
        'key' => 'about',
        'title' => 'О нас',
        'slug' => 'about',
        'is_active' => true,
    ]);

    $this->get(route('pages.show', $page))->assertOk();
});

it('создает заданный набор целевых страниц', function (): void {
    $this->seed(PageSeeder::class);

    expect(Page::query()->orderBy('id')->get(['key', 'title', 'slug'])->toArray())->toBe([
        ['key' => 'production', 'title' => 'Наше производство', 'slug' => 'production'],
        ['key' => 'delivery_payment', 'title' => 'Доставка и оплата', 'slug' => 'delivery'],
        ['key' => 'return_exchange', 'title' => 'Возврат и обмен', 'slug' => 'returns'],
        ['key' => 'about', 'title' => 'О нас', 'slug' => 'about'],
        ['key' => 'confidentiality', 'title' => 'Политика конфиденциальности', 'slug' => 'policy'],
        ['key' => 'policy', 'title' => 'Пользовательское соглашение', 'slug' => 'terms'],
        ['key' => 'quality_control', 'title' => 'Контроль качества', 'slug' => 'quality-control'],
    ]);
});
