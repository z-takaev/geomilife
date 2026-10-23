<?php

declare(strict_types=1);

use App\Enums\ProductUnit;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
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
        ->assertSee(route('pages.show', $activePage), false)
        ->assertDontSee(route('pages.show', $inactivePage), false)
        ->assertSee(route('catalog.category', $category), false)
        ->assertSee(route('products.show', $product), false);
});
