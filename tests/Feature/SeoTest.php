<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\Seo;
use App\Support\Seo\SeoManager;
use Database\Seeders\SeoSeeder;

it('использует SEO модели и экранирует значения метаданных', function (): void {
    $page = Page::query()->create(['key' => 'about', 'slug' => 'about', 'title' => 'О нас', 'is_active' => true]);
    $page->seo()->create(['title' => 'Заголовок <script>alert(1)</script>', 'description' => 'Описание "компании"']);
    $this->get(route('page', $page))->assertOk()
        ->assertSee('<title>Заголовок &lt;script&gt;alert(1)&lt;/script&gt;</title>', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('сбрасывает кеш SEO маршрутов после изменения и удаления', function (): void {
    $seo = Seo::query()->create(['route_name' => 'home', 'title' => 'Первый заголовок']);
    expect(Seo::cached()->get('home')->title)->toBe('Первый заголовок');
    $seo->update(['title' => 'Новый заголовок']);
    expect(Seo::cached()->get('home')->title)->toBe('Новый заголовок');
    $seo->delete();
    expect(Seo::cached()->has('home'))->toBeFalse();
});

it('использует резервные значения для пустых SEO полей', function (): void {
    config(['seo.title' => 'Название сайта', 'seo.description' => 'Описание сайта']);
    Seo::query()->create(['route_name' => 'home', 'title' => '   ', 'description' => '   ']);
    $manager = new SeoManager;
    $manager->forRoute('home', 'Заголовок страницы');
    expect($manager->getTitle())->toBe('Заголовок страницы')->and($manager->getDescription())->toBe('Описание сайта');
});

it('сбрасывает состояние SEO при переходе к другой странице', function (): void {
    config(['seo.image' => '/default.webp']);
    $manager = new SeoManager;
    $manager->title('Товар')->type('product')->image('/product.webp');
    $manager->forRoute('contacts', 'Контакты');
    expect($manager->getTitle())->toBe('Контакты')->and($manager->getType())->toBe('website')
        ->and($manager->getImage())->toBe(asset('default.webp'))->and($manager->getSchemas())->toBe([]);
});

it('создает SEO только для индексируемых статических страниц', function (): void {
    $this->seed(SeoSeeder::class);

    expect(Seo::query()->whereNotNull('route_name')->orderBy('route_name')->pluck('route_name')->all())->toBe([
        'articles.index',
        'catalog.index',
        'contacts',
        'faqs',
        'home',
        'news.index',
        'promos.index',
        'reviews',
    ]);
});

it('сохраняет номер страницы в canonical списков', function (): void {
    $this->get(route('news.index', ['page' => 2, 'filter' => 'ignored']))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('news.index', ['page' => 2]).'">', false);
});

it('закрывает от индексации поиск и страницы ошибок', function (): void {
    $this->get(route('search', ['q' => 'масло']))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false);

    $this->get('/missing-page')
        ->assertNotFound()
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
});
