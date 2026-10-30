<?php

declare(strict_types=1);

use App\Enums\ProductUnit;
use App\Models\Article;
use App\Models\Category;
use App\Models\News;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promo;
use App\Support\Values\Money;

it('включает в карту сайта только доступные динамические страницы', function (): void {
    $activePage = Page::query()->create([
        'key' => 'delivery',
        'title' => 'Доставка',
        'slug' => 'delivery',
        'is_active' => true,
    ]);
    $inactivePage = Page::query()->create([
        'key' => 'hidden',
        'title' => 'Скрытая страница',
        'slug' => 'hidden',
        'is_active' => false,
    ]);
    $category = Category::query()->create([
        'name' => 'Масла',
        'slug' => 'oils',
        'is_active' => true,
    ]);
    $product = Product::query()->create([
        'category_id' => $category->getKey(),
        'name' => 'Льняное масло',
        'slug' => 'linseed-oil',
        'is_active' => true,
    ]);
    ProductVariant::query()->create([
        'product_id' => $product->getKey(),
        'sku' => 'SITEMAP-1',
        'unit' => ProductUnit::MILLILITER,
        'value' => 250,
        'price' => Money::fromRubles(500),
        'is_active' => true,
    ]);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('page', $activePage), false)
        ->assertDontSee(route('page', $inactivePage), false)
        ->assertSee(route('catalog.category', $category), false)
        ->assertSee(route('products.show', $product), false);
});

it('согласует sitemap с датами публикации и периодом действия акций', function (): void {
    $article = Article::query()->create(['title' => 'Статья', 'published_at' => today(), 'is_active' => true]);
    $future = News::query()->create(['title' => 'Новость', 'published_at' => today()->addDay(), 'is_active' => true]);
    $promo = Promo::query()->create(['title' => 'Акция', 'start_at' => today()->subDays(2), 'end_at' => today()->subDay(), 'is_active' => true]);
    $response = $this->get(route('sitemap'))->assertOk();
    $xml = simplexml_load_string($response->getContent());
    expect($xml)->not->toBeFalse();
    $xml->registerXPathNamespace('sitemap', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $locations = array_map(strval(...), $xml->xpath('//sitemap:loc'));
    expect($locations)->toContain(route('articles.show', $article))
        ->not->toContain(route('news.show', $future))
        ->not->toContain(route('promos.show', $promo));
});
