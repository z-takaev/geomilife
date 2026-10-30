<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\News;
use App\Models\Page;
use App\Models\Promo;

it('показывает в списках только опубликованные материалы и текущие акции', function (): void {
    Article::query()->create([
        'title' => 'Опубликованная статья',
        'slug' => 'published-article',
        'published_at' => today(),
        'is_active' => true,
    ]);
    Article::query()->create([
        'title' => 'Будущая статья',
        'slug' => 'future-article',
        'published_at' => today()->addDay(),
        'is_active' => true,
    ]);
    News::query()->create([
        'title' => 'Опубликованная новость',
        'slug' => 'published-news',
        'published_at' => today(),
        'is_active' => true,
    ]);
    News::query()->create([
        'title' => 'Скрытая новость',
        'slug' => 'hidden-news',
        'published_at' => today(),
        'is_active' => false,
    ]);
    Promo::query()->create([
        'title' => 'Текущая акция',
        'slug' => 'current-promo',
        'start_at' => today()->subDay(),
        'end_at' => today()->addDay(),
        'is_active' => true,
    ]);
    Promo::query()->create([
        'title' => 'Завершенная акция',
        'slug' => 'expired-promo',
        'start_at' => today()->subDays(2),
        'end_at' => today()->subDay(),
        'is_active' => true,
    ]);

    $this->get(route('articles.index'))
        ->assertSee('Опубликованная статья')
        ->assertDontSee('Будущая статья');
    $this->get(route('news.index'))
        ->assertSee('Опубликованная новость')
        ->assertDontSee('Скрытая новость');
    $this->get(route('promos.index'))
        ->assertSee('Текущая акция')
        ->assertDontSee('Завершенная акция');
});

it('не открывает неактивные материалы', function (string $modelClass, string $routeName, array $attributes): void {
    $model = $modelClass::query()->create($attributes);

    $this->get(route($routeName, $model))->assertNotFound();
})->with([
    'страница' => [Page::class, 'page', [
        'key' => 'hidden',
        'title' => 'Скрытая страница',
        'slug' => 'hidden-page',
        'is_active' => false,
    ]],
    'новость' => [News::class, 'news.show', [
        'title' => 'Скрытая новость',
        'slug' => 'hidden-news',
        'published_at' => '2026-01-01',
        'is_active' => false,
    ]],
    'статья' => [Article::class, 'articles.show', [
        'title' => 'Скрытая статья',
        'slug' => 'hidden-article',
        'published_at' => '2026-01-01',
        'is_active' => false,
    ]],
    'акция' => [Promo::class, 'promos.show', [
        'title' => 'Скрытая акция',
        'slug' => 'hidden-promo',
        'start_at' => '2026-01-01',
        'end_at' => '2026-12-31',
        'is_active' => false,
    ]],
]);

it('не открывает будущие публикации', function (string $modelClass, string $routeName): void {
    $model = $modelClass::query()->create(['title' => 'Завтрашняя публикация', 'slug' => 'tomorrow', 'published_at' => today()->addDay(), 'is_active' => true]);
    $this->get(route($routeName, $model))->assertNotFound();
})->with([[Article::class, 'articles.show'], [News::class, 'news.show']]);

it('учитывает включительно границы периода акции', function (int $startOffset, int $endOffset, bool $available): void {
    $promo = Promo::query()->create(['title' => 'Акция', 'slug' => 'promo', 'is_active' => true,
        'start_at' => today()->addDays($startOffset), 'end_at' => today()->addDays($endOffset)]);
    $this->get(route('promos.show', $promo))->assertStatus($available ? 200 : 404);
})->with([
    'начинается сегодня' => [0, 1, true],
    'заканчивается сегодня' => [-1, 0, true],
    'начинается завтра' => [1, 2, false],
    'закончилась вчера' => [-2, -1, false],
]);
