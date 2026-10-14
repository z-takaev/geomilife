<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\News;
use App\Models\Page;
use App\Models\Promo;

it('открывает публичную страницу', function (string $routeName): void {
    $this->get(route($routeName))->assertOk();
})->with([
    'главная' => 'home',
    'контакты' => 'contacts.index',
    'вопросы и ответы' => 'faqs.index',
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
        'title' => 'О компании',
        'slug' => 'about',
        'is_active' => true,
    ]);

    $this->get(route('pages.show', $page))->assertOk();
});
